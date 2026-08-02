<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Penilaian;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Penilaian business logic operations.
 */
interface PenilaianServiceInterface
{
    /**
     * Get paginated penilaian with optional search and filters.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated penilaian for a Guru's students.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getGuruPenilaianPaginated(int $guruId, array $filters = []): LengthAwarePaginator;

    /**
     * Get paginated penilaian for a Siswa's own records.
     *
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getSiswaPenilaianPaginated(int $siswaId, array $filters = []): LengthAwarePaginator;

    /**
     * Find a penilaian by ID with eager loading.
     */
    public function findOrFail(int $id): Penilaian;

    /**
     * Store a new penilaian.
     *
     * Automatically calculates nilai_akhir and predikat.
     *
     * @param array<string, mixed> $data
     */
    public function store(array $data): Penilaian;

    /**
     * Update an existing penilaian.
     *
     * Automatically recalculates nilai_akhir and predikat.
     *
     * @param array<string, mixed> $data
     */
    public function update(Penilaian $penilaian, array $data): Penilaian;

    /**
     * Finalize a penilaian (change status to 'final').
     *
     * Once finalized, Guru cannot edit anymore.
     */
    public function finalize(Penilaian $penilaian): Penilaian;

    /**
     * Soft delete a penilaian.
     */
    public function destroy(Penilaian $penilaian): bool;

    /**
     * Restore a soft-deleted penilaian.
     */
    public function restore(Penilaian $penilaian): bool;

    /**
     * Permanently delete a penilaian.
     */
    public function forceDelete(Penilaian $penilaian): bool;

    /**
     * Calculate nilai_akhir from 7 aspek values.
     *
     * Formula: (disiplin + kehadiran + tanggung_jawab + komunikasi + kerjasama + inisiatif + teknis) / 7
     */
    public function calculateNilaiAkhir(
        ?int $disiplin,
        ?int $kehadiran,
        ?int $tanggungJawab,
        ?int $komunikasi,
        ?int $kerjasama,
        ?int $inisiatif,
        ?int $teknis,
    ): ?float;

    /**
     * Calculate predikat based on nilai_akhir.
     *
     * >= 90 → A
     * 80-89 → B
     * 70-79 → C
     * 60-69 → D
     * < 60 → E
     */
    public function calculatePredikat(?float $nilaiAkhir): ?string;
}
