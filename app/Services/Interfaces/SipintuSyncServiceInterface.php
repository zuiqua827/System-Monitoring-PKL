<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;

/**
 * Service for the Super Admin "Sinkronisasi SiPintu" feature.
 *
 * Orchestrates the SiPintu student AND teacher sync and persists the sync
 * history. Student data goes only into the Siswa module; teacher data goes
 * only into the Guru module.
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
     *     sipintu_teacher_count: int,
     *     local_student_count: int,
     *     local_teacher_count: int,
     *     history: \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\SiPintuSyncLog>
     * }
     */
    public function getDashboardData(): array;

/**
     * Run a READ-ONLY preview / dry-run of the sync.
     *
     * Classifies remote students and teachers into 7 categories WITHOUT any
     * INSERT/UPDATE/DELETE. Safe to call any time.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     duration_ms: int,
     *     students: array{
     *         baru: int, diperbarui: int, tidak_berubah: int, konflik: int,
     *         perlu_pemetaan: int, tidak_ditemukan: int, error: int, total_remote: int
     *     },
     *     teachers: array{
     *         baru: int, diperbarui: int, tidak_berubah: int, konflik: int,
     *         perlu_pemetaan: int, tidak_ditemukan: int, error: int, total_remote: int
     *     }
     * }
     */
    public function preview(): array;

    /**
     * Run a manual synchronization triggered by an admin.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     stats: array{
     *         students: array{
     *             created: int, updated: int, deleted: int, skipped: int,
     *             unchanged: int, conflicts: int, needs_mapping: int, errors: int
     *         },
     *         teachers: array{
     *             created: int, updated: int, deleted: int, skipped: int,
     *             unchanged: int, conflicts: int, needs_mapping: int, errors: int
     *         }
     *     }
     * }
     */
    public function runSync(User $admin): array;
}
