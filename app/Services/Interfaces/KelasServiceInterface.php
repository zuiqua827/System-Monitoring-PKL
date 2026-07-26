<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Kelas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Kelas business logic operations.
 */
interface KelasServiceInterface
{
    /**
     * Get paginated kelas with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Kelas>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a kelas by ID.
     */
    public function findOrFail(int $id): Kelas;

    /**
     * Store a new kelas.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Kelas;

    /**
     * Update an existing kelas.
     *
     * @param array<string, mixed> $data
     */
    public function update(Kelas $kelas, array $data): Kelas;

    /**
     * Soft delete a kelas.
     */
    public function destroy(Kelas $kelas): bool;

    /**
     * Restore a soft-deleted kelas.
     */
    public function restore(Kelas $kelas): bool;

    /**
     * Permanently delete a kelas.
     */
    public function forceDelete(Kelas $kelas): bool;
}
