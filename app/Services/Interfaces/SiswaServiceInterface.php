<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Siswa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Siswa business logic operations.
 */
interface SiswaServiceInterface
{
    /**
     * Get paginated siswa with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Siswa>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a siswa by ID.
     */
    public function findOrFail(int $id): Siswa;

    /**
     * Store a new siswa along with its User account.
     *
     * Creates a User with role "Siswa", then creates the Siswa record.
     * All within a single database transaction.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Siswa;

    /**
     * Update an existing siswa and its associated User.
     *
     * @param array<string, mixed> $data
     */
    public function update(Siswa $siswa, array $data): Siswa;

    /**
     * Soft delete a siswa (does NOT delete the User).
     */
    public function destroy(Siswa $siswa): bool;

    /**
     * Restore a soft-deleted siswa.
     */
    public function restore(Siswa $siswa): bool;

    /**
     * Permanently delete a siswa and its associated User.
     */
    public function forceDelete(Siswa $siswa): bool;
}
