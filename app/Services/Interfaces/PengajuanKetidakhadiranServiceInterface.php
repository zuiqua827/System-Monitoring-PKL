<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\PengajuanKetidakhadiran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PengajuanKetidakhadiranServiceInterface
{
    /**
     * Get paginated pengajuan by siswa ID.
     */
    public function getSiswaPengajuanPaginated(int $siswaId, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated pengajuan by DUDI ID.
     */
    public function getDudiPengajuanPaginated(int $dudiId, array $filters = []): LengthAwarePaginator;

    /**
     * Store a new pengajuan ketidakhadiran.
     * Ensures no duplicates for the same date and penempatan.
     */
    public function storePengajuan(array $data): PengajuanKetidakhadiran;

    /**
     * Process (approve/reject) an existing pengajuan.
     */
    public function process(PengajuanKetidakhadiran $pengajuan, string $status, ?string $catatan, int $validatorId): PengajuanKetidakhadiran;
}
