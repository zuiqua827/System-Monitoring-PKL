<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\PenempatanPKL;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for PenempatanPKL business logic operations.
 */
interface PenempatanPKLServiceInterface
{
    /**
     * Get paginated penempatan with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, PenempatanPKL>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get paginated students for a specific DUDI.
     *
     * @return LengthAwarePaginator<int, PenempatanPKL>
     */
    public function getDudiSiswaPaginated(
        int $dudiId,
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a penempatan PKL by ID.
     */
    public function findOrFail(int $id): PenempatanPKL;

    /**
     * Store a new penempatan PKL.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): PenempatanPKL;

    /**
     * Update an existing penempatan PKL.
     *
     * @param array<string, mixed> $data
     */
    public function update(PenempatanPKL $penempatanPkl, array $data): PenempatanPKL;

    /**
     * Soft delete a penempatan PKL.
     */
    public function destroy(PenempatanPKL $penempatanPkl): bool;

    /**
     * Restore a soft-deleted penempatan PKL.
     */
    public function restore(PenempatanPKL $penempatanPkl): bool;

    /**
     * Permanently delete a penempatan PKL.
     */
    public function forceDelete(PenempatanPKL $penempatanPkl): bool;
}
