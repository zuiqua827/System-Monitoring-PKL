<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;

/**
 * Service for the Super Admin "Sinkronisasi SiPintu" feature.
 *
 * Orchestrates the SiPintu student, teacher, and DUDI sync and persists the sync
 * history. Student data goes only into the Siswa module; teacher data goes
 * only into the Guru module; DUDI data goes only into the DUDI module.
 */
interface SipintuSyncServiceInterface
{
    /**
     * Get the data needed to render the sync dashboard page.
     *
     * @return array{
     *     connection_status: string,
     *     connection_message: string,
     *     connection_detail: string|null,
     *     connection_troubleshooting: string|null,
     *     connection_success: bool,
     *     connection_http_status: int|null,
     *     connection_error_type: string|null,
     *     last_sync: array<string, mixed>|null,
     *     sipintu_student_count: int|null,
     *     sipintu_teacher_count: int|null,
     *     sipintu_dudi_count: int|null,
     *     local_student_count: int,
     *     local_teacher_count: int,
     *     local_dudi_count: int,
     *     classroom_mapping_count: int,
     *     needs_mapping_count: int,
     *     history: \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\SiPintuSyncLog>
     * }
     */
    public function getDashboardData(): array;

    /**
     * Test the live SiPintu connection without reading cached dashboard data.
     *
     * @return array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null, detail: string|null, troubleshooting: string|null, endpoint_warning?: bool, endpoints?: array<string, mixed>}
     */
    public function testConnection(): array;

    /**
     * Return cached live diagnostics (short TTL ~5 minutes) or run a fresh check when missing/forced.
     *
     * @return array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null, detail: string|null, troubleshooting: string|null, endpoint_warning?: bool, endpoints?: array<string, mixed>}|null
     */
    public function getLiveDiagnostics(bool $forceRefresh = false): ?array;

    /**
     * Run a READ-ONLY preview / dry-run of the sync.
     *
     * Classifies remote students, teachers, and dudis into categories WITHOUT any
     * INSERT/UPDATE/DELETE. Safe to call any time.
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     success: bool,
     *     scope?: string,
     *     included?: array{students: bool, teachers: bool, dudis: bool},
     *     message: string,
     *     duration_ms: int,
     *     students: array<string, int|string>,
     *     teachers: array<string, int|string>,
     *     dudis?: array<string, int|string>
     * }
     */
    public function preview(string $entityScope = 'all', array $options = []): array;

    /**
     * Run a manual synchronization triggered by an admin.
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     success: bool,
     *     status?: string,
     *     warning?: bool,
     *     message: string,
     *     stats: array{
     *         students: array<string, int>,
     *         teachers: array<string, int>,
     *         dudis?: array<string, int>
     *     }
     * }
     */
    public function runSync(User $admin, string $entityScope = 'all', array $options = []): array;
}
