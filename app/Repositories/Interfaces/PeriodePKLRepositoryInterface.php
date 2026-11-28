<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\PeriodePKL;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<PeriodePKL>
 */
interface PeriodePKLRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate periode PKL with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, PeriodePKL>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all periode PKL including trashed records.
     *
     * @return Collection<int, PeriodePKL>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted periode PKL.
     */
    public function restore(PeriodePKL $periodePkl): bool;

    /**
     * Permanently delete a periode PKL.
     */
    public function forceDelete(PeriodePKL $periodePkl): bool;
}
