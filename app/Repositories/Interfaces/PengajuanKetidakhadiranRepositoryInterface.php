<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\PengajuanKetidakhadiran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<PengajuanKetidakhadiran>
 */
interface PengajuanKetidakhadiranRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get paginated pengajuan by siswa ID.
     */
    public function getBySiswaPaginated(
        int $siswaId,
        ?string $status = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get paginated pengajuan by DUDI ID.
     */
    public function getByDudiPaginated(
        int $dudiId,
        ?string $status = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator;
    
    /**
     * Check if a pengajuan exists for a given penempatan and date.
     */
    public function existsForDate(int $penempatanPklId, string $tanggal): bool;
}
