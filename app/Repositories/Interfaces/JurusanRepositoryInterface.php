<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Jurusan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Jurusan>
 */
interface JurusanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate jurusan with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Jurusan>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'kode',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all jurusan including trashed records.
     *
     * @return Collection<int, Jurusan>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted jurusan.
     */
    public function restore(Jurusan $jurusan): bool;

    /**
     * Permanently delete a jurusan.
     */
    public function forceDelete(Jurusan $jurusan): bool;
}
