<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Guru;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Guru business logic operations.
 */
interface GuruServiceInterface
{
    /**
     * Get paginated guru with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Guru>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a guru by ID.
     */
    public function findOrFail(int $id): Guru;

    /**
     * Store a new guru along with its User account.
     *
     * Creates a User with role "Guru", then creates the Guru record.
     * All within a single database transaction.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Guru;

    /**
     * Update an existing guru and its associated User.
     *
     * @param array<string, mixed> $data
     */
    public function update(Guru $guru, array $data): Guru;

    /**
     * Soft delete a guru and its associated User.
     */
    public function destroy(Guru $guru): bool;

    /**
     * Restore a soft-deleted guru and its associated User.
     */
    public function restore(Guru $guru): bool;

    /**
     * Permanently delete a guru and its associated User.
     */
    public function forceDelete(Guru $guru): bool;
}

