<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Penilaian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Penilaian>
 */
interface PenilaianRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search penilaian with optional filters and sorting.
     *
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function search(
        ?string $keyword = null,
        ?string $status = null,
        ?int $guruId = null,
        ?int $periodeId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get penilaian paginated by guru (penilaian of students under this guru).
     *
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getByGuruPaginated(
        int $guruId,
        ?string $keyword = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

/**
     * Get penilaian paginated by DUDI (penilaian of students under this dudi).
     *
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getByDudiPaginated(
        int $dudiId,
        ?string $keyword = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get penilaian paginated by siswa (penilaian belonging to this siswa).
     *
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getBySiswaPaginated(
        int $siswaId,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all penilaian including trashed records.
     *
     * @return Collection<int, Penilaian>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted penilaian.
     */
    public function restore(Penilaian $penilaian): bool;

    /**
     * Permanently delete a penilaian.
     */
    public function forceDelete(Penilaian $penilaian): bool;
}
