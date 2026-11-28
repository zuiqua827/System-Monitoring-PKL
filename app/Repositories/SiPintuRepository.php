<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\SiPintuApiException;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
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
    private const PROBE_CONNECT_TIMEOUT_SECONDS = 3;

    private const PROBE_HEALTH_TIMEOUT_SECONDS = 5;

    private const PROBE_ENDPOINT_TIMEOUT_SECONDS = 12;

    private function httpClient(): PendingRequest
    {
        $config = $this->resolveGatewayConfig();

        $http = Http::acceptJson()
            ->connectTimeout(max(1, (int) config('services.sipintu.connect_timeout', 10)))
            ->timeout(max(1, (int) config('services.sipintu.timeout', 60)))
            ->retry(2, 1000, function (\Throwable $exception, PendingRequest $request): bool {
                return $exception instanceof ConnectionException;
            }, throw: false);

        return $this->applyClientAuthAndSsl($http, $config);
    }

    /**
     * Lightweight, no-retry HTTP client strictly for status probes (/health & endpoint availability).
     * Keeps full data sync timeouts untouched while bounding worst-case probe latency < 25s.
     */
    private function probeClient(int $maxTimeoutSeconds): PendingRequest
    {
        $config = $this->resolveGatewayConfig();
        $timeout = min($maxTimeoutSeconds, max(1, (int) config('services.sipintu.timeout', $maxTimeoutSeconds)));
        $connectTimeout = min(self::PROBE_CONNECT_TIMEOUT_SECONDS, max(1, (int) config('services.sipintu.connect_timeout', self::PROBE_CONNECT_TIMEOUT_SECONDS)));

        $http = Http::acceptJson()
            ->connectTimeout($connectTimeout)
            ->timeout($timeout);

        return $this->applyClientAuthAndSsl($http, $config);
    }

    /**
     * @return array{base_url: string, api_token: string, client_id: string, client_secret: string, verify_ssl: bool}
     */
    private function resolveGatewayConfig(): array
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

        return [
            'base_url' => $baseUrl,
            'api_token' => $apiToken,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'verify_ssl' => $verifySsl,
        ];
    }

    /**
     * @param  array{base_url: string, api_token: string, client_id: string, client_secret: string, verify_ssl: bool}  $config
     */
    private function applyClientAuthAndSsl(PendingRequest $http, array $config): PendingRequest
    {
        if (! $config['verify_ssl']) {
            $http = $http->withoutVerifying();
        }

        if ($config['api_token'] !== '') {
            return $http->withToken($config['api_token']);
        }

        return $http->withHeaders([
            'X-Client-ID' => $config['client_id'],
            'X-Client-Secret' => $config['client_secret'],
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

        return $this->fetchFromEndpoint(self::ENDPOINT_STUDENTS, $query, 'students');
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

        return $this->fetchFromEndpoint(self::ENDPOINT_TEACHERS, $query, 'teachers');
    }

    /**
     * {@inheritDoc}
     */
    public function fetchDudi(?string $search = null): array
    {
        $query = [];

        if ($search !== null && $search !== '') {
            $query['search'] = $search;
        }

        return $this->fetchFromEndpoint(self::ENDPOINT_DUDI, $query, 'dudi');
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

        $configuredPerPage = config('services.sipintu.per_page');
        if ($configuredPerPage !== null && ! isset($query['per_page'])) {
            $query['per_page'] = (int) $configuredPerPage;
        }

        $response = $this->executeGetRequest($url, $query, $logLabel);
        $firstPageData = $this->parseResponse($response, $logLabel);

        // Inspect pagination metadata (length-aware last_page OR cursor/simplePaginate next link).
        $pagination = $this->extractPaginationMeta($response->json());
        $lastPage = $pagination['last_page'];
        $currentPage = $pagination['current_page'];
        $hasNextLink = $pagination['next_page'] !== null;

        // Defensive infinite loop guard: max 500 pages, must be >= 1.
        $maxPages = 500;
        $lastPage = min($maxPages, max(1, $lastPage));

        if (! $hasNextLink && ($lastPage <= 1 || $lastPage < $currentPage)) {
            return $firstPageData;
        }

        $allData = $firstPageData;
        $visitedPages = [max(1, $currentPage) => true];
        $page = max(1, $currentPage) + 1;

        while ($page <= $maxPages && ($hasNextLink || $page <= $lastPage)) {
            if (isset($visitedPages[$page])) {
                break;
            }
            $visitedPages[$page] = true;

            $pageLabel = $hasNextLink && $lastPage <= 1
                ? "{$logLabel} (halaman {$page})"
                : "{$logLabel} (halaman {$page} dari {$lastPage})";

            $pageQuery = array_merge($query, ['page' => $page]);
            $pageResponse = $this->executeGetRequest($url, $pageQuery, $pageLabel);
            $pageData = $this->parseResponse($pageResponse, "{$logLabel} (halaman {$page})");

            if ($pageData === []) {
                break;
            }

            foreach ($pageData as $record) {
                $allData[] = $record;
            }

            $pageMeta = $this->extractPaginationMeta($pageResponse->json());
            if ($hasNextLink) {
                if ($pageMeta['next_page'] === null) {
                    break;
                }
                $page = $pageMeta['next_page'];
            } else {
                $page++;
            }
        }

        return $allData;
    }

    /**
     * Extract pagination metadata from root or nested data paginator envelope.
     *
     * @return array{current_page: int, last_page: int, next_page: int|null}
     */
    private function extractPaginationMeta(mixed $body): array
    {
        if (! is_array($body)) {
            return ['current_page' => 1, 'last_page' => 1, 'next_page' => null];
        }

        $nestedData = is_array($body['data'] ?? null) && ! array_is_list($body['data']) ? $body['data'] : [];
        $meta = is_array($body['meta'] ?? null)
            ? $body['meta']
            : (is_array($nestedData['meta'] ?? null) ? $nestedData['meta'] : []);

        $rawLastPage = $meta['last_page']
            ?? $meta['pagination']['total_pages']
            ?? $body['last_page']
            ?? $nestedData['last_page']
            ?? null;
        $lastPage = is_numeric($rawLastPage) ? (int) $rawLastPage : 1;

        $rawCurrentPage = $meta['current_page']
            ?? $body['current_page']
            ?? $nestedData['current_page']
            ?? null;
        $currentPage = is_numeric($rawCurrentPage) ? (int) $rawCurrentPage : 1;

        $nextPage = $this->extractNextPageNumber($body)
            ?? ($nestedData !== [] ? $this->extractNextPageNumber($nestedData) : null);

        return [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'next_page' => $nextPage,
        ];
    }

    /**
     * Extract next page number when API uses Laravel simplePaginate / links.next without last_page.
     *
     * @param  array<string, mixed>  $body
     */
    private function extractNextPageNumber(array $body): ?int
    {
        $nextUrl = $body['next_page_url'] ?? null;
        if (! is_string($nextUrl) && is_array($body['links'] ?? null)) {
            $nextUrl = $body['links']['next'] ?? null;
        }

        if (! is_string($nextUrl) || trim($nextUrl) === '') {
            return null;
        }

        $queryStr = parse_url($nextUrl, PHP_URL_QUERY);
        if (! is_string($queryStr)) {
            return null;
        }

        parse_str($queryStr, $params);
        $rawPage = $params['page'] ?? null;

        if (is_string($rawPage) && ctype_digit($rawPage)) {
            $next = (int) $rawPage;

            return $next > 1 ? $next : null;
        }

        return null;
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
    private function parseResponse(Response $response, string $logLabel = ''): array
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
            $desc = $logLabel !== '' ? " ({$logLabel})" : '';
            throw SiPintuApiException::apiError("Endpoint API SiPintu{$desc} tidak ditemukan (HTTP 404).");
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

        $rawBody = trim($response->body());
        $body = $rawBody !== '' ? json_decode($rawBody, true) : null;

        if ($rawBody === '' || json_last_error() !== JSON_ERROR_NONE || ! is_array($body)) {
            throw SiPintuApiException::apiError(
                'Respons SiPintu tidak valid: format JSON tidak dikenali. Sinkronisasi dibatalkan.'
            );
        }

        if ($this->isExplicitErrorPayload($body)) {
            throw SiPintuApiException::apiError(
                'Respons SiPintu mengembalikan status error dalam payload JSON. Sinkronisasi dibatalkan.'
            );
        }

        $data = $this->extractRecordContainer($body, $rawBody);

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
     * Determine whether decoded JSON indicates an API-level error payload despite HTTP 200.
     *
     * @param  array<string|int, mixed>  $body
     */
    private function isExplicitErrorPayload(array $body): bool
    {
        if (($body['success'] ?? null) === false) {
            return true;
        }

        $status = $body['status'] ?? null;
        if (is_string($status) && in_array(strtolower(trim($status)), ['error', 'failed', 'fail'], true)) {
            return true;
        }

        return array_key_exists('error', $body) && $body['error'] !== null && $body['error'] !== false;
    }

    /**
     * Extract normalized list of records from explicit SiPintu response envelopes
     * (`data`, `data.data`, `data.items`, `items`, bare list, or single record object)
     * without flattening associative objects via array_values().
     *
     * @param  array<string|int, mixed>  $body
     * @return array<int, array<string, mixed>>
     */
    private function extractRecordContainer(array $body, string $rawBody): array
    {
        if (array_key_exists('data', $body)) {
            if (! is_array($body['data'])) {
                throw SiPintuApiException::apiError(
                    'Respons SiPintu tidak valid: field "data" bukan array. Sinkronisasi dibatalkan.'
                );
            }

            $inner = $body['data'];
            if ($inner === []) {
                return [];
            }

            if (array_is_list($inner)) {
                /** @var array<int, array<string, mixed>> $inner */
                return $inner;
            }

            if (array_key_exists('data', $inner)) {
                if (! is_array($inner['data'])) {
                    throw SiPintuApiException::apiError(
                        'Respons SiPintu tidak valid: field "data.data" bukan array. Sinkronisasi dibatalkan.'
                    );
                }

                return $this->normalizeCollectionOrSingleRecord($inner['data']);
            }

            if (array_key_exists('items', $inner)) {
                if (! is_array($inner['items'])) {
                    throw SiPintuApiException::apiError(
                        'Respons SiPintu tidak valid: field "data.items" bukan array. Sinkronisasi dibatalkan.'
                    );
                }

                return $this->normalizeCollectionOrSingleRecord($inner['items']);
            }

            /** @var array<string, mixed> $inner */
            return [$inner];
        }

        if (! array_is_list($body) && array_key_exists('items', $body)) {
            if (! is_array($body['items'])) {
                throw SiPintuApiException::apiError(
                    'Respons SiPintu tidak valid: field "items" bukan array. Sinkronisasi dibatalkan.'
                );
            }

            return $this->normalizeCollectionOrSingleRecord($body['items']);
        }

        if (array_is_list($body)) {
            if ($body === [] && $rawBody === '{}') {
                throw SiPintuApiException::apiError(
                    'Respons SiPintu tidak valid: format amplop JSON kosong tidak dikenali. Sinkronisasi dibatalkan.'
                );
            }

            /** @var array<int, array<string, mixed>> $body */
            return $body;
        }

        if ($this->looksLikeSingleEntityRecord($body)) {
            /** @var array<string, mixed> $body */
            return [$body];
        }

        throw SiPintuApiException::apiError(
            'Respons SiPintu tidak valid: format amplop JSON tidak dikenali. Sinkronisasi dibatalkan.'
        );
    }

    /**
     * Normalize an already-extracted list or single entity object into a list of records.
     *
     * @param  array<string|int, mixed>  $container
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCollectionOrSingleRecord(array $container): array
    {
        if ($container === []) {
            return [];
        }

        if (array_is_list($container)) {
            /** @var array<int, array<string, mixed>> $container */
            return $container;
        }

        /** @var array<string, mixed> $container */
        return [$container];
    }

    /**
     * Detect whether a bare JSON object represents a single entity record (Siswa, Guru, or DUDI)
     * rather than a status/meta envelope missing its data payload.
     *
     * @param  array<string|int, mixed>  $record
     */
    private function looksLikeSingleEntityRecord(array $record): bool
    {
        $entityKeys = [
            'id',
            'external_id',
            'dudi_code',
            'kode_dudi',
            'kode',
            'sipintu_id',
            'dudi_id',
            'sipintu_user_id',
            'nis',
            'nisn',
            'nip',
            'nama',
            'nama_perusahaan',
            'perusahaan',
            'name',
            'email',
            'email_perusahaan',
            'penanggung_jawab',
            'no_telepon',
            'telepon',
            'alamat',
            'classroom_id',
        ];

        foreach ($entityKeys as $key) {
            if (array_key_exists($key, $record)) {
                return true;
            }
        }

        return false;
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
        // Lightweight health check endpoint /api/v1/health (short timeout, zero retries)
        $healthEndpoint = $baseUrl.'/api/v1/health';

        try {
            $client = $this->probeClient(self::PROBE_HEALTH_TIMEOUT_SECONDS);
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

    /**
     * {@inheritDoc}
     */
    public function checkEndpointStatus(string $endpoint): array
    {
        try {
            $config = $this->resolveGatewayConfig();
            $client = $this->probeClient(self::PROBE_ENDPOINT_TIMEOUT_SECONDS);
            $response = $client->get(
                $config['base_url'].$endpoint,
                $this->probeQueryFor($endpoint),
            );

            return $this->resolveEndpointProbeOutcome($endpoint, $response);
        } catch (\Throwable $e) {
            return $this->resolveEndpointProbeOutcome($endpoint, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkEndpointsStatus(array $endpoints): array
    {
        $normalized = array_values(array_unique(array_filter(
            $endpoints,
            static fn (string $ep): bool => trim($ep) !== '',
        )));

        if ($normalized === []) {
            return [];
        }

        try {
            $config = $this->resolveGatewayConfig();
        } catch (\Throwable $e) {
            $errorOutcome = $this->resolveEndpointProbeOutcome($normalized[0], $e);
            $results = [];
            foreach ($normalized as $ep) {
                $results[$ep] = $errorOutcome;
            }

            return $results;
        }

        $timeout = min(self::PROBE_ENDPOINT_TIMEOUT_SECONDS, max(1, (int) config('services.sipintu.timeout', self::PROBE_ENDPOINT_TIMEOUT_SECONDS)));
        $connectTimeout = min(self::PROBE_CONNECT_TIMEOUT_SECONDS, max(1, (int) config('services.sipintu.connect_timeout', self::PROBE_CONNECT_TIMEOUT_SECONDS)));

        try {
            $responses = Http::pool(function (Pool $pool) use ($normalized, $config, $timeout, $connectTimeout): array {
                $requests = [];
                foreach ($normalized as $endpoint) {
                    $pending = $this->applyClientAuthAndSsl(
                        $pool->as($endpoint)
                            ->acceptJson()
                            ->connectTimeout($connectTimeout)
                            ->timeout($timeout),
                        $config,
                    );

                    $requests[$endpoint] = $pending->get(
                        $config['base_url'].$endpoint,
                        $this->probeQueryFor($endpoint),
                    );
                }

                return $requests;
            });

            $results = [];
            foreach ($normalized as $endpoint) {
                $results[$endpoint] = $this->resolveEndpointProbeOutcome(
                    $endpoint,
                    $responses[$endpoint] ?? null,
                );
            }

            return $results;
        } catch (\Throwable) {
            // Fallback per-endpoint when a pool stub throws synchronously (e.g. Http::fake closures)
            $results = [];
            foreach ($normalized as $endpoint) {
                $results[$endpoint] = $this->checkEndpointStatus($endpoint);
            }

            return $results;
        }
    }

    /**
     * @return array<string, int>
     */
    private function probeQueryFor(string $endpoint): array
    {
        return $endpoint === self::ENDPOINT_HEALTH
            ? []
            : ['page' => 1, 'per_page' => 1];
    }

    /**
     * Normalize HTTP responses or transport exceptions into structured endpoint probe statuses.
     *
     * @return array{status: int|null, http_status: int|null, success: bool, error_type: string|null, message: string, count: int|null}
     */
    private function resolveEndpointProbeOutcome(string $endpoint, mixed $outcome): array
    {
        if ($outcome instanceof \Throwable) {
            return $this->probeExceptionResult($endpoint, $outcome);
        }

        if (! $outcome instanceof Response) {
            return [
                'status' => null,
                'http_status' => null,
                'success' => false,
                'error_type' => 'connection',
                'message' => "Gagal memeriksa status endpoint {$endpoint}: respons tidak valid.",
                'count' => null,
            ];
        }

        $httpStatus = $outcome->status();

        if ($httpStatus < 200 || $httpStatus >= 300) {
            return $this->probeHttpErrorResult($endpoint, $httpStatus);
        }

        $rawBody = trim($outcome->body());
        $decoded = $rawBody !== '' ? json_decode($rawBody, true) : null;

        if ($rawBody === '' || json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [
                'status' => $httpStatus,
                'http_status' => $httpStatus,
                'success' => false,
                'error_type' => 'invalid_payload',
                'message' => "Endpoint {$endpoint} merespons HTTP {$httpStatus} namun body bukan JSON yang valid.",
                'count' => null,
            ];
        }

        if ($this->isExplicitErrorPayload($decoded)) {
            return [
                'status' => $httpStatus,
                'http_status' => $httpStatus,
                'success' => false,
                'error_type' => 'invalid_payload',
                'message' => "Endpoint {$endpoint} mengembalikan payload error pada HTTP {$httpStatus}.",
                'count' => null,
            ];
        }

        if ($endpoint === self::ENDPOINT_HEALTH) {
            $hasHealthFields = array_key_exists('status', $decoded)
                || array_key_exists('ok', $decoded)
                || array_key_exists('gateway', $decoded);

            return [
                'status' => $httpStatus,
                'http_status' => $httpStatus,
                'success' => $hasHealthFields,
                'error_type' => $hasHealthFields ? null : 'invalid_payload',
                'message' => $hasHealthFields
                    ? "Endpoint aktif (HTTP {$httpStatus})."
                    : "Endpoint {$endpoint} merespons HTTP {$httpStatus} namun struktur health check tidak sesuai.",
                'count' => null,
            ];
        }

        $validation = $this->validateEndpointDataPayload($decoded, $rawBody);

        return [
            'status' => $httpStatus,
            'http_status' => $httpStatus,
            'success' => $validation['valid'],
            'error_type' => $validation['valid'] ? null : 'invalid_payload',
            'message' => $validation['valid']
                ? "Endpoint aktif (HTTP {$httpStatus})."
                : "Endpoint {$endpoint} merespons HTTP {$httpStatus} namun struktur payload tidak sesuai ({$validation['reason']}).",
            'count' => $validation['valid'] ? $validation['count'] : null,
        ];
    }

    /**
     * @return array{status: int, http_status: int, success: false, error_type: string, message: string, count: null}
     */
    private function probeHttpErrorResult(string $endpoint, int $httpStatus): array
    {
        if ($httpStatus === 404) {
            return [
                'status' => 404,
                'http_status' => 404,
                'success' => false,
                'error_type' => 'not_found',
                'message' => "Endpoint {$endpoint} belum tersedia di server SiPintu (HTTP 404).",
                'count' => null,
            ];
        }

        if ($httpStatus === 401) {
            return [
                'status' => 401,
                'http_status' => 401,
                'success' => false,
                'error_type' => 'authentication',
                'message' => "Akses endpoint {$endpoint} ditolak (HTTP 401 Unauthorized). Periksa Client ID / Client Secret.",
                'count' => null,
            ];
        }

        if ($httpStatus === 403) {
            return [
                'status' => 403,
                'http_status' => 403,
                'success' => false,
                'error_type' => 'permission',
                'message' => "Akses endpoint {$endpoint} ditolak (HTTP 403 Forbidden). Credential tidak memiliki izin.",
                'count' => null,
            ];
        }

        if ($httpStatus >= 500) {
            return [
                'status' => $httpStatus,
                'http_status' => $httpStatus,
                'success' => false,
                'error_type' => 'server',
                'message' => "Server SiPintu mengalami gangguan pada {$endpoint} (HTTP {$httpStatus}).",
                'count' => null,
            ];
        }

        return [
            'status' => $httpStatus,
            'http_status' => $httpStatus,
            'success' => false,
            'error_type' => 'api',
            'message' => "Respons gagal pada {$endpoint} (HTTP {$httpStatus}).",
            'count' => null,
        ];
    }

    /**
     * @return array{status: null, http_status: null, success: false, error_type: string, message: string, count: null}
     */
    private function probeExceptionResult(string $endpoint, \Throwable $e): array
    {
        $sanitized = (string) $this->sanitizeMessage($e->getMessage());
        $lower = strtolower($e->getMessage());

        if ($e instanceof \RuntimeException && (str_contains($lower, 'dikonfigurasi') || str_contains($lower, 'client'))) {
            return [
                'status' => null,
                'http_status' => null,
                'success' => false,
                'error_type' => 'configuration',
                'message' => $sanitized,
                'count' => null,
            ];
        }

        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return [
                'status' => null,
                'http_status' => null,
                'success' => false,
                'error_type' => 'timeout',
                'message' => "Pengecekan endpoint {$endpoint} melebihi batas waktu (Timeout).",
                'count' => null,
            ];
        }

        return [
            'status' => null,
            'http_status' => null,
            'success' => false,
            'error_type' => 'connection',
            'message' => "Gagal terhubung ke endpoint {$endpoint}: {$sanitized}",
            'count' => null,
        ];
    }

    /**
     * Validate that a 2xx data endpoint payload uses a recognized collection/record envelope
     * and extract total record count cleanly.
     *
     * @param  array<string|int, mixed>  $decoded
     * @return array{valid: bool, count: int|null, reason: string|null}
     */
    private function validateEndpointDataPayload(array $decoded, string $rawBody): array
    {
        $meta = is_array($decoded['meta'] ?? null) ? $decoded['meta'] : [];

        if (array_key_exists('data', $decoded)) {
            if (! is_array($decoded['data'])) {
                return ['valid' => false, 'count' => null, 'reason' => 'field data bukan array'];
            }

            $inner = $decoded['data'];
            $innerMeta = ! array_is_list($inner) && is_array($inner['meta'] ?? null) ? $inner['meta'] : [];
            $rawCount = $meta['total']
                ?? $meta['pagination']['total']
                ?? $decoded['total']
                ?? $decoded['count']
                ?? $innerMeta['total']
                ?? (! array_is_list($inner) ? ($inner['total'] ?? $inner['count'] ?? null) : null);

            if (is_numeric($rawCount)) {
                return ['valid' => true, 'count' => (int) $rawCount, 'reason' => null];
            }

            if ($inner === []) {
                return ['valid' => true, 'count' => 0, 'reason' => null];
            }

            if (array_is_list($inner)) {
                return ['valid' => true, 'count' => count($inner), 'reason' => null];
            }

            if (array_key_exists('data', $inner)) {
                if (! is_array($inner['data'])) {
                    return ['valid' => false, 'count' => null, 'reason' => 'field data.data bukan array'];
                }

                $count = array_is_list($inner['data']) ? count($inner['data']) : 1;

                return ['valid' => true, 'count' => $count, 'reason' => null];
            }

            if (array_key_exists('items', $inner)) {
                if (! is_array($inner['items'])) {
                    return ['valid' => false, 'count' => null, 'reason' => 'field data.items bukan array'];
                }

                $count = array_is_list($inner['items']) ? count($inner['items']) : 1;

                return ['valid' => true, 'count' => $count, 'reason' => null];
            }

            if ($this->isExplicitErrorPayload($inner) || ! $this->looksLikeSingleEntityRecord($inner)) {
                return ['valid' => false, 'count' => null, 'reason' => 'objek data tidak memiliki field entitas yang dikenali'];
            }

            return ['valid' => true, 'count' => 1, 'reason' => null];
        }

        if (! array_is_list($decoded) && array_key_exists('items', $decoded)) {
            if (! is_array($decoded['items'])) {
                return ['valid' => false, 'count' => null, 'reason' => 'field items bukan array'];
            }

            $rawCount = $meta['total'] ?? $decoded['total'] ?? $decoded['count'] ?? null;
            $count = is_numeric($rawCount)
                ? (int) $rawCount
                : (array_is_list($decoded['items']) ? count($decoded['items']) : 1);

            return ['valid' => true, 'count' => $count, 'reason' => null];
        }

        if (array_is_list($decoded)) {
            if ($decoded === [] && $rawBody === '{}') {
                return ['valid' => false, 'count' => null, 'reason' => 'objek JSON kosong tanpa field data'];
            }

            if ($decoded !== [] && ! is_array($decoded[0])) {
                return ['valid' => false, 'count' => null, 'reason' => 'daftar item bukan array objek'];
            }

            return ['valid' => true, 'count' => count($decoded), 'reason' => null];
        }

        if ($this->looksLikeSingleEntityRecord($decoded)) {
            return ['valid' => true, 'count' => 1, 'reason' => null];
        }

        return ['valid' => false, 'count' => null, 'reason' => 'tidak memiliki field data/items atau struktur record'];
    }

    /**
     * {@inheritDoc}
     */
    public function verifyCredentials(string $identity, string $password): array
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $url = $baseUrl . '/api/v1/auth/verify-credentials';

        try {
            $client = $this->httpClient()->timeout(15);
            $response = $client->post($url, [
                'identity' => $identity,
                'password' => $password,
            ]);

            $jsonData = $response->json();

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => is_array($jsonData) ? $jsonData : null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => null,
                'message' => (string) $this->sanitizeMessage($e->getMessage()),
            ];
        }
    }
}
