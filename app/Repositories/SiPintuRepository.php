<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\SiPintuApiException;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SiPintu Gateway repository (Server-to-Server method).
 *
 * Encapsulates all HTTP calls to the SiPintu Identity & API Gateway.
 * Credentials are read from config ('services.sipintu.*') which is backed
 * by .env — never hardcoded.
 */
class SiPintuRepository implements SiPintuRepositoryInterface
{
    private function httpClient(): PendingRequest
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $apiToken = trim((string) config('services.sipintu.api_token', ''));
        $clientId = trim((string) config('services.sipintu.client_id', ''));
        $clientSecret = trim((string) config('services.sipintu.client_secret', ''));
        $verifySsl = (bool) config('services.sipintu.verify_ssl', true);

        if ($baseUrl === '') {
            throw new \RuntimeException('Base URL SiPintu belum dikonfigurasi.');
        }

        if ($apiToken === '' && ($clientId === '' || $clientSecret === '')) {
            throw new \RuntimeException('Client ID atau Client Secret SiPintu belum dikonfigurasi.');
        }

        $http = Http::acceptJson()
            ->connectTimeout(max(1, (int) config('services.sipintu.connect_timeout', 10)))
            ->timeout(max(1, (int) config('services.sipintu.timeout', 15)));

        if (! $verifySsl) {
            $http = $http->withoutVerifying();
        }

        if ($apiToken !== '') {
            return $http->withToken($apiToken);
        }

