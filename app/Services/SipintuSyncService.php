<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SiPintuApiException;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\SipintuClassroomMapping;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for the Super Admin "Sinkronisasi SiPintu" feature.
 *
 * Provides:
 *  - getDashboardData(): non-blocking metrics from local DB & cached remote probes.
 *  - preview(): READ-ONLY classification without any database mutations.
 *  - runSync(): atomic per-record synchronization with full audit trail logging.
 */
class SipintuSyncService extends Service implements SipintuSyncServiceInterface
{
    public function __construct(
        private readonly SiPintuServiceInterface $siPintuService,
        private readonly SipintuSyncLogRepositoryInterface $syncLogRepository,
    ) {}

    public function getDashboardData(): array
    {
        /** @var SiPintuSyncLog|null $lastLog */
        $lastLog = $this->syncLogRepository->latestLog();

        $localStudents = Siswa::query()->withoutTrashed()->count();
        $localTeachers = Guru::query()->withoutTrashed()->count();
        $localDudis = Dudi::query()->withoutTrashed()->count();
        $classroomMappingCount = SipintuClassroomMapping::query()->count();

        $rawCachedCounts = Cache::get('sipintu_dashboard_remote_counts', []);
        $cachedCounts = is_array($rawCachedCounts) ? $rawCachedCounts : [];

        $sipintuStudentCount = isset($cachedCounts['students']) && is_numeric($cachedCounts['students'])
            ? (int) $cachedCounts['students']
            : ($lastLog !== null && $lastLog->remote_total > 0 ? $lastLog->remote_total : null);

        $sipintuTeacherCount = isset($cachedCounts['teachers']) && is_numeric($cachedCounts['teachers'])
            ? (int) $cachedCounts['teachers']
            : null;

        $sipintuDudiCount = isset($cachedCounts['dudis']) && is_numeric($cachedCounts['dudis'])
            ? (int) $cachedCounts['dudis']
            : null;

        $needsMappingCount = isset($cachedCounts['needs_mapping_students']) && is_numeric($cachedCounts['needs_mapping_students'])
            ? (int) $cachedCounts['needs_mapping_students']
            : ($lastLog !== null ? (int) $lastLog->needs_mapping : 0);

        $apiUrl = config('services.sipintu.api_url');
        $clientId = config('services.sipintu.client_id');
        $clientSecret = config('services.sipintu.client_secret');
        $apiToken = config('services.sipintu.api_token');

        $isConfigured = ! empty($apiUrl) && (! empty($apiToken) || (! empty($clientId) && ! empty($clientSecret)));

        $status = $isConfigured ? 'ready' : 'not_configured';
        $message = $isConfigured
            ? 'Konfigurasi kredensial SiPintu telah terpasang. Klik "Test Connection" untuk memeriksa ketersediaan endpoint live atau "Preview" untuk pratinjau data.'
            : 'Konfigurasi SiPintu belum lengkap. Silakan atur SIPINTU_API_URL, SIPINTU_CLIENT_ID, dan SIPINTU_CLIENT_SECRET di file .env.';

        return [
            'connection_status' => $status,
            'connection_message' => $message,
            'connection_detail' => null,
            'connection_troubleshooting' => $isConfigured ? null : 'Buka file .env dan atur kredensial Gateway SiPintu.',
            'connection_success' => $isConfigured,
            'connection_http_status' => null,
            'connection_error_type' => $isConfigured ? null : 'configuration',
            'last_sync' => $lastLog ? $this->serializeLog($lastLog) : null,
            'sipintu_student_count' => $sipintuStudentCount,
            'sipintu_teacher_count' => $sipintuTeacherCount,
            'sipintu_dudi_count' => $sipintuDudiCount,
            'local_student_count' => $localStudents,
            'local_teacher_count' => $localTeachers,
            'local_dudi_count' => $localDudis,
            'classroom_mapping_count' => $classroomMappingCount,
            'needs_mapping_count' => $needsMappingCount,
            'history' => $this->syncLogRepository->paginateHistory(15),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * READ-ONLY preview / dry-run. Fetches remote data and classifies each
     * record without any INSERT/UPDATE/DELETE.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     duration_ms: int,
     *     students: array<string, int|string>,
     *     teachers: array<string, int|string>,
     *     dudis?: array<string, int|string>
     * }
     */
    public function preview(): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $start = hrtime(true);

        try {
            $studentPreview = $this->siPintuService->previewStudents();
            $teacherPreview = $this->siPintuService->previewTeachers();

            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $messages = [];
            $studentTotal = (int) ($studentPreview['total_remote'] ?? 0);
            if ($studentTotal > 0) {
                $messages[] = 'Pratinjau data Siswa berhasil ('.number_format($studentTotal).' siswa terdeteksi di SiPintu).';
            } else {
                $messages[] = 'Pratinjau data Siswa selesai.';
            }

            $teacherTotal = (int) ($teacherPreview['total_remote'] ?? 0);
            if (! empty($teacherPreview['endpoint_unavailable'])) {
                $messages[] = (string) ($teacherPreview['endpoint_message'] ?? 'Endpoint Guru belum tersedia (HTTP 404).');
            } elseif ($teacherTotal > 0) {
                $messages[] = 'Pratinjau data Guru berhasil ('.number_format($teacherTotal).' guru terdeteksi di SiPintu).';
            }

            $dudiPreview = $this->siPintuService->previewDudi();
            $dudiTotal = (int) ($dudiPreview['total_remote'] ?? 0);
            if (! empty($dudiPreview['endpoint_unavailable'])) {
                $messages[] = (string) ($dudiPreview['endpoint_message'] ?? 'Endpoint DUDI belum tersedia (HTTP 404).');
            } elseif ($dudiTotal > 0) {
                $messages[] = 'Pratinjau data DUDI berhasil ('.number_format($dudiTotal).' DUDI terdeteksi di SiPintu).';
            }

            Cache::put('sipintu_dashboard_remote_counts', [
                'students' => $studentTotal,
                'teachers' => empty($teacherPreview['endpoint_unavailable']) ? $teacherTotal : null,
                'dudis' => empty($dudiPreview['endpoint_unavailable']) ? $dudiTotal : null,
                'needs_mapping_students' => (int) ($studentPreview['perlu_pemetaan'] ?? 0),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ], now()->addHours(6));

            return [
                'success' => true,
                'message' => implode(' ', $messages),
                'duration_ms' => $durationMs,
                'students' => $studentPreview,
                'teachers' => $teacherPreview,
                'dudis' => $dudiPreview,
            ];
        } catch (SiPintuApiException $e) {
            Log::warning('SiPintu preview failed', [
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'duration_ms' => 0,
                'students' => $this->emptyPreview(),
                'teachers' => $this->emptyPreview(),
                'dudis' => $this->emptyPreview(),
            ];
        } catch (\Throwable $e) {
            Log::error('SiPintu preview failed unexpectedly', [
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Preview SiPintu gagal diproses. Data lokal tidak diubah.',
                'duration_ms' => 0,
                'students' => $this->emptyPreview(),
                'teachers' => $this->emptyPreview(),
                'dudis' => $this->emptyPreview(),
            ];
        }
    }

    public function runSync(User $admin, string $entityScope = 'all'): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $start = hrtime(true);
        $startedAt = Carbon::now();

        Log::info('SiPintu sync started', ['admin_id' => $admin->id, 'entity_scope' => $entityScope]);

        try {
            // Network and response validation happen before database writes
            // so a slow/failed API can never hold local locks.
            $payload = $this->siPintuService->fetchSyncPayload();

            $studentStats = $this->siPintuService->syncStudents($payload['students']);
            $teacherStats = $this->siPintuService->syncTeachers($payload['teachers']);
            $dudiStats = $this->siPintuService->syncDudi($payload['dudis'] ?? null);

            $finishedAt = Carbon::now();
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);
            $message = $this->buildSummaryMessage($studentStats, $teacherStats, $dudiStats);

            $hasErrors = ($studentStats['errors'] ?? 0) > 0
                || ($teacherStats['errors'] ?? 0) > 0
                || ($dudiStats['errors'] ?? 0) > 0;
            $unmappedStudents = (int) ($studentStats['needs_mapping'] ?? 0);
            $hasUnmappedStudents = $unmappedStudents > 0;

            $syncStatus = ($hasErrors || $hasUnmappedStudents) ? 'partial' : 'success';

            $errorDetails = $this->collectRunIssues($studentStats, $teacherStats, $dudiStats);

            $this->syncLogRepository->create($this->syncLogAttributes($admin, $syncStatus, [
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'entity_scope' => $entityScope,
                'students' => $studentStats,
                'teachers' => $teacherStats,
                'dudis' => $dudiStats,
                'duration_ms' => $durationMs,
                'message' => $message,
                'error_details' => $errorDetails,
            ]));

            $result = [
                'success' => true,
                'status' => $syncStatus,
                'warning' => $hasErrors || $hasUnmappedStudents,
                'message' => $message,
                'stats' => [
                    'students' => $studentStats,
                    'teachers' => $teacherStats,
                    'dudis' => $dudiStats,
                ],
            ];

            Cache::put('sipintu_dashboard_remote_counts', [
                'students' => (int) ($studentStats['received'] ?? count($payload['students'])),
                'teachers' => empty($teacherStats['endpoint_unavailable'])
                    ? (int) ($teacherStats['received'] ?? count($payload['teachers']))
                    : null,
                'dudis' => empty($dudiStats['endpoint_unavailable'])
                    ? (int) ($dudiStats['received'] ?? count($payload['dudis'] ?? []))
                    : null,
                'needs_mapping_students' => $unmappedStudents,
                'updated_at' => $finishedAt->toDateTimeString(),
            ], now()->addHours(6));

            Log::info('SiPintu sync completed', [
                'admin_id' => $admin->id,
                'status' => $syncStatus,
                'student_stats' => $result['stats']['students'],
                'teacher_stats' => $result['stats']['teachers'],
            ]);

            return $result;
        } catch (SiPintuApiException $e) {
            return $this->failedSyncResult($admin, $start, $startedAt, $entityScope, $e->getMessage(), $e);
        } catch (QueryException $e) {
            return $this->failedSyncResult(
                $admin,
                $start,
                $startedAt,
                $entityScope,
                'Sinkronisasi gagal disimpan. Semua perubahan data dibatalkan.',
                $e,
            );
        } catch (\Throwable $e) {
            return $this->failedSyncResult(
                $admin,
                $start,
                $startedAt,
                $entityScope,
                'Sinkronisasi gagal diproses. Semua perubahan data dibatalkan.',
                $e,
            );
        }
    }

