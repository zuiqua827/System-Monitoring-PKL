<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Kelas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Kelas>
 */
interface KelasRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate kelas with optional search and sorting.
     * Eager loads jurusan relationship.
     *
     * @return LengthAwarePaginator<int, Kelas>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all kelas including trashed records.
     *
     * @return Collection<int, Kelas>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted kelas.
     */
    public function restore(Kelas $kelas): bool;

    /**
     * Permanently delete a kelas.
     */
    public function forceDelete(Kelas $kelas): bool;
}