        return $http->withHeaders([
            'X-Client-ID' => $clientId,
            'X-Client-Secret' => $clientSecret,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $query = [];

        if ($nis !== null && $nis !== '') {
            $query['nis'] = $nis;
        }

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        try {
            $client = $this->httpClient();
            $response = $client->get($baseUrl.'/api/v1/sijuna/students', $query);
        } catch (\RuntimeException $e) {
            throw SiPintuApiException::invalidCredentials($e->getMessage());
        } catch (ConnectionException $e) {
            Log::error('SiPintu API connection error (students)', [
                'url' => $baseUrl.'/api/v1/sijuna/students',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw SiPintuApiException::connectionError();
        } catch (\Throwable $e) {
            Log::error('SiPintu API unexpected exception (students)', [
                'url' => $baseUrl.'/api/v1/sijuna/students',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
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
        $query = [];

        if ($nip !== null && $nip !== '') {
            $query['nip'] = $nip;
        }

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        try {
            $client = $this->httpClient();
            $response = $client->get($baseUrl.'/api/v1/sijuna/teachers', $query);
        } catch (\RuntimeException $e) {
            throw SiPintuApiException::invalidCredentials($e->getMessage());
        } catch (ConnectionException $e) {
            Log::error('SiPintu API connection error (teachers)', [
                'url' => $baseUrl.'/api/v1/sijuna/teachers',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
            throw SiPintuApiException::connectionError();
        } catch (\Throwable $e) {
            Log::error('SiPintu API unexpected exception (teachers)', [
                'url' => $baseUrl.'/api/v1/sijuna/teachers',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);
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
        if ($response->unauthorized()) {
            Log::warning('SiPintu API auth error (401)', [
                'url' => $response->effectiveUri() ? (string) $response->effectiveUri() : 'unknown',
            ]);
            throw SiPintuApiException::invalidCredentials('Autentikasi SiPintu gagal. Periksa Client ID dan Client Secret.');
        }

        if ($response->forbidden()) {
            Log::warning('SiPintu API forbidden error (403)', [
                'url' => $response->effectiveUri() ? (string) $response->effectiveUri() : 'unknown',
            ]);
            throw SiPintuApiException::invalidCredentials('Akses ditolak (Forbidden). Credential valid tetapi tidak memiliki permission.');
        }

        if ($response->status() === 404) {
            throw SiPintuApiException::apiError('Endpoint API SiPintu tidak ditemukan (HTTP 404).');
        }

        if ($response->status() === 422) {
            throw SiPintuApiException::apiError('Request tidak sesuai (HTTP 422).');
        }

        if ($response->status() === 429) {
            throw SiPintuApiException::apiError('Terlalu banyak request ke server SiPintu (Rate Limit).');
        }

        if ($response->serverError()) {
            throw SiPintuApiException::apiError('Server SiPintu mengalami gangguan internal (HTTP '.$response->status().').');
        }

        if (! $response->successful()) {
            Log::error('SiPintu API request failed', [
                'status' => $response->status(),
                'url' => $response->effectiveUri() ? (string) $response->effectiveUri() : 'unknown',
            ]);
            throw SiPintuApiException::apiError(
                'SiPintu mengembalikan respons gagal (HTTP '.$response->status().').',
            );
        }

        $body = $response->json();

        // Accept { "data": [...] }, single record object, or bare array/list.
        if (is_array($body) && array_key_exists('data', $body)) {
            if (! is_array($body['data'])) {
                throw SiPintuApiException::apiError(
                    'Respons SiPintu tidak valid: field "data" bukan array. Sinkronisasi dibatalkan.'
                );
            }

            /** @var array<int, array<string, mixed>> $data */
            $data = is_array($body['data']) && ! array_is_list($body['data']) && (isset($body['data']['nis']) || isset($body['data']['nip']))
                ? [$body['data']]
                : array_values($body['data']);
        } elseif (is_array($body) && ! array_is_list($body) && (isset($body['nis']) || isset($body['nip']))) {
            $data = [$body];
        } elseif (is_array($body)) {
            /** @var array<int, array<string, mixed>> $data */
            $data = array_values($body);
        } else {
            throw SiPintuApiException::apiError(
                'Respons SiPintu tidak valid: format JSON tidak dikenali. Sinkronisasi dibatalkan.'
            );
        }

        // A malformed envelope is fatal. Invalid individual records are
        // handled as skipped items by the sync service so one bad item never
        // prevents otherwise valid data from being synchronized.
        if (! $this->isValidRecordList($data)) {
            throw SiPintuApiException::apiError(
                'Respons SiPintu tidak valid: struktur item tidak sesuai. Sinkronisasi dibatalkan.'
            );
        }

        return $data;
    }

    /**
     * Ensure the response is a JSON list. Individual item validation belongs
     * to the service because invalid records are safely skipped and logged.
     *
     * @param  array<int, mixed>  $data
     */
    private function isValidRecordList(array $data): bool
    {
        return array_is_list($data);
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array
    {
        $endpoint = rtrim((string) config('services.sipintu.api_url', ''), '/').'/api/v1/sijuna/students';

        try {
            $response = $this->httpClient()->get($endpoint);

            Log::debug('SiPintu test connection response.', [
                'endpoint' => '/api/v1/sijuna/students',
                'http_status' => $response->status(),
            ]);

            if ($response->successful()) {
                return $this->connectionResult(true, $response->status(), 'Koneksi ke SiPintu berhasil.');
            }

            return match ($response->status()) {
                401 => $this->connectionResult(
                    false,
                    401,
                    'Autentikasi SiPintu gagal.',
                    'authentication',
                    'Client ID atau Client Secret tidak cocok dengan server SiPintu.',
                    'Periksa kembali SIPINTU_CLIENT_ID dan SIPINTU_CLIENT_SECRET di file .env.'
                ),
                403 => $this->connectionResult(
                    false,
                    403,
                    'Akses SiPintu ditolak.',
                    'permission',
                    'Credential valid tetapi tidak memiliki hak akses (Forbidden).',
                    'Pastikan kredensial memiliki hak akses ke modul SIJUNA di gateway SiPintu.'
                ),
                404 => $this->connectionResult(
                    false,
                    404,
                    'Endpoint SiPintu tidak ditemukan.',
                    'endpoint',
                    'URL Endpoint (/api/v1/sijuna/students) mengembalikan HTTP 404.',
                    'Periksa alamat SIPINTU_API_URL pada file .env, pastikan domain dan path base URL sudah benar.'
                ),
                422 => $this->connectionResult(
                    false,
                    422,
                    'Request ke SiPintu tidak valid.',
                    'validation',
                    'Format permintaan tidak dapat diproses oleh server SiPintu.',
                    'Periksa parameter permintaan ke gateway SiPintu.'
                ),
                429 => $this->connectionResult(
                    false,
                    429,
                    'SiPintu membatasi terlalu banyak request.',
                    'rate_limit',
                    'Batas jumlah pemanggilan API (Rate Limit) telah terlampaui.',
                    'Tunggu beberapa menit sebelum mencoba lagi.'
                ),
                default => $this->connectionResult(
                    false,
                    $response->status(),
                    'SiPintu mengembalikan HTTP '.$response->status().'.',
                    $response->serverError() ? 'server' : 'api',
                    'Server SiPintu merespons dengan status error HTTP '.$response->status().'.',
                    'Pastikan server backend SiPintu berjalan normal dan tidak mengalami error internal.'
                ),
            };
        } catch (\RuntimeException $e) {
            Log::warning('SiPintu test connection configuration error.', [
                'endpoint' => '/api/v1/sijuna/students',
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->connectionResult(
                false,
                null,
                $e->getMessage(),
                'configuration',
                $e->getMessage(),
                'Buka file .env dan isi konfigurasi SIPINTU_API_URL, SIPINTU_CLIENT_ID, serta SIPINTU_CLIENT_SECRET.'
            );
        } catch (ConnectionException $e) {
            $rawMsg = $e->getMessage();
            $messageLower = strtolower($rawMsg);
            $errorType = str_contains($messageLower, 'ssl') ? 'ssl' : (str_contains($messageLower, 'timed out') || str_contains($messageLower, 'timeout') ? 'timeout' : 'network');

            Log::warning('SiPintu test connection exception.', [
                'endpoint' => '/api/v1/sijuna/students',
                'exception_class' => $e::class,
                'message' => $rawMsg,
                'error_type' => $errorType,
            ]);

            $troubleshooting = match ($errorType) {
                'ssl' => 'Periksa sertifikat SSL server SiPintu. Anda dapat menyetel SIPINTU_VERIFY_SSL=false di file .env untuk pengujian lokal.',
                'timeout' => 'Server tidak menerima respons dalam batas waktu (timeout). Pastikan jaringan server aktif, domain dapat diakses, atau tingkatkan SIPINTU_TIMEOUT di file .env.',
                default => 'Gagal terhubung ke host SiPintu. Pastikan server terhubung ke internet/intranet dan domain SIPINTU_API_URL dapat dijangkau.',
            };

            return $this->connectionResult(
                false,
                null,
                match ($errorType) {
                    'ssl' => 'Koneksi SSL SiPintu gagal.',
                    'timeout' => 'Koneksi ke SiPintu melebihi batas waktu (Timeout).',
                    default => 'Gagal terhubung ke server SiPintu.',
                },
                $errorType,
                $rawMsg,
                $troubleshooting
            );
        } catch (\Throwable $e) {
            Log::error('SiPintu test connection unexpected exception.', [
                'endpoint' => '/api/v1/sijuna/students',
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->connectionResult(
                false,
                null,
                'Terjadi kesalahan saat menguji koneksi SiPintu.',
                'unexpected',
                $e->getMessage(),
                'Periksa file storage/logs/laravel.log untuk rincian exception selengkapnya.'
            );
        }
    }

    /**
     * @return array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null, detail: string|null, troubleshooting: string|null}
     */
    private function connectionResult(bool $success, ?int $httpStatus, string $message, ?string $errorType = null, ?string $detail = null, ?string $troubleshooting = null): array
    {
        return [
            'success' => $success,
            'status' => $success,
            'connection' => $success,
            'http_status' => $httpStatus,
            'message' => $message,
            'error_type' => $errorType,
            'detail' => $detail,
            'troubleshooting' => $troubleshooting,
        ];
    }
}
