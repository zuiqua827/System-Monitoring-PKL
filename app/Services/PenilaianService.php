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
    /**
     * {@inheritDoc}
     */
    public function store(array $data): Penilaian
    {
        /** @var Penilaian $penilaian */
        $penilaian = $this->transaction(function () use ($data): Model {
            $penempatanId = (int) $data['penempatan_pkl_id'];
            $nilaiKehadiran = $this->calculateKehadiranScore($penempatanId);

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
                'penempatan_pkl_id' => $penempatanId,
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
            $penempatanId = (int) $penilaian->penempatan_pkl_id;
            $nilaiKehadiran = $this->calculateKehadiranScore($penempatanId);

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
            return null;
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
            $nilaiAkhir >= 95 => 'A+',
            $nilaiAkhir >= 90 => 'A',
            $nilaiAkhir >= 80 => 'B',
            $nilaiAkhir >= 70 => 'C',
            default => 'D',
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getRekapAbsensiData(int $penempatanPklId): array
    {
        $defaultResult = [
            'hadir' => 0,
            'sakit' => 0,
            'izin' => 0,
            'alpha' => 0,
            'total_hari' => 0,
            'hadir_pct' => 0.0,
            'sakit_pct' => 0.0,
            'izin_pct' => 0.0,
            'alpha_pct' => 0.0,
        ];

        $penempatan = \App\Models\PenempatanPKL::with(['dudi', 'periodePKL'])->find($penempatanPklId);
        if ($penempatan === null) {
            return $defaultResult;
        }

        $absensiList = $this->absensiRepository->getByPenempatan($penempatanPklId);
        
        $pengajuanApproved = \App\Models\PengajuanKetidakhadiran::where('penempatan_pkl_id', $penempatanPklId)
            ->where('status', 'disetujui')
            ->get();

        $tanggalMulai = $penempatan->tanggal_mulai ?? $penempatan->periodePKL?->tanggal_mulai;
        $tanggalSelesai = $penempatan->tanggal_selesai ?? $penempatan->periodePKL?->tanggal_selesai;

        $timezone = config('app.timezone');
        $today = \Illuminate\Support\Carbon::today($timezone);

        // Find earliest absensi date for this placement if any
        $earliestAbsensi = null;
        foreach ($absensiList as $a) {
            if ($a->tanggal) {
                $cDate = \Illuminate\Support\Carbon::parse($a->tanggal, $timezone)->startOfDay();
                if ($earliestAbsensi === null || $cDate->lt($earliestAbsensi)) {
                    $earliestAbsensi = $cDate;
                }
            }
        }

        $startCalc = $tanggalMulai ? \Illuminate\Support\Carbon::parse($tanggalMulai, $timezone)->startOfDay() : null;
        if ($startCalc === null || ($startCalc->gt($today) && $earliestAbsensi && $earliestAbsensi->lte($today))) {
            $startCalc = $earliestAbsensi ? clone $earliestAbsensi : null;
        } elseif ($earliestAbsensi && $earliestAbsensi->lt($startCalc)) {
            $startCalc = clone $earliestAbsensi;
        }

        if ($startCalc === null || $startCalc->gt($today)) {
            return $defaultResult;
        }

        $endCalc = ($tanggalSelesai && $today->gt(\Illuminate\Support\Carbon::parse($tanggalSelesai, $timezone)))
            ? \Illuminate\Support\Carbon::parse($tanggalSelesai, $timezone)->startOfDay()
            : clone $today;

        if ($startCalc->gt($endCalc)) {
            $endCalc = clone $startCalc;
        }

        $dudi = $penempatan->dudi;
        $workingDays = [];
        $cursor = clone $startCalc;
        while ($cursor->lte($endCalc)) {
            if ($dudi && $dudi->isHariOperasional($cursor)) {
                $workingDays[] = $cursor->format('Y-m-d');
            } elseif (!$dudi && $cursor->isWeekday()) {
                $workingDays[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        $absensiMap = [];
        foreach ($absensiList as $a) {
            if ($a->tanggal) {
                $absensiMap[\Illuminate\Support\Carbon::parse($a->tanggal)->format('Y-m-d')] = $a;
            }
        }

        $pengajuanMap = [];
        foreach ($pengajuanApproved as $pg) {
            if ($pg->tanggal) {
                $pengajuanMap[\Illuminate\Support\Carbon::parse($pg->tanggal)->format('Y-m-d')] = $pg;
            }
        }

        $hadirCount = 0;
        $sakitCount = 0;
        $izinCount = 0;
        $alphaCount = 0;

        foreach ($workingDays as $dateStr) {
            if (isset($absensiMap[$dateStr])) {
                $st = $absensiMap[$dateStr]->status;
                if ($st === 'hadir' || $st === 'terlambat') {
                    $hadirCount++;
                } elseif ($st === 'sakit') {
                    $sakitCount++;
                } elseif ($st === 'izin') {
                    $izinCount++;
                } elseif ($st === 'alpha') {
                    $alphaCount++;
                } else {
                    $alphaCount++;
                }
            } elseif (isset($pengajuanMap[$dateStr])) {
                $jenis = $pengajuanMap[$dateStr]->jenis;
                if ($jenis === 'sakit') {
                    $sakitCount++;
                } else {
                    $izinCount++;
                }
            } else {
                $alphaCount++;
            }
        }

        $calcTotal = count($workingDays);

        return [
            'hadir' => $hadirCount,
            'sakit' => $sakitCount,
            'izin' => $izinCount,
            'alpha' => $alphaCount,
            'total_hari' => $calcTotal,
            'hadir_pct' => $calcTotal > 0 ? (float) round(($hadirCount / $calcTotal) * 100, 1) : 0.0,
            'sakit_pct' => $calcTotal > 0 ? (float) round(($sakitCount / $calcTotal) * 100, 1) : 0.0,
            'izin_pct' => $calcTotal > 0 ? (float) round(($izinCount / $calcTotal) * 100, 1) : 0.0,
            'alpha_pct' => $calcTotal > 0 ? (float) round(($alphaCount / $calcTotal) * 100, 1) : 0.0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateKehadiranScore(int $penempatanPklId): int
    {
        $rekap = $this->getRekapAbsensiData($penempatanPklId);
        if ($rekap['total_hari'] <= 0) {
            return 100;
        }

        $points = ($rekap['hadir'] * 1.0) + ($rekap['sakit'] * 0.85) + ($rekap['izin'] * 0.70) + ($rekap['alpha'] * 0.0);
        $score = ($points / $rekap['total_hari']) * 100;

        return (int) min(100, max(0, round($score)));
    }

    /**
     * {@inheritDoc}
     */
    public static function getDeskripsiPredikat(?string $predikat): string
    {
        return match (strtoupper((string) $predikat)) {
            'A+' => 'Sangat baik dalam melaksanakan kegiatan Praktik Kerja Lapangan, menunjukkan kompetensi teknis dan sikap kerja yang sangat baik, disiplin, bertanggung jawab, komunikatif, mampu bekerja sama, serta mampu menyelesaikan masalah secara mandiri.',
            'A' => 'Baik dalam melaksanakan kegiatan Praktik Kerja Lapangan, menunjukkan penguasaan kompetensi teknis dan sikap kerja yang baik, mampu berkomunikasi dan bekerja sama serta menyelesaikan tugas dengan baik.',
            'B' => 'Baik dalam melaksanakan kegiatan Praktik Kerja Lapangan, menunjukkan kemampuan teknis dan sikap kerja yang cukup baik, mampu menyelesaikan tugas dan beradaptasi dengan lingkungan kerja.',
            'C' => 'Cukup dalam melaksanakan kegiatan Praktik Kerja Lapangan, telah menunjukkan kemampuan dalam menyelesaikan tugas namun masih perlu meningkatkan kompetensi teknis, komunikasi, kerja sama, dan kemandirian.',
            'D', 'E' => 'Perlu meningkatkan kompetensi dan sikap kerja dalam melaksanakan Praktik Kerja Lapangan, terutama dalam penyelesaian tugas, komunikasi, kerja sama, kemandirian, dan penguasaan kompetensi teknis.',
            default => 'Menunjukkan kemampuan dalam melaksanakan kegiatan PKL.',
        };
    }
}
