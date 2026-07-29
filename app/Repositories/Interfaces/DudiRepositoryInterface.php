<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Dudi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Dudi>
 */
interface DudiRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate DUDI with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Dudi>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama_perusahaan',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all DUDI including trashed records.
     *
     * @return Collection<int, Dudi>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted DUDI.
     */
    public function restore(Dudi $dudi): bool;

    /**
     * Permanently delete a DUDI.
     */
    public function forceDelete(Dudi $dudi): bool;
}
