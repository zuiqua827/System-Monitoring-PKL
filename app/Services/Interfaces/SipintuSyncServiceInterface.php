<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;

/**
 * Service for the Super Admin "Sinkronisasi SiPintu" feature.
 *
 * Orchestrates the SiPintu student sync and persists the sync history.
 * Only student data is synchronized (never Guru/DUDI/Admin/PKL modules).
 */
interface SipintuSyncServiceInterface
{
    /**
     * Get the data needed to render the sync dashboard page.
     *
     * @return array{
     *     connection_status: string,
     *     connection_message: string,
     *     last_sync: array<string, mixed>|null,
     *     sipintu_student_count: int,
     *     local_student_count: int,
     *     history: \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\SiPintuSyncLog>
     * }
     */
    public function getDashboardData(): array;

    /**
     * Run a manual synchronization triggered by an admin.
     *
     * @return array{success: bool, message: string, stats: array{added: int, updated: int, deleted: int, skipped: int}}
     */
    public function runSync(User $admin): array;
}
