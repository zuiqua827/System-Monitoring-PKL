<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\PeriodePKL;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for PeriodePKL business logic operations.
 */
interface PeriodePKLServiceInterface
{
    /**
     * Get paginated periode PKL with optional search and sorting.
     *
     * @return LengthAwarePaginator<int, PeriodePKL>
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Find a periode PKL by ID.
     */
    public function findOrFail(int $id): PeriodePKL;

    /**
     * Store a new periode PKL.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): PeriodePKL;

    /**
     * Update an existing periode PKL.
     *
     * @param array<string, mixed> $data
     */
    public function update(PeriodePKL $periodePkl, array $data): PeriodePKL;

    /**
     * Soft delete a periode PKL.
     */
    public function destroy(PeriodePKL $periodePkl): bool;

    /**
     * Restore a soft-deleted periode PKL.
     */
    public function restore(PeriodePKL $periodePkl): bool;

    /**
     * Permanently delete a periode PKL.
     */
    public function forceDelete(PeriodePKL $periodePkl): bool;
}
