<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Dudi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for DUDI business logic operations.
 */
interface DudiServiceInterface
{
    /**
     * Get paginated DUDI with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Dudi>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama_perusahaan',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a DUDI by ID.
     */
    public function findOrFail(int $id): Dudi;

    /**
     * Store a new DUDI along with its User account.
     *
     * Creates a User with role "DUDI", then creates the Dudi record.
     * All within a single database transaction.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Dudi;

    /**
     * Update an existing DUDI and its associated User.
     *
     * @param array<string, mixed> $data
     */
    public function update(Dudi $dudi, array $data): Dudi;

    /**
     * Soft delete a DUDI and its associated User.
     */
    public function destroy(Dudi $dudi): bool;

    /**
     * Restore a soft-deleted DUDI and its associated User.
     */
    public function restore(Dudi $dudi): bool;

    /**
     * Permanently delete a DUDI and its associated User.
     */
    public function forceDelete(Dudi $dudi): bool;
}
