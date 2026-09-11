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
     * Get paginated penilaian for a DUDI's students.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Penilaian>
     */
    public function getDudiPenilaianPaginated(int $dudiId, array $filters = []): LengthAwarePaginator;

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
        ?int $kehadiran,
        ?int $kerjasama,
        ?int $komunikasi,
        ?int $problemSolving,
        ?int $teknis,
        ?int $inisiatif,
    ): ?float;

    /**
     * Calculate predikat based on nilai_akhir.
     *
     * >= 95 → A+
     * >= 90 → A
     * 80-89 → B
     * 70-79 → C
     * < 70 → D
     */
    public function calculatePredikat(?float $nilaiAkhir): ?string;

    /**
     * Get attendance summary data (hadir, sakit, izin, alpha, total_hari, and percentages) for a placement.
     *
     * @return array{hadir: int, sakit: int, izin: int, alpha: int, total_hari: int, hadir_pct: float, sakit_pct: float, izin_pct: float, alpha_pct: float}
     */
    public function getRekapAbsensiData(int $penempatanPklId): array;

    /**
     * Get rule-based evaluation description for a given predicate (Nilai Akhir).
     */
    public static function getDeskripsiPredikat(?string $predikat): string;

    /**
     * Get rule-based evaluation description for a specific aspect and score or predicate.
     *
     * @param string $aspek Key or name of aspect (e.g. 'kehadiran', 'kerjasama', 'komunikasi', 'problem_solving', 'teknis', 'inisiatif')
     * @param int|float|string|null $nilaiOrPredikat Score (0-100) or predicate ('A+', 'A', 'B', 'C', 'D')
     */
    public static function getDeskripsiAspek(string $aspek, int|float|string|null $nilaiOrPredikat): string;
}
