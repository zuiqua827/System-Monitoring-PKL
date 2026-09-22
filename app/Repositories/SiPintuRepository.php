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
            ->timeout(max(1, (int) config('services.sipintu.timeout', 60)))
            ->retry(2, 1000, function (\Throwable $exception, PendingRequest $request): bool {
                return $exception instanceof ConnectionException;
            }, throw: false);

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
     * Sanitizes credentials from exception or diagnostic messages.
     */
    private function sanitizeMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }

        $clientId = trim((string) config('services.sipintu.client_id', ''));
        $clientSecret = trim((string) config('services.sipintu.client_secret', ''));
        $apiToken = trim((string) config('services.sipintu.api_token', ''));

        if ($clientId !== '') {
            $message = str_replace($clientId, '[CLIENT_ID_HIDDEN]', $message);
        }
        if ($clientSecret !== '') {
            $message = str_replace($clientSecret, '[CLIENT_SECRET_HIDDEN]', $message);
        }
        if ($apiToken !== '') {
            $message = str_replace($apiToken, '[API_TOKEN_HIDDEN]', $message);
        }

        $message = preg_replace('/X-Client-Secret:\s*[^\s,\t\r\n]+/i', 'X-Client-Secret: [HIDDEN]', $message) ?? $message;
        $message = preg_replace('/X-Client-ID:\s*[^\s,\t\r\n]+/i', 'X-Client-ID: [HIDDEN]', $message) ?? $message;

        return $message;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array
    {
        $query = [];

        if ($nis !== null && $nis !== '') {
            $query['nis'] = $nis;
        }

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->fetchFromEndpoint('/api/v1/sijuna/students', $query, 'students');
    }

    /**
     * {@inheritDoc}
     */
    public function fetchTeachers(?string $nip = null, ?string $search = null): array
    {
        $query = [];

        if ($nip !== null && $nip !== '') {
            $query['nip'] = $nip;
        }

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->fetchFromEndpoint('/api/v1/sijuna/teachers', $query, 'teachers');
    }

    /**
     * Fetch all records from an endpoint, handling multi-page pagination if present.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    private function fetchFromEndpoint(string $endpoint, array $query, string $logLabel): array
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $url = $baseUrl.$endpoint;

        $response = $this->executeGetRequest($url, $query, $logLabel);
        $firstPageData = $this->parseResponse($response);

        // Check if the response indicates pagination (e.g. last_page > 1 or links.next)
        $body = $response->json();
        $lastPage = 1;
        if (is_array($body)) {
            $lastPage = (int) ($body['meta']['last_page'] ?? $body['last_page'] ?? 1);
        }

        if ($lastPage <= 1) {
            return $firstPageData;
        }

        $allData = $firstPageData;
        for ($page = 2; $page <= $lastPage; $page++) {
            $pageQuery = array_merge($query, ['page' => $page]);
            $pageResponse = $this->executeGetRequest($url, $pageQuery, "{$logLabel} (halaman {$page})");
            $pageData = $this->parseResponse($pageResponse);
            foreach ($pageData as $record) {
                $allData[] = $record;
            }
        }

        return $allData;
    }

    /**
     * Execute a GET request to the SiPintu API with standardized error handling.
     *
     * @param  array<string, mixed>  $query
     */
    private function executeGetRequest(string $url, array $query, string $logLabel): Response
    {
        try {
            $client = $this->httpClient();
            $response = $client->get($url, $query);
        } catch (\RuntimeException $e) {
            throw SiPintuApiException::invalidCredentials($this->sanitizeMessage($e->getMessage()));
        } catch (ConnectionException $e) {
            $rawMsg = $e->getMessage();
            $sanitizedMsg = (string) $this->sanitizeMessage($rawMsg);
            $messageLower = strtolower($rawMsg);
            $isTimeout = str_contains($messageLower, 'timed out') || str_contains($messageLower, 'timeout');
            $bytesInfo = '';
            if (preg_match('/with\s+(\d+)\s+bytes\s+received/i', $rawMsg, $matches)) {
                $bytesInfo = ' (' . number_format((int) $matches[1]) . ' bytes telah diterima sebelum timeout)';
            }

            Log::error("SiPintu API connection error ({$logLabel})", [
                'url' => $url,
                'exception' => get_class($e),
                'message' => $sanitizedMsg,
            ]);

            if ($isTimeout) {
                $timeoutDetail = ($matches[1] ?? null) === '0' || ! isset($matches[1])
                    ? 'Request ke server SiPintu melebihi batas waktu dan belum menerima response (0 bytes diterima).'
                    : "Pengambilan data {$logLabel} dari SiPintu melebihi batas waktu (Timeout){$bytesInfo}.";

                throw SiPintuApiException::apiError(
                    "{$timeoutDetail} Data lokal tetap aman."
                );
            }

            throw SiPintuApiException::connectionError();
        } catch (\Throwable $e) {
            Log::error("SiPintu API unexpected exception ({$logLabel})", [
                'url' => $url,
                'exception' => get_class($e),
                'message' => $this->sanitizeMessage($e->getMessage()),
            ]);
            throw SiPintuApiException::timeout();
        }

        return $response;
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

        if ($response->status() === 530) {
            throw SiPintuApiException::apiError('Server origin SiPintu tidak dapat dijangkau (HTTP 530 / Cloudflare Error 1033). Cloudflare Edge dapat dijangkau tetapi origin/tunnel SiPintu tidak memberikan response.');
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
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        // Priority 1: Use official lightweight health check endpoint /api/v1/health
        $healthEndpoint = $baseUrl.'/api/v1/health';

        try {
            // Lightweight test: enforce max 5 seconds timeout so Test Connection is responsive and never hangs
            $client = $this->httpClient()->timeout(min(5, (int) config('services.sipintu.timeout', 60)));
            $response = $client->get($healthEndpoint);

            Log::debug('SiPintu test connection response.', [
                'endpoint' => '/api/v1/health',
                'http_status' => $response->status(),
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $gatewayMsg = is_array($body) && isset($body['message']) && is_string($body['message'])
                    ? ' '.$body['message']
                    : '';

                return $this->connectionResult(
                    true,
                    $response->status(),
                    'Koneksi ke SiPintu berhasil.'.$gatewayMsg
                );
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
                    'Pastikan kredensial memiliki hak akses ke gateway SiPintu.'
                ),
                404 => $this->connectionResult(
                    false,
                    404,
                    'Endpoint SiPintu tidak ditemukan.',
                    'endpoint',
                    'URL Endpoint (/api/v1/health) mengembalikan HTTP 404.',
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
                    'SiPintu membatasi terlalu banyak request (Rate Limit).',
                    'rate_limit',
                    'Batas jumlah pemanggilan API telah terlampaui.',
                    'Tunggu beberapa menit sebelum mencoba kembali.'
                ),
                500 => $this->connectionResult(
                    false,
                    500,
                    'Server SiPintu mengalami internal error (HTTP 500).',
                    'server',
                    'Aplikasi backend SiPintu mengalami kegagalan internal saat melayani request.',
                    'Hubungi pengelola server SiPintu untuk memeriksa log error aplikasi pada server SiPintu.'
                ),
                502 => $this->connectionResult(
                    false,
                    502,
                    'Bad Gateway dari server proxy SiPintu (HTTP 502).',
                    'server',
                    'Proxy/webserver SiPintu tidak menerima respons valid dari upstream PHP-FPM / aplikasi backend.',
                    'Pastikan service backend SiPintu (misal PHP-FPM atau Octane) aktif berjalan di server SiPintu.'
                ),
                503 => $this->connectionResult(
                    false,
                    503,
                    'Layanan SiPintu sedang tidak tersedia (HTTP 503 Service Unavailable).',
                    'server',
                    'Server SiPintu sedang dalam mode pemeliharaan atau kelebihan beban.',
                    'Coba beberapa saat lagi atau pastikan server SiPintu tidak dalam maintenance.'
                ),
                530 => $this->connectionResult(
                    false,
                    530,
                    'Server origin SiPintu tidak dapat dijangkau (HTTP 530 / Cloudflare Error 1033).',
                    'server',
                    'Cloudflare Edge dapat dijangkau tetapi origin/tunnel SiPintu tidak memberikan response.',
                    'Pastikan komputer/server origin yang menjalankan SiPintu aktif dan koneksi Cloudflare Tunnel (cloudflared) berjalan.'
                ),
                default => $this->connectionResult(
                    false,
                    $response->status(),
                    'SiPintu mengembalikan HTTP '.$response->status().'.',
                    $response->serverError() ? 'server' : 'api',
                    'Server SiPintu merespons dengan status error HTTP '.$response->status().(str_contains(strtolower((string)$response->header('Content-Type')), 'text/html') ? ' (Respons berupa halaman HTML, bukan JSON API).' : '.'),
                    'Pastikan server backend SiPintu berjalan normal dan URL endpoint sesuai.'
                ),
            };
        } catch (\RuntimeException $e) {
            $msg = (string) $this->sanitizeMessage($e->getMessage());
            Log::warning('SiPintu test connection configuration error.', [
                'endpoint' => '/api/v1/health',
                'exception_class' => $e::class,
                'message' => $msg,
            ]);

            return $this->connectionResult(
                false,
                null,
                $msg,
                'configuration',
                $msg,
                'Buka file .env dan isi konfigurasi SIPINTU_API_URL, SIPINTU_CLIENT_ID, serta SIPINTU_CLIENT_SECRET.'
            );
        } catch (ConnectionException $e) {
            $rawMsg = $e->getMessage();
            $sanitizedMsg = (string) $this->sanitizeMessage($rawMsg);
            $messageLower = strtolower($rawMsg);
            $isTimeout = str_contains($messageLower, 'timed out') || str_contains($messageLower, 'timeout');
            $errorType = str_contains($messageLower, 'ssl') ? 'ssl' : ($isTimeout ? 'timeout' : 'network');

            $bytesReceived = null;
            if (preg_match('/with\s+(\d+)\s+bytes\s+received/i', $rawMsg, $matches)) {
                $bytesReceived = (int) $matches[1];
            }

            Log::warning('SiPintu test connection exception.', [
                'endpoint' => '/api/v1/health',
                'exception_class' => $e::class,
                'message' => $sanitizedMsg,
                'error_type' => $errorType,
            ]);

            $troubleshooting = match ($errorType) {
                'ssl' => 'Periksa sertifikat SSL server SiPintu. Anda dapat menyetel SIPINTU_VERIFY_SSL=false di file .env untuk pengujian lokal.',
                'timeout' => $bytesReceived === 0 || $bytesReceived === null
                    ? 'Server SiPintu belum merespons sama sekali (0 bytes received). Pastikan upstream backend dan tunnel SiPintu berjalan normal.'
                    : 'Koneksi ke SiPintu melebihi batas waktu saat menerima respons (' . number_format($bytesReceived) . ' bytes telah diterima).',
                default => 'Gagal terhubung ke host SiPintu sebelum menerima respons. Pastikan server terhubung ke internet/intranet dan domain SIPINTU_API_URL dapat dijangkau.',
            };

            $message = match ($errorType) {
                'ssl' => 'Koneksi SSL SiPintu gagal.',
                'timeout' => $bytesReceived === 0 || $bytesReceived === null
                    ? 'Request ke server SiPintu melebihi batas waktu dan belum menerima response.'
                    : 'Request ke server SiPintu melebihi batas waktu (' . number_format($bytesReceived) . ' bytes diterima).',
                default => 'Koneksi gagal sebelum menerima respons dari server SiPintu.',
            };

            return $this->connectionResult(
                false,
                null,
                $message,
                $errorType,
                $sanitizedMsg,
                $troubleshooting
            );
        } catch (\Throwable $e) {
            $msg = (string) $this->sanitizeMessage($e->getMessage());
            Log::error('SiPintu test connection unexpected exception.', [
                'endpoint' => '/api/v1/health',
                'exception_class' => $e::class,
                'message' => $msg,
            ]);

            return $this->connectionResult(
                false,
                null,
                'Terjadi kesalahan saat menguji koneksi SiPintu.',
                'unexpected',
                $msg,
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
            'message' => $this->sanitizeMessage($message) ?? '',
            'error_type' => $errorType,
            'detail' => $this->sanitizeMessage($detail),
            'troubleshooting' => $this->sanitizeMessage($troubleshooting),
        ];
    }
}
