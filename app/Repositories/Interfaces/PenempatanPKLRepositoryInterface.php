<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\PenempatanPKL;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<PenempatanPKL>
 */
interface PenempatanPKLRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate penempatan with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, PenempatanPKL>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

/**
     * Search penempatan by DUDI.
     *
     * @return LengthAwarePaginator<int, PenempatanPKL>
     */
    public function searchByDudi(
        int $dudiId,
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all penempatan including trashed records.
     *
     * @return Collection<int, PenempatanPKL>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted penempatan.
     */
    public function restore(PenempatanPKL $penempatanPkl): bool;

    /**
     * Permanently delete a penempatan.
     */
    public function forceDelete(PenempatanPKL $penempatanPkl): bool;
}
