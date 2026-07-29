<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Absensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Absensi business logic operations.
 */
interface AbsensiServiceInterface
{
    /**
     * Get paginated absensi with search and filters.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator;

    /**
     * Find absensi by ID.
     */
    public function findOrFail(int $id): Absensi;

    /**
     * Store a new absensi (CRUD).
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Absensi;

    /**
     * Update an existing absensi.
     *
     * @param array<string, mixed> $data
     */
    public function update(Absensi $absensi, array $data): Absensi;

    /**
     * Soft delete an absensi.
     */
    public function destroy(Absensi $absensi): bool;

    /**
     * Restore a soft-deleted absensi.
     */
    public function restore(Absensi $absensi): bool;

    /**
     * Permanently delete an absensi.
     */
    public function forceDelete(Absensi $absensi): bool;

    /**
     * Check In for a student.
     *
     * @param array<string, mixed> $data
     */
    public function checkIn(int $penempatanPklId, array $data): Absensi;

    /**
     * Check Out for a student.
     *
     * @param array<string, mixed> $data
     */
    public function checkOut(int $penempatanPklId, array $data): Absensi;

    /**
     * Get today's absensi for a penempatan.
     */
    public function getTodayAbsensi(int $penempatanPklId): ?Absensi;

    /**
     * Get paginated absensi for a specific siswa.
     *
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getSiswaAbsensiPaginated(int $siswaId, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated absensi for a specific guru.
     *
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getGuruAbsensiPaginated(int $guruId, array $filters = []): LengthAwarePaginator;

    /**
     * Validate/verify an absensi (by Guru).
     *
     * @param array<string, mixed> $data
     */
    public function validateAbsensi(Absensi $absensi, array $data): Absensi;
}

