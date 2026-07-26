<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Jurusan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Jurusan business logic operations.
 */
interface JurusanServiceInterface
{
    /**
     * Get paginated jurusan with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, Jurusan>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'kode',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a jurusan by ID.
     */
    public function findOrFail(int $id): Jurusan;

    /**
     * Store a new jurusan.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Jurusan;

    /**
     * Update an existing jurusan.
     *
     * @param array<string, mixed> $data
     */
    public function update(Jurusan $jurusan, array $data): Jurusan;

    /**
     * Soft delete a jurusan.
     */
    public function destroy(Jurusan $jurusan): bool;

    /**
     * Restore a soft-deleted jurusan.
     */
    public function restore(Jurusan $jurusan): bool;

    /**
     * Permanently delete a jurusan.
     */
    public function forceDelete(Jurusan $jurusan): bool;
}
