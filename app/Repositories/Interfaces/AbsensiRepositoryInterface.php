<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Absensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Absensi>
 */
interface AbsensiRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and filter absensi with eager loading.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function search(
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get absensi by penempatan PKL ID.
     *
     * @return Collection<int, Absensi>
     */
    public function getByPenempatan(int $penempatanPklId): Collection;

    /**
     * Find today's absensi for a given penempatan.
     */
    public function findTodayByPenempatan(int $penempatanPklId): ?Absensi;

    /**
     * Get absensi for a specific siswa (via penempatan).
     *
     * @return Collection<int, Absensi>
     */
    public function getBySiswa(int $siswaId): Collection;

    /**
     * Get paginated absensi for a specific siswa.
     *
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getBySiswaPaginated(
        int $siswaId,
        ?string $tanggal = null,
        ?string $status = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

/**
     * Get paginated absensi for a specific DUDI (via penempatan).
     *
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getByDudiPaginated(
        int $dudiId,
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get paginated absensi for a specific guru (via penempatan bimbingan).
     *
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getByGuruPaginated(
        int $guruId,
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all trashed records.
     *
     * @return Collection<int, Absensi>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted absensi.
     */
    public function restore(Absensi $absensi): bool;

    /**
     * Permanently delete an absensi.
     */
    public function forceDelete(Absensi $absensi): bool;
}

