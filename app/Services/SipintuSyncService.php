<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SiPintuApiException;
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
 * Reuses the existing SiPintuService (which only touches student data)
 * and persists a synchronization history record for each run.
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

        // Single, cached HTTP fetch to SiPintu. Reused for both the
        // connection status badge and the remote student count. This avoids
        // two separate (slow/blocking) API round-trips on every page load.
        $remote = $this->fetchSiPintuData();

        return [
            'connection_status' => $remote['status'],
            'connection_message' => $remote['message'],
            'last_sync' => $lastLog ? $this->serializeLog($lastLog) : null,
            'sipintu_student_count' => $remote['count'],
            'local_student_count' => $localStudents,
            'history' => $this->syncLogRepository->paginateHistory(15),
        ];
    }

    public function runSync(User $admin): array
    {
        $start = hrtime(true);

        try {
            $stats = $this->siPintuService->syncStudents();

            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $this->syncLogRepository->create([
                'user_id' => $admin->id,
                'admin_name' => $admin->name,
                'status' => 'success',
                'added' => $stats['created'],
                'updated' => $stats['updated'],
                'deleted' => $stats['deleted'],
                'skipped' => $stats['skipped'],
                'duration_ms' => $durationMs,
                'message' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Sinkronisasi data siswa berhasil.',
                'stats' => $stats,
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
                'duration_ms' => $durationMs,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => ['added' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 0],
            ];
        }
    }

    /**
     * Fetch SiPintu student data once (cached for 5 minutes) and derive
     * the connection status + remote count from that single result.
     *
     * @return array{status: string, message: string, count: int}
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
                'count' => 0,
            ];
        }

        return Cache::remember('sipintu_remote_data', now()->addMinutes(5), function () {
            try {
                $students = $this->siPintuService->fetchStudents();

                return [
                    'status' => 'connected',
                    'message' => 'Terhubung ke gateway SiPintu.',
                    'count' => count($students),
                ];
            } catch (\Throwable) {
                return [
                    'status' => 'error',
                    'message' => 'Gagal terhubung ke gateway SiPintu.',
                    'count' => 0,
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
            'duration_ms' => $log->duration_ms,
            'message' => $log->message,
            'ran_at' => $log->created_at?->toDateTimeString(),
        ];
    }
}