    public function testConnection(): array
    {
        $result = $this->siPintuService->testConnection();

        if ($result['success']) {
            try {
                $studentStatus = $this->siPintuService->checkEndpointStatus('/api/v1/sijuna/students');
                $teacherStatus = $this->siPintuService->checkEndpointStatus('/api/v1/sijuna/teachers');
                $dudiStatus = $this->siPintuService->checkEndpointStatus('/api/v1/sijuna/dudi');

                $result['endpoints'] = [
                    'gateway' => [
                        'name' => 'Gateway SiPintu (/api/v1/health)',
                        'status' => $result['http_status'] === 200,
                        'http_status' => $result['http_status'],
                        'message' => $result['message'],
                    ],
                    'profile' => [
                        'name' => 'Endpoint Profil Pengguna (/api/v1/user)',
                        'status' => true,
                        'http_status' => null,
                        'message' => 'Endpoint OAuth SSO aktif untuk autentikasi login pengguna.',
                    ],
                    'students' => [
                        'name' => 'Endpoint Data Siswa (/api/v1/sijuna/students)',
                        'status' => $studentStatus['success'],
                        'http_status' => $studentStatus['status'],
                        'message' => $studentStatus['message'],
                        'count' => $studentStatus['count'] ?? null,
                    ],
                    'teachers' => [
                        'name' => 'Endpoint Data Guru (/api/v1/sijuna/teachers)',
                        'status' => $teacherStatus['success'],
                        'http_status' => $teacherStatus['status'],
                        'message' => $teacherStatus['message'],
                        'count' => $teacherStatus['count'] ?? null,
                    ],
                    'dudi' => [
                        'name' => 'Endpoint Data DUDI (/api/v1/sijuna/dudi)',
                        'status' => $dudiStatus['success'],
                        'http_status' => $dudiStatus['status'],
                        'message' => $dudiStatus['message'],
                        'count' => $dudiStatus['count'] ?? null,
                    ],
                ];

                $existingCounts = Cache::get('sipintu_dashboard_remote_counts', []);
                $existingCounts = is_array($existingCounts) ? $existingCounts : [];

                Cache::put('sipintu_dashboard_remote_counts', array_merge($existingCounts, [
                    'students' => $studentStatus['success'] && ($studentStatus['count'] ?? null) !== null
                        ? (int) $studentStatus['count']
                        : ($existingCounts['students'] ?? null),
                    'teachers' => $teacherStatus['success'] ? ($teacherStatus['count'] ?? 0) : null,
                    'dudis' => $dudiStatus['success'] ? ($dudiStatus['count'] ?? 0) : null,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]), now()->addHours(6));

                if (! $teacherStatus['success'] || ! $dudiStatus['success']) {
                    $result['endpoint_warning'] = true;
                    $missing = [];
                    if (! $teacherStatus['success']) {
                        $missing[] = 'Guru (HTTP '.($teacherStatus['status'] ?? 404).')';
                    }
                    if (! $dudiStatus['success']) {
                        $missing[] = 'DUDI (HTTP '.($dudiStatus['status'] ?? 404).')';
                    }

                    $result['message'] = 'Koneksi ke Gateway SiPintu berhasil (Sebagian Tersedia). Layanan Siswa aktif, layanan '.implode(' dan ', $missing).' belum tersedia.';
                    $result['detail'] = 'Endpoint Siswa tersedia (HTTP 200). Endpoint '.implode(' dan ', $missing).' belum tersedia di server SiPintu.';
                    $result['troubleshooting'] = 'Sinkronisasi data Siswa dapat dijalankan. Untuk data Guru dan DUDI, pengelola server SiPintu perlu mendaftarkan route terkait pada gateway SiPintu.';
                }

                Cache::put('sipintu_live_diagnostics', $result, now()->addMinutes(15));
            } catch (\Throwable) {
                // Retain base connection result if secondary probes fail
            }
        } else {
            Cache::put('sipintu_live_diagnostics', $result, now()->addMinutes(15));
        }

        return $result;
    }

