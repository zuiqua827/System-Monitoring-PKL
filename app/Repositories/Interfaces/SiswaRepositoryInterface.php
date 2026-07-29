<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Siswa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Siswa>
 */
interface SiswaRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate siswa with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Siswa>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all siswa including trashed records.
     *
     * @return Collection<int, Siswa>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted siswa.
     */
    public function restore(Siswa $siswa): bool;

    /**
     * Permanently delete a siswa.
     */
    public function forceDelete(Siswa $siswa): bool;
}
