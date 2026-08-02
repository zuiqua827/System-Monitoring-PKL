<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Absensi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Absensi business logic operations.
 *
 * Covers CRUD, Check In/Out with GPS/radius validation, and photo handling.
 */
interface AbsensiServiceInterface
{
    /**
     * Get paginated absensi with optional search and filters.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator;

    /**
     * Find an absensi by ID.
     */
    public function findOrFail(int $id): Absensi;

    /**
     * Store a new absensi record.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Absensi;

    /**
     * Update an existing absensi record.
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
     * Process Check In with GPS radius validation and auto status.
     *
     * @param int $penempatanPklId The active penempatan PKL ID
     * @param array<string, mixed> $data Contains: foto_base64|foto_masuk, latitude, longitude, accuracy, lokasi_masuk
     * @return Absensi The created absensi record
     * @throws \RuntimeException If already checked in today or outside radius
     */
    public function checkIn(int $penempatanPklId, array $data): Absensi;

    /**
     * Process Check Out.
     *
     * @param int $penempatanPklId The active penempatan PKL ID
     * @param array<string, mixed> $data Contains: foto_base64|foto_pulang, latitude, longitude, accuracy, lokasi_pulang
     * @return Absensi The updated absensi record
     * @throws \RuntimeException If not checked in or already checked out
     */
    public function checkOut(int $penempatanPklId, array $data): Absensi;

    /**
     * Get today's absensi for a given penempatan.
     */
    public function getTodayAbsensi(int $penempatanPklId): ?Absensi;

    /**
     * Get paginated absensi for a specific siswa.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getSiswaAbsensiPaginated(int $siswaId, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated absensi for a specific guru's bimbingan students.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Absensi>
     */
    public function getGuruAbsensiPaginated(int $guruId, array $filters = []): LengthAwarePaginator;

    /**
     * Validate/update absensi status by guru.
     *
     * @param array<string, mixed> $data
     */
    public function validateAbsensi(Absensi $absensi, array $data): Absensi;
}