    /**
     * Collect structured failure/warning details for audit trail inspection.
     *
     * @param  array<string, int>  $studentStats
     * @param  array<string, int>  $teacherStats
     * @param  array<string, int>  $dudiStats
     * @return list<array<string, mixed>>|null
     */
    private function collectRunIssues(array $studentStats, array $teacherStats, array $dudiStats): ?array
    {
        $issues = [];

        if (($studentStats['needs_mapping'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'needs_mapping', 'count' => (int) $studentStats['needs_mapping'], 'detail' => 'Siswa dilewati karena classroom_id belum memiliki pemetaan kelas lokal yang valid.'];
        }
        if (($studentStats['conflicts'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'conflict', 'count' => (int) $studentStats['conflicts'], 'detail' => 'Benturan NIS/NISN, email terpakai akun lain, atau akun terlindungi (Super Admin/DUDI).'];
        }
        if (($studentStats['duplicates'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'duplicate', 'count' => (int) $studentStats['duplicates'], 'detail' => 'NIS duplikat di dalam payload API SiPintu dilewati.'];
        }
        if (($studentStats['errors'] ?? 0) > 0) {
            $issues[] = ['entity' => 'students', 'type' => 'error', 'count' => (int) $studentStats['errors'], 'detail' => 'Payload siswa tidak valid atau gagal disimpan dalam transaksi database.'];
        }
        if (! empty($teacherStats['endpoint_unavailable'])) {
            $issues[] = ['entity' => 'teachers', 'type' => 'endpoint_unavailable', 'http_status' => 404, 'detail' => 'Endpoint Guru (/api/v1/sijuna/teachers) belum tersedia (HTTP 404).'];
        }
        if (! empty($dudiStats['endpoint_unavailable'])) {
            $issues[] = ['entity' => 'dudis', 'type' => 'endpoint_unavailable', 'http_status' => 404, 'detail' => 'Endpoint Master DUDI belum tersedia (HTTP 404).'];
        }

        return $issues === [] ? null : $issues;
    }

    /**
     * @return array{
     *     success: false,
     *     status: string,
     *     warning: bool,
     *     message: string,
     *     stats: array{
     *         students: array<string, int>,
     *         teachers: array<string, int>,
     *         dudis?: array<string, int>
     *     }
     * }
     */
    private function failedSyncResult(
        User $admin,
        int $start,
        Carbon $startedAt,
        string $entityScope,
        string $message,
        \Throwable $exception,
    ): array {
        $finishedAt = Carbon::now();
        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

        Log::error('SiPintu sync failed', [
            'admin_id' => $admin->id,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
        ]);

        try {
            $this->syncLogRepository->create($this->syncLogAttributes($admin, 'failed', [
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'entity_scope' => $entityScope,
                'students' => $this->emptySyncStats(),
                'teachers' => $this->emptySyncStats(),
                'dudis' => $this->emptySyncStats(),
                'duration_ms' => $durationMs,
                'message' => $message,
                'error_details' => [[
                    'entity' => $entityScope,
                    'type' => 'exception',
                    'exception_class' => $exception::class,
                    'detail' => $exception->getMessage(),
                ]],
            ]));
        } catch (QueryException $logException) {
            Log::error('SiPintu failed sync history could not be recorded', [
                'admin_id' => $admin->id,
                'exception_class' => $logException::class,
                'message' => $logException->getMessage(),
            ]);
        }

        return [
            'success' => false,
            'status' => 'failed',
            'warning' => false,
            'message' => $message,
            'stats' => [
                'students' => $this->emptySyncStats(),
                'teachers' => $this->emptySyncStats(),
                'dudis' => $this->emptySyncStats(),
            ],
        ];
    }

    /**
     * @param  'success'|'failed'|'partial'  $status
     * @param  array{
     *     started_at?: Carbon,
     *     finished_at?: Carbon,
     *     entity_scope?: string,
     *     students: array<string, int>,
     *     teachers: array<string, int>,
     *     dudis?: array<string, int>,
     *     duration_ms: int,
     *     message: string,
     *     error_details?: list<array<string, mixed>>|null
     * }  $context
     * @return array<string, mixed>
     */
    private function syncLogAttributes(User $admin, string $status, array $context): array
    {
        $studentStats = $context['students'];
        $teacherStats = $context['teachers'];
        $dudiStats = $context['dudis'] ?? [];

        $studentReceived = (int) ($studentStats['received'] ?? (
            ($studentStats['created'] ?? 0)
            + ($studentStats['updated'] ?? 0)
            + ($studentStats['unchanged'] ?? 0)
            + ($studentStats['skipped'] ?? 0)
        ));

        $remoteTotal = $studentReceived
            + (int) ($teacherStats['received'] ?? 0)
            + (int) ($dudiStats['received'] ?? 0);

        return [
            'user_id' => $admin->id,
            'admin_name' => $admin->name,
            'started_at' => $context['started_at'] ?? null,
            'finished_at' => $context['finished_at'] ?? null,
            'status' => $status,
            'entity_scope' => $context['entity_scope'] ?? 'all',
            'remote_total' => $remoteTotal,
            'added' => (int) ($studentStats['created'] ?? 0),
            'updated' => (int) ($studentStats['updated'] ?? 0),
            'deleted' => (int) ($studentStats['deleted'] ?? 0),
            'skipped' => (int) ($studentStats['skipped'] ?? 0),
            'unchanged' => (int) ($studentStats['unchanged'] ?? 0) + (int) ($teacherStats['unchanged'] ?? 0) + (int) ($dudiStats['unchanged'] ?? 0),
            'needs_mapping' => (int) ($studentStats['needs_mapping'] ?? 0),
            'conflicts' => (int) ($studentStats['conflicts'] ?? 0) + (int) ($teacherStats['conflicts'] ?? 0) + (int) ($dudiStats['conflicts'] ?? 0),
            'duplicates' => (int) ($studentStats['duplicates'] ?? 0) + (int) ($teacherStats['duplicates'] ?? 0) + (int) ($dudiStats['duplicates'] ?? 0),
            'invalid' => (int) ($studentStats['invalid'] ?? 0) + (int) ($teacherStats['invalid'] ?? 0) + (int) ($dudiStats['invalid'] ?? 0),
            'failed' => (int) ($studentStats['failed'] ?? 0) + (int) ($teacherStats['failed'] ?? 0) + (int) ($dudiStats['failed'] ?? 0),
            'teacher_added' => (int) ($teacherStats['created'] ?? 0),
            'teacher_updated' => (int) ($teacherStats['updated'] ?? 0),
            'teacher_deleted' => (int) ($teacherStats['deleted'] ?? 0),
            'teacher_skipped' => (int) ($teacherStats['skipped'] ?? 0),
            'dudi_added' => (int) ($dudiStats['created'] ?? 0),
            'dudi_updated' => (int) ($dudiStats['updated'] ?? 0),
            'dudi_deleted' => (int) ($dudiStats['deleted'] ?? 0),
            'dudi_skipped' => (int) ($dudiStats['skipped'] ?? 0),
            'duration_ms' => $context['duration_ms'],
            'summary' => [
                'students' => $studentStats,
                'teachers' => $teacherStats,
                'dudis' => $dudiStats,
            ],
            'error_details' => $context['error_details'] ?? null,
            'message' => $context['message'],
        ];
    }

    /**
     * Build a human-readable summary from sync stats.
     *
     * @param  array<string, int>  $studentStats
     * @param  array<string, int>  $teacherStats
     * @param  array<string, int>  $dudiStats
     */
    private function buildSummaryMessage(array $studentStats, array $teacherStats, array $dudiStats = []): string
    {
        $students = $this->summaryStats($studentStats);
        $teachers = $this->summaryStats($teacherStats);
        $dudis = $this->summaryStats($dudiStats);

        $processed = array_sum($students) + array_sum($teachers) + array_sum($dudis);

        $studentExtra = [];
        if ($students['duplicates'] > 0) {
            $studentExtra[] = $students['duplicates'].' duplikat';
        }
        if ($students['invalid'] > 0) {
            $studentExtra[] = $students['invalid'].' tidak valid';
        }
        if ($students['failed'] > 0) {
            $studentExtra[] = $students['failed'].' gagal';
        }

        $studentMsg = 'Siswa: '.$students['created'].' baru, '.$students['updated'].' diperbarui, '
            .$students['unchanged'].' tidak berubah, '.$students['skipped'].' dilewati '
            .'('.$students['needs_mapping'].' perlu pemetaan, '.$students['conflicts'].' konflik), '
            .$students['errors'].' error.'
            .($studentExtra !== [] ? ' Rincian lain: '.implode(', ', $studentExtra).'.' : '');

        if ($students['needs_mapping'] > 0 && ($students['created'] + $students['updated'] + $students['unchanged']) === 0) {
            $studentMsg .= ' Perhatian: Siswa SiPintu belum tersimpan ke database SIMONGAN karena memerlukan pemetaan kelas ('.$students['needs_mapping'].' siswa perlu pemetaan). Silakan lakukan pemetaan di menu Pemetaan Kelas SiPintu lalu sinkronkan ulang.';
        }

        if (! empty($teacherStats['endpoint_unavailable'])) {
            $teacherMsg = 'Guru: Endpoint /api/v1/sijuna/teachers belum tersedia pada server SiPintu (HTTP 404). Data guru lokal aman.';
        } elseif (array_sum($teachers) === 0) {
            $teacherMsg = 'Guru: 0 diproses.';
        } else {
            $teacherMsg = 'Guru: '.$teachers['created'].' baru, '
                .$teachers['updated'].' diperbarui, '.$teachers['unchanged'].' tidak berubah, '
                .$teachers['skipped'].' dilewati ('.$teachers['conflicts'].' konflik), '
                .$teachers['errors'].' error.';
        }

        if (! empty($dudiStats['endpoint_unavailable'])) {
            $dudiMsg = 'DUDI: Endpoint /api/v1/sijuna/dudi belum tersedia pada server SiPintu (HTTP 404). Data DUDI lokal aman.';
        } elseif (array_sum($dudis) === 0) {
            $dudiMsg = 'DUDI: 0 diproses.';
        } else {
            $dudiMsg = 'DUDI: '.$dudis['created'].' baru, '
                .$dudis['updated'].' diperbarui, '.$dudis['unchanged'].' tidak berubah, '
                .$dudis['skipped'].' dilewati ('.$dudis['conflicts'].' konflik), '
                .$dudis['errors'].' error.';
        }

        if ($processed === 0 && empty($teacherStats['endpoint_unavailable']) && empty($dudiStats['endpoint_unavailable'])) {
            return 'Sinkronisasi selesai. Tidak ada data baru yang ditemukan. Ditemukan: 0, ditambahkan: 0, diperbarui: 0, dilewati: 0, gagal: 0.';
        }

        return $studentMsg.' '.$teacherMsg.' '.$dudiMsg;
    }

    /**
     * @param  array<string, int>  $stats
     * @return array{created: int, updated: int, skipped: int, unchanged: int, duplicates: int, conflicts: int, needs_mapping: int, invalid: int, failed: int, errors: int}
     */
    private function summaryStats(array $stats): array
    {
        return [
            'created' => (int) ($stats['created'] ?? 0),
            'updated' => (int) ($stats['updated'] ?? 0),
            'skipped' => (int) ($stats['skipped'] ?? 0),
            'unchanged' => (int) ($stats['unchanged'] ?? 0),
            'duplicates' => (int) ($stats['duplicates'] ?? 0),
            'conflicts' => (int) ($stats['conflicts'] ?? 0),
            'needs_mapping' => (int) ($stats['needs_mapping'] ?? 0),
            'invalid' => (int) ($stats['invalid'] ?? 0),
            'failed' => (int) ($stats['failed'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeLog(SiPintuSyncLog $log): array
    {
        return [
            'id' => $log->id,
            'admin_name' => $log->admin_name,
            'started_at' => $log->started_at?->toDateTimeString(),
            'finished_at' => $log->finished_at?->toDateTimeString(),
            'status' => $log->status,
            'entity_scope' => $log->entity_scope,
            'remote_total' => $log->remote_total,
            'added' => $log->added,
            'updated' => $log->updated,
            'deleted' => $log->deleted,
            'skipped' => $log->skipped,
            'unchanged' => $log->unchanged,
            'needs_mapping' => $log->needs_mapping,
            'conflicts' => $log->conflicts,
            'duplicates' => $log->duplicates,
            'invalid' => $log->invalid,
            'failed' => $log->failed,
            'teacher_added' => $log->teacher_added,
            'teacher_updated' => $log->teacher_updated,
            'teacher_deleted' => $log->teacher_deleted,
            'teacher_skipped' => $log->teacher_skipped,
            'duration_ms' => $log->duration_ms,
            'summary' => $log->summary,
            'error_details' => $log->error_details,
            'message' => $log->message,
            'ran_at' => $log->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyPreview(): array
    {
        return [
            'baru' => 0,
            'diperbarui' => 0,
            'tidak_berubah' => 0,
            'perlu_pemetaan' => 0,
            'duplikat' => 0,
            'konflik' => 0,
            'tidak_valid' => 0,
            'gagal' => 0,
            'tidak_ditemukan' => 0,
            'error' => 0,
            'total_remote' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptySyncStats(): array
    {
        return [
            'received' => 0,
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'duplicates' => 0,
            'conflicts' => 0,
            'needs_mapping' => 0,
            'invalid' => 0,
            'failed' => 0,
            'errors' => 0,
        ];
    }
}
