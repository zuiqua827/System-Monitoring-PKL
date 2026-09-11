<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SiPintuApiException;
use App\Models\Guru;
use App\Models\SipintuClassroomMapping;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for the Super Admin "Sinkronisasi SiPintu" feature.
 *
 * Provides:
 *  - getDashboardData(): metrics for the dashboard.
 *  - preview(): READ-ONLY classification of the upcoming sync (dry run).
 *  - runSync(): actually applies the sync (per-record transaction, no
 *    automatic deletion, no default-kelas fallback).
 *  - Persists a synchronization history record for each run.
 *
 * Student data → Siswa module. Teacher data → Guru module.
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
        $classroomMappingCount = SipintuClassroomMapping::query()->count();

        $apiUrl = config('services.sipintu.api_url');
        $clientId = config('services.sipintu.client_id');
        $clientSecret = config('services.sipintu.client_secret');
        $apiToken = config('services.sipintu.api_token');

        $isConfigured = ! empty($apiUrl) && (! empty($apiToken) || (! empty($clientId) && ! empty($clientSecret)));

        $status = $isConfigured ? 'ready' : 'not_configured';
        $message = $isConfigured
            ? 'Sistem SiPintu siap digunakan. Klik "Test Connection" untuk menguji koneksi live atau "Mulai Sinkronisasi" untuk menyinkronkan data.'
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
            'sipintu_student_count' => null,
            'sipintu_teacher_count' => null,
            'local_student_count' => $localStudents,
            'local_teacher_count' => $localTeachers,
            'classroom_mapping_count' => $classroomMappingCount,
            'history' => $this->syncLogRepository->paginateHistory(15),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * READ-ONLY preview / dry-run. Fetches remote data and classifies each
     * record into the 7 categories WITHOUT any INSERT/UPDATE/DELETE.
     */
    public function preview(): array
    {
        $start = hrtime(true);

        try {
            $studentPreview = $this->siPintuService->previewStudents();
            $teacherPreview = $this->siPintuService->previewTeachers();

            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            return [
                'success' => true,
                'message' => 'Pratinjau (Preview/Dry Run) berhasil. Data TIDAK diubah.',
                'duration_ms' => $durationMs,
                'students' => $studentPreview,
                'teachers' => $teacherPreview,
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
            ];
        }
    }

    public function runSync(User $admin): array
    {
        $start = hrtime(true);

        Log::info('SiPintu sync started', ['admin_id' => $admin->id]);

        try {
            // Network and response validation happen before the database
            // transaction so a slow/failed API can never hold local locks.
            $payload = $this->siPintuService->fetchSyncPayload();

            $studentStats = $this->siPintuService->syncStudents($payload['students']);
            $teacherStats = $this->siPintuService->syncTeachers($payload['teachers']);
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);
            $message = $this->buildSummaryMessage($studentStats, $teacherStats);

            $this->syncLogRepository->create($this->syncLogAttributes($admin, 'success', [
                'students' => $studentStats,
                'teachers' => $teacherStats,
                'duration_ms' => $durationMs,
                'message' => $message,
            ]));

            $result = [
                'success' => true,
                'message' => $message,
                'stats' => [
                    'students' => $studentStats,
                    'teachers' => $teacherStats,
                ],
            ];

            Cache::forget('sipintu_dashboard_remote_counts');
            Log::info('SiPintu sync completed', [
                'admin_id' => $admin->id,
                'student_stats' => $result['stats']['students'],
                'teacher_stats' => $result['stats']['teachers'],
            ]);

            return $result;
        } catch (SiPintuApiException $e) {
            return $this->failedSyncResult($admin, $start, $e->getMessage(), $e);
        } catch (QueryException $e) {
            return $this->failedSyncResult(
                $admin,
                $start,
                'Sinkronisasi gagal disimpan. Semua perubahan data dibatalkan.',
                $e,
            );
        } catch (\Throwable $e) {
            return $this->failedSyncResult(
                $admin,
                $start,
                'Sinkronisasi gagal diproses. Semua perubahan data dibatalkan.',
                $e,
            );
        }
    }

    public function testConnection(): array
    {
        Cache::forget('sipintu_dashboard_remote_counts');

        return $this->siPintuService->testConnection();
    }

    /**
     * @return array{success: false, message: string, stats: array{students: array<string, int>, teachers: array<string, int>}}
     */
    private function failedSyncResult(User $admin, int $start, string $message, \Throwable $exception): array
    {
        $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

        Log::error('SiPintu sync failed', [
            'admin_id' => $admin->id,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
        ]);

        try {
            $this->syncLogRepository->create($this->syncLogAttributes($admin, 'failed', [
                'students' => $this->emptySyncStats(),
                'teachers' => $this->emptySyncStats(),
                'duration_ms' => $durationMs,
                'message' => $message,
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
            'message' => $message,
            'stats' => [
                'students' => $this->emptySyncStats(),
                'teachers' => $this->emptySyncStats(),
            ],
        ];
    }

    /**
     * @param  'success'|'failed'  $status
     * @param  array{students: array<string, int>, teachers: array<string, int>, duration_ms: int, message: string}  $context
     * @return array<string, int|string|null>
     */
    private function syncLogAttributes(User $admin, string $status, array $context): array
    {
        $studentStats = $context['students'];
        $teacherStats = $context['teachers'];

        return [
            'user_id' => $admin->id,
            'admin_name' => $admin->name,
            'status' => $status,
            'added' => (int) ($studentStats['created'] ?? 0),
            'updated' => (int) ($studentStats['updated'] ?? 0),
            'deleted' => (int) ($studentStats['deleted'] ?? 0),
            'skipped' => (int) ($studentStats['skipped'] ?? 0),
            'teacher_added' => (int) ($teacherStats['created'] ?? 0),
            'teacher_updated' => (int) ($teacherStats['updated'] ?? 0),
            'teacher_deleted' => (int) ($teacherStats['deleted'] ?? 0),
            'teacher_skipped' => (int) ($teacherStats['skipped'] ?? 0),
            'duration_ms' => $context['duration_ms'],
            'message' => $context['message'],
        ];
    }

    /**
     * Build a human-readable summary from sync stats.
     *
     * @param  array<string, int>  $studentStats
     * @param  array<string, int>  $teacherStats
     */
    private function buildSummaryMessage(array $studentStats, array $teacherStats): string
    {
        $students = $this->summaryStats($studentStats);
        $teachers = $this->summaryStats($teacherStats);

        $processed = array_sum($students) + array_sum($teachers);

        if ($processed === 0) {
            return 'Sinkronisasi selesai. Tidak ada data baru yang ditemukan. Ditemukan: 0, ditambahkan: 0, diperbarui: 0, dilewati: 0, gagal: 0.';
        }

        return 'Siswa: '.$students['created'].' baru, '.$students['updated'].' diperbarui, '
            .$students['unchanged'].' tidak berubah, '.$students['skipped'].' dilewati '
            .'('.$students['needs_mapping'].' perlu pemetaan, '.$students['conflicts'].' konflik), '
            .$students['errors'].' error. Guru: '.$teachers['created'].' baru, '
            .$teachers['updated'].' diperbarui, '.$teachers['unchanged'].' tidak berubah, '
            .$teachers['skipped'].' dilewati ('.$teachers['conflicts'].' konflik), '
            .$teachers['errors'].' error.';
    }

    /**
     * @param  array<string, int>  $stats
     * @return array{created: int, updated: int, skipped: int, unchanged: int, conflicts: int, needs_mapping: int, errors: int}
     */
    private function summaryStats(array $stats): array
    {
        return [
            'created' => (int) ($stats['created'] ?? 0),
            'updated' => (int) ($stats['updated'] ?? 0),
            'skipped' => (int) ($stats['skipped'] ?? 0),
            'unchanged' => (int) ($stats['unchanged'] ?? 0),
            'conflicts' => (int) ($stats['conflicts'] ?? 0),
            'needs_mapping' => (int) ($stats['needs_mapping'] ?? 0),
            'errors' => (int) ($stats['errors'] ?? 0),
        ];
    }

    /**
     * Test the live connection first, then cache only successful remote counts.
     * A past connection or API failure must never be retained as dashboard state.
     *
     * @return array{success: bool, status: string, connection: bool, http_status: int|null, message: string, error_type: string|null, student_count: int, teacher_count: int}
     */
    private function fetchSiPintuData(): array
    {
        $connection = $this->siPintuService->testConnection();
        if (! $connection['success']) {
            return $this->connectionErrorData($connection);
        }

        try {
            $remote = Cache::remember('sipintu_dashboard_remote_counts', now()->addMinutes(5), function (): array {
                $students = $this->siPintuService->fetchStudents();
                $teachers = $this->siPintuService->fetchTeachers();

                return [
                    'student_count' => count($students),
                    'teacher_count' => count($teachers),
                ];
            });
        } catch (SiPintuApiException $e) {
            logger()->warning('Respons data dashboard SiPintu gagal setelah koneksi berhasil.', [
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->apiResponseErrorData($connection, $e->getMessage());
        } catch (\Throwable $e) {
            logger()->error('Kesalahan tak terduga saat memuat data dashboard SiPintu.', [
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->apiResponseErrorData($connection, 'Respons data SiPintu tidak dapat dimuat.');
        }

        return $this->normalizeDashboardRemoteData($connection, $remote);
    }

    /**
     * @param  array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null}  $connection
     * @return array{success: bool, status: string, connection: bool, http_status: int|null, message: string, error_type: string|null, student_count: int, teacher_count: int}
     */
    private function connectionErrorData(array $connection): array
    {
        return [
            'success' => false,
            'status' => $connection['error_type'] === 'configuration' ? 'not_configured' : 'error',
            'connection' => false,
            'http_status' => $connection['http_status'],
            'message' => $connection['message'],
            'error_type' => $connection['error_type'],
            'detail' => $connection['detail'] ?? null,
            'troubleshooting' => $connection['troubleshooting'] ?? null,
            'student_count' => 0,
            'teacher_count' => 0,
        ];
    }

    /**
     * @param  array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null}  $connection
     * @return array{success: bool, status: string, connection: bool, http_status: int|null, message: string, error_type: string, student_count: int, teacher_count: int}
     */
    private function apiResponseErrorData(array $connection, string $message): array
    {
        return [
            'success' => false,
            'status' => 'connected',
            'connection' => true,
            'http_status' => $connection['http_status'],
            'message' => 'Koneksi berhasil, tetapi respons data SiPintu gagal: '.$message,
            'error_type' => 'response',
            'detail' => $message,
            'troubleshooting' => 'Format data yang diterima tidak sesuai skema API SiPintu.',
            'student_count' => 0,
            'teacher_count' => 0,
        ];
    }

    /**
     * @param  array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null}  $connection
     * @return array{success: bool, status: string, connection: bool, http_status: int|null, message: string, error_type: string|null, student_count: int, teacher_count: int}
     */
    private function normalizeDashboardRemoteData(array $connection, mixed $remote): array
    {
        if (! is_array($remote)
            || ! is_numeric($remote['student_count'] ?? null)
            || ! is_numeric($remote['teacher_count'] ?? null)) {
            logger()->warning('Cache dashboard SiPintu tidak valid; memakai status aman.', [
                'cache_value_type' => get_debug_type($remote),
            ]);

            return $this->apiResponseErrorData($connection, 'Status data SiPintu belum tersedia. Silakan coba lagi.');
        }

        return [
            'success' => true,
            'status' => 'connected',
            'connection' => true,
            'http_status' => $connection['http_status'],
            'message' => 'Autentikasi berhasil dan server SiPintu dapat dijangkau.',
            'error_type' => null,
            'student_count' => max(0, (int) $remote['student_count']),
            'teacher_count' => max(0, (int) $remote['teacher_count']),
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
            'status' => $log->status,
            'added' => $log->added,
            'updated' => $log->updated,
            'deleted' => $log->deleted,
            'skipped' => $log->skipped,
            'teacher_added' => $log->teacher_added,
            'teacher_updated' => $log->teacher_updated,
            'teacher_deleted' => $log->teacher_deleted,
            'teacher_skipped' => $log->teacher_skipped,
            'duration_ms' => $log->duration_ms,
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
            'konflik' => 0,
            'perlu_pemetaan' => 0,
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
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'conflicts' => 0,
            'needs_mapping' => 0,
            'errors' => 0,
        ];
    }
}
