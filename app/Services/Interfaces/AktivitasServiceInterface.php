<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Aktivitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Aktivitas (Daily Activity) business logic operations.
 */
interface AktivitasServiceInterface
{
    /**
     * Get paginated aktivitas with optional filters.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Aktivitas>
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator;

    /**
     * Find an aktivitas by ID.
     */
    public function findOrFail(int $id): Aktivitas;

    /**
     * Store a new aktivitas.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Aktivitas;

    /**
     * Update an existing aktivitas.
     *
     * @param array<string, mixed> $data
     */
    public function update(Aktivitas $aktivitas, array $data): Aktivitas;

    /**
     * Soft delete an aktivitas.
     */
    public function destroy(Aktivitas $aktivitas): bool;

    /**
     * Restore a soft-deleted aktivitas.
     */
    public function restore(Aktivitas $aktivitas): bool;

    /**
     * Permanently delete an aktivitas.
     */
    public function forceDelete(Aktivitas $aktivitas): bool;

    /**
     * Get paginated aktivitas for a specific siswa.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Aktivitas>
     */
    public function getSiswaAktivitasPaginated(int $siswaId, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated aktivitas for a specific guru's bimbingan.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Aktivitas>
     */
    public function getGuruAktivitasPaginated(int $guruId, array $filters = []): LengthAwarePaginator;

    /**
     * Submit aktivitas for validation (change status from draft to menunggu_validasi).
     */
    public function submit(Aktivitas $aktivitas): Aktivitas;

    /**
     * Validate (approve/reject) an aktivitas as a guru.
     *
     * @param array<string, mixed> $data
     */
    public function validateAktivitas(Aktivitas $aktivitas, array $data): Aktivitas;

    /**
     * Get today's absensi untuk pengecekan sudah checkin.
     */
    public function hasCheckedInToday(int $penempatanPklId): bool;
}

