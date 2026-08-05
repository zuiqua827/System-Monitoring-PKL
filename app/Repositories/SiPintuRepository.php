<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\SiPintuApiException;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * SiPintu Gateway repository (Server-to-Server method).
 *
 * Encapsulates all HTTP calls to the SiPintu Identity & API Gateway.
 * Credentials are read from config ('services.sipintu.*') which is backed
 * by .env — never hardcoded.
 */
class SiPintuRepository implements SiPintuRepositoryInterface
{
    private int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('services.sipintu.timeout', 15);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $clientId = (string) config('services.sipintu.client_id');
        $clientSecret = (string) config('services.sipintu.client_secret');

        if ($baseUrl === '' || $clientId === '' || $clientSecret === '') {
            throw SiPintuApiException::invalidCredentials();
        }

        $query = [];

        if ($nis !== null && $nis !== '') {
            $query['nis'] = $nis;
        }

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

$verifySsl = (bool) config('services.sipintu.verify_ssl', true);

        try {
            $client = Http::withHeaders([
                'X-Client-ID' => $clientId,
                'X-Client-Secret' => $clientSecret,
                'Accept' => 'application/json',
            ])->timeout($this->timeout);

            if (! $verifySsl) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get($baseUrl.'/api/v1/sijuna/students', $query);
        } catch (ConnectionException) {
            throw SiPintuApiException::connectionError();
        } catch (\Throwable) {
            throw SiPintuApiException::timeout();
        }

        return $this->parseResponse($response);
    }

    /**
     * Parse the Gateway response with proper error handling.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseResponse(Response $response): array
    {
        if ($response->unauthorized() || $response->forbidden()) {
            throw SiPintuApiException::invalidCredentials();
        }

        if ($response->successful()) {
            $body = $response->json();

            if (is_array($body) && array_key_exists('data', $body) && is_array($body['data'])) {
                /** @var array<int, array<string, mixed>> $data */
                $data = $body['data'];

                return $data;
            }

            if (is_array($body)) {
                /** @var array<int, array<string, mixed>> $body */
                return $body;
            }

            return [];
        }

        throw SiPintuApiException::apiError(
            (string) ($response->json('message') ?? 'Terjadi kesalahan pada server SiPintu.'),
        );
    }
}
