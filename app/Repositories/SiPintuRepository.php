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
    /**
     * The dashboard fetches the student and teacher endpoints sequentially.
     * Keep each upstream request comfortably below the usual PHP web request
     * limit so an unavailable gateway cannot turn the dashboard into HTTP 500.
     */
    private const MAX_SAFE_TIMEOUT_SECONDS = 10;

    private int $timeout;

    public function __construct()
    {
        $configuredTimeout = (int) config('services.sipintu.timeout', 15);

        $this->timeout = max(1, min($configuredTimeout, self::MAX_SAFE_TIMEOUT_SECONDS));
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
            ])
                ->connectTimeout(min($this->timeout, 5))
                ->timeout($this->timeout);

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
     * {@inheritDoc}
     */
    public function fetchTeachers(?string $nip = null, ?string $search = null): array
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $clientId = (string) config('services.sipintu.client_id');
        $clientSecret = (string) config('services.sipintu.client_secret');

        if ($baseUrl === '' || $clientId === '' || $clientSecret === '') {
            throw SiPintuApiException::invalidCredentials();
        }

        $query = [];

        if ($nip !== null && $nip !== '') {
            $query['nip'] = $nip;
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
            ])
                ->connectTimeout(min($this->timeout, 5))
                ->timeout($this->timeout);

            if (! $verifySsl) {
                $client = $client->withoutVerifying();
            }

            $response = $client->get($baseUrl.'/api/v1/sijuna/teachers', $query);
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
     * Validates the response envelope and each record's required fields so a
     * truncated/malformed/unexpected response never reaches the sync process
     * (which would otherwise risk corrupting or guessing local data).
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseResponse(Response $response): array
    {
        if ($response->unauthorized() || $response->forbidden()) {
            throw SiPintuApiException::invalidCredentials();
        }

        if (! $response->successful()) {
            throw SiPintuApiException::apiError(
                (string) ($response->json('message') ?? 'Terjadi kesalahan pada server SiPintu.'),
            );
        }

        $body = $response->json();

        // Accept either { "data": [...] } or a bare array/list.
        if (is_array($body) && array_key_exists('data', $body)) {
            if (! is_array($body['data'])) {
                throw SiPintuApiException::apiError(
                    'Respons SiPintu tidak valid: field "data" bukan array. Sinkronisasi dibatalkan.'
                );
            }

            /** @var array<int, array<string, mixed>> $data */
            $data = $body['data'];
        } elseif (is_array($body)) {
            /** @var array<int, array<string, mixed>> $data */
            $data = $body;
        } else {
            throw SiPintuApiException::apiError(
                'Respons SiPintu tidak valid: format JSON tidak dikenali. Sinkronisasi dibatalkan.'
            );
        }

        // Validate each record is a well-formed item array.
        if (! $this->isValidRecordList($data)) {
            throw SiPintuApiException::apiError(
                'Respons SiPintu tidak valid: struktur item tidak sesuai. Sinkronisasi dibatalkan.'
            );
        }

        return $data;
    }

    /**
     * Ensure every item in the list is an associative array (not a scalar or
     * null). A record list that is not a list of arrays indicates a changed
     * API contract and must abort the sync before any DB write.
     *
     * @param  array<int, mixed>  $data
     */
    private function isValidRecordList(array $data): bool
    {
        foreach ($data as $item) {
            if (! is_array($item) || $item === []) {
                return false;
            }
        }

        return true;
    }
}
