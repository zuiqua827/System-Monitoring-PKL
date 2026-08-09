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
        ?int $jurusanId = null,
        ?int $kelasId = null,
        ?string $status = null,
    ): LengthAwarePaginator;

/**
     * Search students for the searchable select (AJAX).
     *
     * @return list<array{id: int, nama: string, nis: string, nisn: ?string, kelas: ?string, jurusan: ?string}>
     */
    public function searchForSelect(string $search): array;

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

    /**
     * Migrate every existing Student (Siswa-role) user to the new default
     * password policy.
     *
     * For each non-deleted Siswa-role user:
     *   - password             = Hash::make('password')
     *   - must_change_password = false
     *
     * Rules:
     *   - Only Siswa-role users are touched (never Guru, DUDI, Super Admin).
     *   - Already-compliant users are skipped (idempotent).
     *   - Never creates duplicates; never modifies NIS/email/profile/
     *     relations/roles/permissions.
     *   - Each update runs inside a database transaction.
     *
     * @return array{total: int, updated: int, skipped: int, failed: int}
     */
    public function migrateStudentPasswords(): array;
}
