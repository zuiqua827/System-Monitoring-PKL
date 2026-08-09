<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Guru;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Guru>
 */
interface GuruRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate guru with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Guru>
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

/**
     * Find a guru by NIP (including trashed records).
     */
    public function findByNip(string $nip): ?Guru;

    /**
     * Get all guru including trashed records.
     *
     * @return Collection<int, Guru>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted guru.
     */
    public function restore(Guru $guru): bool;

    /**
     * Permanently delete a guru.
     */
    public function forceDelete(Guru $guru): bool;
}

