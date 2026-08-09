<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SiPintuApiException;
use App\Models\Guru;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Support\Facades\Cache;

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

        $remote = $this->fetchSiPintuData();

        return [
            'connection_status' => $remote['status'],
            'connection_message' => $remote['message'],
            'last_sync' => $lastLog ? $this->serializeLog($lastLog) : null,
            'sipintu_student_count' => $remote['student_count'],
            'sipintu_teacher_count' => $remote['teacher_count'],
            'local_student_count' => $localStudents,
            'local_teacher_count' => $localTeachers,
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
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'duration_ms' => 0,
                'students' => $this->emptyPreview(),
                'teachers' => $this->emptyPreview(),
            ];
        }
    }

    public function runSync(User $admin): array
    {
        $start = hrtime(true);

        try {
            $studentStats = $this->siPintuService->syncStudents();
            $teacherStats = $this->siPintuService->syncTeachers();

            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $message = $this->buildSummaryMessage($studentStats, $teacherStats);

            $this->syncLogRepository->create([
                'user_id' => $admin->id,
                'admin_name' => $admin->name,
                'status' => 'success',
                'added' => $studentStats['created'],
                'updated' => $studentStats['updated'],
                'deleted' => $studentStats['deleted'],
                'skipped' => $studentStats['skipped'],
                'teacher_added' => $teacherStats['created'],
                'teacher_updated' => $teacherStats['updated'],
                'teacher_deleted' => $teacherStats['deleted'],
                'teacher_skipped' => $teacherStats['skipped'],
                'duration_ms' => $durationMs,
                'message' => $message,
            ]);

            return [
                'success' => true,
                'message' => $message,
                'stats' => [
                    'students' => $studentStats,
                    'teachers' => $teacherStats,
                ],
            ];
        } catch (SiPintuApiException $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $this->syncLogRepository->create([
                'user_id' => $admin->id,
                'admin_name' => $admin->name,
                'status' => 'failed',
                'added' => 0,
                'updated' => 0,
                'deleted' => 0,
                'skipped' => 0,
                'teacher_added' => 0,
                'teacher_updated' => 0,
                'teacher_deleted' => 0,
                'teacher_skipped' => 0,
                'duration_ms' => $durationMs,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => [
                    'students' => $this->emptySyncStats(),
                    'teachers' => $this->emptySyncStats(),
                ],
            ];
        }
    }

    /**
     * Build a human-readable summary from sync stats.
     *
     * @param  array<string, int>  $studentStats
     * @param  array<string, int>  $teacherStats
     */
    private function buildSummaryMessage(array $studentStats, array $teacherStats): string
    {
        $s = $studentStats;
        $t = $teacherStats;

        return sprintf(
            'Siswa: %d baru, %d diperbarui, %d tidak berubah, %d konflik, %d perlu pemetaan, %d tidak ditemukan, %d error. '
            .'Guru: %d baru, %d diperbarui, %d tidak berubah, %d error.',
            $s['created'],
            $s['updated'],
            $s['unchanged'],
            $s['conflicts'],
            $s['needs_mapping'],
            $s['errors'],
            $t['created'],
            $t['updated'],
            $t['unchanged'],
            $t['errors'],
        );
    }

    /**
     * Fetch SiPintu student + teacher data once (cached for 5 minutes) and
     * derive the connection status + remote counts from those results.
     *
     * @return array{status: string, message: string, student_count: int, teacher_count: int}
     */
    private function fetchSiPintuData(): array
    {
        $baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
        $clientId = (string) config('services.sipintu.client_id');
        $clientSecret = (string) config('services.sipintu.client_secret');

        if ($baseUrl === '' || $clientId === '' || $clientSecret === '') {
            return [
                'status' => 'not_configured',
                'message' => 'Kredensial SiPintu belum dikonfigurasi.',
                'student_count' => 0,
                'teacher_count' => 0,
            ];
        }

        return Cache::remember('sipintu_remote_data', now()->addMinutes(5), function () {
            try {
                $students = $this->siPintuService->fetchStudents();
                $teachers = $this->siPintuService->fetchTeachers();

                return [
                    'status' => 'connected',
                    'message' => 'Terhubung ke gateway SiPintu.',
                    'student_count' => count($students),
                    'teacher_count' => count($teachers),
                ];
            } catch (\Throwable) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal terhubung ke gateway SiPintu.',
                    'student_count' => 0,
                    'teacher_count' => 0,
                ];
            }
        });
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
