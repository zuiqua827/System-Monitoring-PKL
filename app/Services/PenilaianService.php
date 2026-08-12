<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Penilaian;
use App\Repositories\Interfaces\PenilaianRepositoryInterface;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use App\Services\Interfaces\PenilaianServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for Penilaian business logic.
 */
class PenilaianService extends Service implements PenilaianServiceInterface
{
    public function __construct(
        private readonly PenilaianRepositoryInterface $penilaianRepository,
        private readonly AbsensiRepositoryInterface $absensiRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return $this->penilaianRepository->search(
            keyword: $filters['search'] ?? null,
            status: $filters['status'] ?? null,
            guruId: $filters['guru_id'] ?? null,
            periodeId: $filters['periode_id'] ?? null,
            sortBy: $filters['sort_by'] ?? 'created_at',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 15),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getGuruPenilaianPaginated(int $guruId, array $filters = []): LengthAwarePaginator
    {
        return $this->penilaianRepository->getByGuruPaginated(
            guruId: $guruId,
            keyword: $filters['search'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: $filters['periode_id'] ?? null,
            sortBy: $filters['sort_by'] ?? 'created_at',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 15),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiPenilaianPaginated(int $dudiId, array $filters = []): LengthAwarePaginator
    {
        return $this->penilaianRepository->getByDudiPaginated(
            dudiId: $dudiId,
            keyword: $filters['search'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: $filters['periode_id'] ?? null,
            sortBy: $filters['sort_by'] ?? 'created_at',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 15),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSiswaPenilaianPaginated(int $siswaId, array $filters = []): LengthAwarePaginator
    {
        return $this->penilaianRepository->getBySiswaPaginated(
            siswaId: $siswaId,
            sortBy: $filters['sort_by'] ?? 'created_at',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 15),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Penilaian
    {
        /** @var Penilaian|null $penilaian */
        $penilaian = $this->penilaianRepository->find($id);

        if ($penilaian === null) {
            throw new ModelNotFoundException('Penilaian tidak ditemukan.');
        }

        return $penilaian;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Penilaian
    {
        /** @var Penilaian $penilaian */
        $penilaian = $this->transaction(function () use ($data): Model {
            $nilaiKehadiran = $this->calculateKehadiranPercentage((int) $data['penempatan_pkl_id']);

            $nilaiAkhir = $this->calculateNilaiAkhir(
                kehadiran: $nilaiKehadiran,
                kerjasama: isset($data['nilai_kerjasama']) ? (int) $data['nilai_kerjasama'] : null,
                komunikasi: isset($data['nilai_komunikasi']) ? (int) $data['nilai_komunikasi'] : null,
                problemSolving: isset($data['nilai_problem_solving']) ? (int) $data['nilai_problem_solving'] : null,
                teknis: isset($data['nilai_teknis']) ? (int) $data['nilai_teknis'] : null,
                inisiatif: isset($data['nilai_inisiatif']) ? (int) $data['nilai_inisiatif'] : null,
            );

            $predikat = $this->calculatePredikat($nilaiAkhir);

            return $this->penilaianRepository->create([
                'penempatan_pkl_id' => $data['penempatan_pkl_id'],
                'dinilai_oleh' => Auth::id(),
                'nilai_kehadiran' => $nilaiKehadiran,
                'nilai_kerjasama' => $data['nilai_kerjasama'] ?? null,
                'nilai_komunikasi' => $data['nilai_komunikasi'] ?? null,
                'nilai_problem_solving' => $data['nilai_problem_solving'] ?? null,
                'nilai_teknis' => $data['nilai_teknis'] ?? null,
                'nilai_inisiatif' => $data['nilai_inisiatif'] ?? null,
                'nilai_akhir' => $nilaiAkhir,
                'predikat' => $predikat,
                'status' => $data['status'] ?? 'draft',
                'tanggal_penilaian' => $data['tanggal_penilaian'] ?? now()->toDateString(),
                'catatan' => $data['catatan'] ?? null,
                'catatan_guru' => $data['catatan_guru'] ?? null,
            ]);
        });

        return $penilaian;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Penilaian $penilaian, array $data): Penilaian
    {
        /** @var Penilaian $updated */
        $updated = $this->transaction(function () use ($penilaian, $data): Model {
            $nilaiKehadiran = $this->calculateKehadiranPercentage((int) $penilaian->penempatan_pkl_id);

            $nilaiAkhir = $this->calculateNilaiAkhir(
                kehadiran: $nilaiKehadiran,
                kerjasama: isset($data['nilai_kerjasama']) ? (int) $data['nilai_kerjasama'] : ($penilaian->nilai_kerjasama),
                komunikasi: isset($data['nilai_komunikasi']) ? (int) $data['nilai_komunikasi'] : ($penilaian->nilai_komunikasi),
                problemSolving: isset($data['nilai_problem_solving']) ? (int) $data['nilai_problem_solving'] : ($penilaian->nilai_problem_solving),
                teknis: isset($data['nilai_teknis']) ? (int) $data['nilai_teknis'] : ($penilaian->nilai_teknis),
                inisiatif: isset($data['nilai_inisiatif']) ? (int) $data['nilai_inisiatif'] : ($penilaian->nilai_inisiatif),
            );

            $predikat = $this->calculatePredikat($nilaiAkhir);

            return $this->penilaianRepository->update($penilaian, [
                'nilai_kehadiran' => $nilaiKehadiran,
                'nilai_kerjasama' => $data['nilai_kerjasama'] ?? $penilaian->nilai_kerjasama,
                'nilai_komunikasi' => $data['nilai_komunikasi'] ?? $penilaian->nilai_komunikasi,
                'nilai_problem_solving' => $data['nilai_problem_solving'] ?? $penilaian->nilai_problem_solving,
                'nilai_teknis' => $data['nilai_teknis'] ?? $penilaian->nilai_teknis,
                'nilai_inisiatif' => $data['nilai_inisiatif'] ?? $penilaian->nilai_inisiatif,
                'nilai_akhir' => $nilaiAkhir,
                'predikat' => $predikat,
                'status' => $data['status'] ?? $penilaian->status,
                'catatan' => $data['catatan'] ?? $penilaian->catatan,
                'catatan_guru' => $data['catatan_guru'] ?? $penilaian->catatan_guru,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function finalize(Penilaian $penilaian): Penilaian
    {
        if ($penilaian->status === 'final') {
            throw new \RuntimeException('Penilaian sudah dalam status Final.');
        }

        /** @var Penilaian $updated */
        $updated = $this->penilaianRepository->update($penilaian, [
            'status' => 'final',
        ]);

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Penilaian $penilaian): bool
    {
        return $this->penilaianRepository->delete($penilaian);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Penilaian $penilaian): bool
    {
        return $this->penilaianRepository->restore($penilaian);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Penilaian $penilaian): bool
    {
        return $this->penilaianRepository->forceDelete($penilaian);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateNilaiAkhir(
        ?int $kehadiran,
        ?int $kerjasama,
        ?int $komunikasi,
        ?int $problemSolving,
        ?int $teknis,
        ?int $inisiatif,
    ): ?float {
        if ($kehadiran === null || $kerjasama === null || $komunikasi === null || $problemSolving === null || $teknis === null || $inisiatif === null) {
            return null; // Return null if any component is missing, or adjust based on requirements. If all are required, this is safe.
        }

        $totalBobot = 14;
        
        $nilai = (
            ($kehadiran * 4) +
            ($kerjasama * 2) +
            ($komunikasi * 2) +
            ($problemSolving * 2) +
            ($teknis * 2) +
            ($inisiatif * 2)
        ) / $totalBobot;

        return round($nilai, 2);
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePredikat(?float $nilaiAkhir): ?string
    {
        if ($nilaiAkhir === null) {
            return null;
        }

        return match (true) {
            $nilaiAkhir >= 90 => 'A',
            $nilaiAkhir >= 80 => 'B',
            $nilaiAkhir >= 70 => 'C',
            $nilaiAkhir >= 60 => 'D',
            default => 'E',
        };
    }

    private function calculateKehadiranPercentage(int $penempatanPklId): int
    {
        $absensi = $this->absensiRepository->getByPenempatan($penempatanPklId);
        
        $total = $absensi->count();
        if ($total === 0) {
            return 0; // Or 100 if assuming no absensi records = perfect? No, based on rule, missing is Alfa. So 0.
        }

        $hadir = $absensi->whereIn('status', ['hadir', 'terlambat'])->count();
        
        return (int) round(($hadir / $total) * 100);
    }
}
