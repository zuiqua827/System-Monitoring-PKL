<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Penilaian;
use App\Repositories\Interfaces\PenilaianRepositoryInterface;
use App\Services\Interfaces\AbsensiServiceInterface;
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
        private readonly AbsensiServiceInterface $absensiService,
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

            $payload = [
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
            ];

            /** @var Penilaian|null $existing */
            $existing = Penilaian::withTrashed()
                ->where('penempatan_pkl_id', $penempatanId)
                ->first();

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($payload);

                return $existing;
            }

            return $this->penilaianRepository->create($payload);
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
    /**
     * {@inheritDoc}
     */
    public function calculatePredikat(?float $nilaiAkhir): ?string
    {
        return self::calculatePredikatStatic($nilaiAkhir);
    }

    /**
     * Static helper to calculate predicate from score (0-100).
     */
    public static function calculatePredikatStatic(int|float|null $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        return match (true) {
            $nilai >= 95 => 'A+',
            $nilai >= 90 => 'A',
            $nilai >= 80 => 'B',
            $nilai >= 70 => 'C',
            default => 'D',
        };
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

    /**
     * {@inheritDoc}
     */
    public static function getDeskripsiAspek(string $aspek, int|float|string|null $nilaiOrPredikat): string
    {
        if ($nilaiOrPredikat === null || $nilaiOrPredikat === '') {
            return '-';
        }

        $predikat = is_numeric($nilaiOrPredikat)
            ? self::calculatePredikatStatic((float) $nilaiOrPredikat)
            : strtoupper((string) $nilaiOrPredikat);

        $aspekKey = strtolower(trim($aspek));
        $aspekKey = match ($aspekKey) {
            'kehadiran', 'nilai_kehadiran' => 'kehadiran',
            'kerja sama', 'kerjasama', 'nilai_kerjasama' => 'kerjasama',
            'komunikasi', 'nilai_komunikasi' => 'komunikasi',
            'problem solving', 'problem_solving', 'nilai_problem_solving' => 'problem_solving',
            'teknis', 'kemampuan teknis', 'nilai_teknis' => 'teknis',
            'inisiatif', 'nilai_inisiatif' => 'inisiatif',
            default => $aspekKey,
        };

        $templates = [
            'kehadiran' => [
                'A+' => 'Sangat baik dalam kehadiran dan sangat konsisten mengikuti kegiatan Praktik Kerja Lapangan sesuai jadwal.',
                'A'  => 'Baik dalam kehadiran dan konsisten mengikuti sebagian besar kegiatan Praktik Kerja Lapangan sesuai jadwal.',
                'B'  => 'Cukup baik dalam kehadiran, namun masih terdapat beberapa ketidakhadiran yang perlu diperhatikan.',
                'C'  => 'Perlu meningkatkan konsistensi kehadiran karena masih terdapat beberapa ketidakhadiran selama pelaksanaan PKL.',
                'D'  => 'Perlu meningkatkan kehadiran secara signifikan karena tingkat ketidakhadiran masih tinggi selama pelaksanaan PKL.',
            ],
            'kerjasama' => [
                'A+' => 'Sangat baik dalam bekerja sama, aktif membantu anggota tim, mampu berkoordinasi, dan memberikan kontribusi positif dalam pekerjaan.',
                'A'  => 'Baik dalam bekerja sama dan mampu berkoordinasi serta berkontribusi secara positif dalam menyelesaikan pekerjaan.',
                'B'  => 'Cukup baik dalam bekerja sama, namun masih perlu meningkatkan koordinasi dan kontribusi dalam pekerjaan tim.',
                'C'  => 'Perlu meningkatkan kemampuan bekerja sama, koordinasi, dan kontribusi dalam menyelesaikan pekerjaan bersama tim.',
                'D'  => 'Perlu meningkatkan kemampuan bekerja sama secara signifikan terutama dalam koordinasi dan kontribusi terhadap tim.',
            ],
            'komunikasi' => [
                'A+' => 'Sangat baik dalam berkomunikasi, mampu menyampaikan informasi dengan jelas, sopan, efektif, dan percaya diri.',
                'A'  => 'Baik dalam berkomunikasi dan mampu menyampaikan informasi dengan jelas, sopan, dan efektif.',
                'B'  => 'Cukup baik dalam berkomunikasi, namun masih perlu meningkatkan kejelasan dan kepercayaan diri dalam menyampaikan informasi.',
                'C'  => 'Perlu meningkatkan kemampuan komunikasi agar informasi dapat disampaikan dengan lebih jelas, tepat, dan percaya diri.',
                'D'  => 'Perlu meningkatkan kemampuan komunikasi secara signifikan dalam menyampaikan informasi dan berinteraksi di lingkungan kerja.',
            ],
            'problem_solving' => [
                'A+' => 'Sangat baik dalam menyelesaikan masalah, mampu menganalisis permasalahan dan menemukan solusi secara tepat serta mandiri.',
                'A'  => 'Baik dalam menyelesaikan masalah dan mampu menganalisis serta menemukan solusi yang tepat.',
                'B'  => 'Cukup baik dalam menyelesaikan masalah, namun masih memerlukan arahan pada kondisi tertentu.',
                'C'  => 'Perlu meningkatkan kemampuan menganalisis permasalahan dan menentukan solusi secara mandiri.',
                'D'  => 'Perlu meningkatkan kemampuan penyelesaian masalah karena masih mengalami kesulitan dalam menganalisis dan menentukan solusi.',
            ],
            'teknis' => [
                'A+' => 'Sangat baik dalam penguasaan kompetensi teknis dan mampu menerapkan pengetahuan serta keterampilan secara tepat dalam pekerjaan.',
                'A'  => 'Baik dalam penguasaan kompetensi teknis dan mampu menerapkan pengetahuan serta keterampilan dengan baik.',
                'B'  => 'Cukup baik dalam penguasaan kompetensi teknis, namun masih perlu meningkatkan ketelitian dan penguasaan beberapa kompetensi.',
                'C'  => 'Perlu meningkatkan penguasaan kompetensi teknis, ketelitian, dan kemampuan menerapkan pengetahuan dalam pekerjaan.',
                'D'  => 'Perlu meningkatkan kompetensi teknis secara signifikan karena masih mengalami kesulitan dalam menerapkan pengetahuan dan keterampilan.',
            ],
            'inisiatif' => [
                'A+' => 'Sangat baik dalam menunjukkan inisiatif, aktif mencari pekerjaan yang dapat dilakukan dan mampu mengambil tindakan yang tepat tanpa selalu menunggu instruksi.',
                'A'  => 'Baik dalam menunjukkan inisiatif dan mampu mengambil tindakan yang tepat dalam menyelesaikan pekerjaan.',
                'B'  => 'Cukup baik dalam menunjukkan inisiatif, namun masih perlu meningkatkan keaktifan dan keberanian dalam mengambil tindakan.',
                'C'  => 'Perlu meningkatkan inisiatif dan sikap proaktif dalam melaksanakan pekerjaan serta mengurangi ketergantungan terhadap arahan.',
                'D'  => 'Perlu meningkatkan inisiatif secara signifikan karena masih cenderung menunggu instruksi dan kurang aktif dalam melaksanakan pekerjaan.',
            ],
        ];

        return $templates[$aspekKey][$predikat] ?? '-';
    }

    /**
     * {@inheritDoc}
     */
    public function getRekapAbsensiData(int $penempatanPklId): array
    {
        return $this->absensiService->getRekapAbsensiData($penempatanPklId);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateKehadiranScore(int $penempatanPklId): int
    {
        $rekap = $this->getRekapAbsensiData($penempatanPklId);
        if ($rekap['total_hari'] <= 0) {
            return 0;
        }

        $points = ($rekap['hadir'] * 1.0)
            + ($rekap['terlambat'] * 0.90)
            + ($rekap['sangat_terlambat'] * 0.75)
            + ($rekap['sakit'] * 0.85)
            + ($rekap['izin'] * 0.70)
            + ($rekap['alpha'] * 0.0);

        $score = ($points / $rekap['total_hari']) * 100;

        return (int) min(100, max(0, round($score)));
    }
}
