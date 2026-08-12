<?php

declare(strict_types=1);

namespace App\Repositories\Laporan;

use App\Models\PenempatanPKL;
use App\Repositories\Laporan\Interfaces\LaporanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LaporanRepository implements LaporanRepositoryInterface
{
    public function getPenempatanSummary(array $filters): LengthAwarePaginator|Collection
    {
        $query = PenempatanPKL::query()
            ->with([
                'siswa.kelas.jurusan',
                'guru',
                'dudi',
                'periodePKL',
                'penilaian'
            ])
            ->withCount(['absensi', 'aktivitas']);

        if (!empty($filters['guru_id'])) {
            $query->where('guru_id', $filters['guru_id']);
        }

        if (!empty($filters['dudi_id'])) {
            $query->where('dudi_id', $filters['dudi_id']);
        }

        if (!empty($filters['periode_id'])) {
            $query->where('periode_pkl_id', $filters['periode_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['jurusan_id'])) {
            $query->whereHas('siswa.kelas', function ($q) use ($filters) {
                $q->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['kelas_id'])) {
            $query->whereHas('siswa', function ($q) use ($filters) {
                $q->where('class_id', $filters['kelas_id']);
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        
        return $query->paginate($perPage);
    }

    public function getSiswaReport(array $filters): LengthAwarePaginator|Collection
    {
        return $this->getPenempatanSummary($filters);
    }

    public function getSiswaSummaryStats(array $filters): array
    {
        $query = PenempatanPKL::query();

        if (!empty($filters['guru_id'])) {
            $query->where('guru_id', $filters['guru_id']);
        }

        if (!empty($filters['dudi_id'])) {
            $query->where('dudi_id', $filters['dudi_id']);
        }

        if (!empty($filters['periode_id'])) {
            $query->where('periode_pkl_id', $filters['periode_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['jurusan_id'])) {
            $query->whereHas('siswa.kelas', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['kelas_id'])) {
            $query->whereHas('siswa', function ($query) use ($filters) {
                $query->where('class_id', $filters['kelas_id']);
            });
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_siswa,
            SUM(CASE WHEN status = "aktif" THEN 1 ELSE 0 END) as aktif,
            SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai,
            SUM(CASE WHEN status = "dibatalkan" THEN 1 ELSE 0 END) as dibatalkan
        ')->first();

        return [
            'total_siswa' => (int) ($stats->total_siswa ?? 0),
            'aktif' => (int) ($stats->aktif ?? 0),
            'selesai' => (int) ($stats->selesai ?? 0),
            'dibatalkan' => (int) ($stats->dibatalkan ?? 0),
        ];
    }

    public function getAbsensiReport(array $filters): LengthAwarePaginator|Collection
    {
        $query = \App\Models\Absensi::query()
            ->with([
                'penempatanPKL.siswa.kelas.jurusan',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
            ]);

        $this->applyAbsensiFilters($query, $filters);

        $query->orderBy('tanggal', 'desc');

        $perPage = (int) ($filters['per_page'] ?? 15);
        
        return $query->paginate($perPage);
    }

    public function getAbsensiSummaryStats(array $filters): array
    {
        $query = \App\Models\Absensi::query();

        $this->applyAbsensiFilters($query, $filters);

        $stats = $query->selectRaw('
            COUNT(DISTINCT penempatan_pkl_id) as total_siswa,
            COUNT(*) as total_absensi,
            SUM(CASE WHEN status = "hadir" THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN status = "terlambat" THEN 1 ELSE 0 END) as terlambat,
            SUM(CASE WHEN status = "izin" THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status = "sakit" THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN status = "alpha" THEN 1 ELSE 0 END) as alpha
        ')->first();

        if (!$stats) {
            return [
                'total_siswa' => 0, 'total_absensi' => 0, 'hadir' => 0,
                'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0,
            ];
        }

        return [
            'total_siswa' => (int) $stats->total_siswa,
            'total_absensi' => (int) $stats->total_absensi,
            'hadir' => (int) $stats->hadir,
            'terlambat' => (int) $stats->terlambat,
            'izin' => (int) $stats->izin,
            'sakit' => (int) $stats->sakit,
            'alpha' => (int) $stats->alpha,
        ];
    }

    public function getAbsensiExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = \App\Models\Absensi::query()
            ->with([
                'penempatanPKL.siswa.kelas.jurusan',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
            ]);

        $this->applyAbsensiFilters($query, $filters);

        return $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id');
    }

    public function getSiswaExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = \App\Models\PenempatanPKL::query()
            ->with([
                'siswa.kelas.jurusan',
                'guru',
                'dudi',
                'periodePKL',
            ]);

        $this->applySiswaFilters($query, $filters);

        $query->orderBy('id');

        return $query;
    }

    public function getSiswaReportForPdf(array $filters, int $limit): Collection
    {
        $query = PenempatanPKL::query()
            ->with([
                'siswa.kelas.jurusan',
                'guru',
                'dudi',
                'periodePKL',
            ]);

        if (!empty($filters['guru_id'])) {
            $query->where('guru_id', $filters['guru_id']);
        }

        if (!empty($filters['dudi_id'])) {
            $query->where('dudi_id', $filters['dudi_id']);
        }

        if (!empty($filters['periode_id'])) {
            $query->where('periode_pkl_id', $filters['periode_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['jurusan_id'])) {
            $query->whereHas('siswa.kelas', function ($query) use ($filters) {
                $query->where('jurusan_id', $filters['jurusan_id']);
            });
        }

        if (!empty($filters['kelas_id'])) {
            $query->whereHas('siswa', function ($query) use ($filters) {
                $query->where('class_id', $filters['kelas_id']);
            });
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->whereHas('siswa', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            });
        }

        return $query->orderBy('id')->limit($limit)->get();
    }

    public function getSiswaPdfFilterSummary(array $filters): array
    {
        $summary = [];

        if (!empty($filters['periode_id'])) {
            $periode = \App\Models\PeriodePKL::query()->find($filters['periode_id']);
            $summary[] = [
                'label' => 'Periode PKL',
                'value' => $periode?->nama ?? 'ID #'.$filters['periode_id'],
            ];
        }

        if (!empty($filters['jurusan_id'])) {
            $jurusan = \App\Models\Jurusan::query()->find($filters['jurusan_id']);
            $summary[] = [
                'label' => 'Jurusan',
                'value' => $jurusan?->nama ?? 'ID #'.$filters['jurusan_id'],
            ];
        }

        if (!empty($filters['kelas_id'])) {
            $kelas = \App\Models\Kelas::query()->find($filters['kelas_id']);
            $summary[] = [
                'label' => 'Kelas',
                'value' => $kelas?->nama ?? 'ID #'.$filters['kelas_id'],
            ];
        }

        if (!empty($filters['guru_id'])) {
            $guru = \App\Models\Guru::query()->find($filters['guru_id']);
            $summary[] = [
                'label' => 'Guru Pembimbing',
                'value' => $guru?->nama ?? 'ID #'.$filters['guru_id'],
            ];
        }

        if (!empty($filters['dudi_id'])) {
            $dudi = \App\Models\Dudi::query()->find($filters['dudi_id']);
            $summary[] = [
                'label' => 'DUDI',
                'value' => $dudi?->nama_perusahaan ?? 'ID #'.$filters['dudi_id'],
            ];
        }

        if (!empty($filters['status'])) {
            $summary[] = [
                'label' => 'Status PKL',
                'value' => ucfirst((string) $filters['status']),
            ];
        }

        if (!empty($filters['search'])) {
            $summary[] = [
                'label' => 'Pencarian',
                'value' => trim((string) $filters['search']),
            ];
        }

        return $summary === []
            ? [['label' => 'Filter', 'value' => 'Semua data']]
            : $summary;
    }

    private function applySiswaFilters($query, array $filters): void
    {
        if (!empty($filters['guru_id'])) {
            $query->where('guru_id', $filters['guru_id']);
        }
        if (!empty($filters['dudi_id'])) {
            $query->where('dudi_id', $filters['dudi_id']);
        }
        if (!empty($filters['periode_id'])) {
            $query->where('periode_pkl_id', $filters['periode_id']);
        }
        if (!empty($filters['jurusan_id'])) {
            $query->whereHas('siswa.kelas', function ($q2) use ($filters) {
                $q2->where('jurusan_id', $filters['jurusan_id']);
            });
        }
        if (!empty($filters['kelas_id'])) {
            $query->whereHas('siswa', function ($q2) use ($filters) {
                $q2->where('class_id', $filters['kelas_id']);
            });
        }
    }

    private function applyAbsensiFilters($query, array $filters): void
    {
        $query->whereHas('penempatanPKL', function ($q) use ($filters) {
            if (!empty($filters['guru_id'])) {
                $q->where('guru_id', $filters['guru_id']);
            }
            if (!empty($filters['dudi_id'])) {
                $q->where('dudi_id', $filters['dudi_id']);
            }
            if (!empty($filters['periode_id'])) {
                $q->where('periode_pkl_id', $filters['periode_id']);
            }
            if (!empty($filters['jurusan_id'])) {
                $q->whereHas('siswa.kelas', function ($q2) use ($filters) {
                    $q2->where('jurusan_id', $filters['jurusan_id']);
                });
            }
            if (!empty($filters['kelas_id'])) {
                $q->whereHas('siswa', function ($q2) use ($filters) {
                    $q2->where('class_id', $filters['kelas_id']);
                });
            }
        });

        if (!empty($filters['tanggal_mulai'])) {
            $query->whereDate('tanggal', '>=', $filters['tanggal_mulai']);
        }
        if (!empty($filters['tanggal_akhir'])) {
            $query->whereDate('tanggal', '<=', $filters['tanggal_akhir']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    public function getAktivitasReport(array $filters): LengthAwarePaginator|Collection
    {
        $query = \App\Models\Aktivitas::query()
            ->with([
                'penempatanPKL.siswa.kelas.jurusan',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
            ]);

        $this->applyAktivitasFilters($query, $filters);

        $query->orderBy('tanggal', 'desc');

        $perPage = (int) ($filters['per_page'] ?? 15);
        
        return $query->paginate($perPage);
    }

    public function getAktivitasSummaryStats(array $filters): array
    {
        $query = \App\Models\Aktivitas::query();

        $this->applyAktivitasFilters($query, $filters);

        $stats = $query->selectRaw('
            COUNT(DISTINCT penempatan_pkl_id) as total_siswa,
            COUNT(*) as total_aktivitas,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected
        ')->first();

        if (!$stats) {
            return [
                'total_siswa' => 0, 'total_aktivitas' => 0,
                'pending' => 0, 'approved' => 0, 'rejected' => 0,
            ];
        }

        return [
            'total_siswa' => (int) $stats->total_siswa,
            'total_aktivitas' => (int) $stats->total_aktivitas,
            'pending' => (int) $stats->pending,
            'approved' => (int) $stats->approved,
            'rejected' => (int) $stats->rejected,
        ];
    }

    private function applyAktivitasFilters($query, array $filters): void
    {
        $query->whereHas('penempatanPKL', function ($q) use ($filters) {
            if (!empty($filters['guru_id'])) {
                $q->where('guru_id', $filters['guru_id']);
            }
            if (!empty($filters['dudi_id'])) {
                $q->where('dudi_id', $filters['dudi_id']);
            }
            if (!empty($filters['periode_id'])) {
                $q->where('periode_pkl_id', $filters['periode_id']);
            }
            if (!empty($filters['jurusan_id'])) {
                $q->whereHas('siswa.kelas', function ($q2) use ($filters) {
                    $q2->where('jurusan_id', $filters['jurusan_id']);
                });
            }
            if (!empty($filters['kelas_id'])) {
                $q->whereHas('siswa', function ($q2) use ($filters) {
                    $q2->where('class_id', $filters['kelas_id']);
                });
            }
        });

        if (!empty($filters['tanggal_mulai'])) {
            $query->whereDate('tanggal', '>=', $filters['tanggal_mulai']);
        }
        if (!empty($filters['tanggal_akhir'])) {
            $query->whereDate('tanggal', '<=', $filters['tanggal_akhir']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    public function getPenilaianReport(array $filters): LengthAwarePaginator|Collection
    {
        $query = \App\Models\Penilaian::query()
            ->with([
                'penempatanPKL.siswa.kelas.jurusan',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
            ]);

        $this->applyPenilaianFilters($query, $filters);

        $query->orderBy('tanggal_penilaian', 'desc');

        $perPage = (int) ($filters['per_page'] ?? 15);
        
        return $query->paginate($perPage);
    }

    public function getPenilaianSummaryStats(array $filters): array
    {
        $query = \App\Models\Penilaian::query();

        $this->applyPenilaianFilters($query, $filters);

        $stats = $query->selectRaw('
            COUNT(DISTINCT penempatan_pkl_id) as total_siswa,
            COUNT(*) as total_penilaian,
            SUM(CASE WHEN status = "final" THEN 1 ELSE 0 END) as final,
            SUM(CASE WHEN status != "final" THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN predikat = "A" THEN 1 ELSE 0 END) as predikat_a,
            SUM(CASE WHEN predikat = "B" THEN 1 ELSE 0 END) as predikat_b,
            SUM(CASE WHEN predikat = "C" THEN 1 ELSE 0 END) as predikat_c,
            SUM(CASE WHEN predikat = "D" THEN 1 ELSE 0 END) as predikat_d,
            AVG(nilai_akhir) as rata_rata_nilai
        ')->first();

        if (!$stats) {
            return [
                'total_siswa' => 0, 'total_penilaian' => 0,
                'final' => 0, 'draft' => 0, 
                'predikat_a' => 0, 'predikat_b' => 0, 'predikat_c' => 0, 'predikat_d' => 0,
                'rata_rata_nilai' => 0
            ];
        }

        return [
            'total_siswa' => (int) $stats->total_siswa,
            'total_penilaian' => (int) $stats->total_penilaian,
            'final' => (int) $stats->final,
            'draft' => (int) $stats->draft,
            'predikat_a' => (int) $stats->predikat_a,
            'predikat_b' => (int) $stats->predikat_b,
            'predikat_c' => (int) $stats->predikat_c,
            'predikat_d' => (int) $stats->predikat_d,
            'rata_rata_nilai' => (float) $stats->rata_rata_nilai,
        ];
    }

    private function applyPenilaianFilters($query, array $filters): void
    {
        $query->whereHas('penempatanPKL', function ($q) use ($filters) {
            if (!empty($filters['guru_id'])) {
                $q->where('guru_id', $filters['guru_id']);
            }
            if (!empty($filters['dudi_id'])) {
                $q->where('dudi_id', $filters['dudi_id']);
            }
            if (!empty($filters['periode_id'])) {
                $q->where('periode_pkl_id', $filters['periode_id']);
            }
            if (!empty($filters['jurusan_id'])) {
                $q->whereHas('siswa.kelas', function ($q2) use ($filters) {
                    $q2->where('jurusan_id', $filters['jurusan_id']);
                });
            }
            if (!empty($filters['kelas_id'])) {
                $q->whereHas('siswa', function ($q2) use ($filters) {
                    $q2->where('class_id', $filters['kelas_id']);
                });
            }
        });

        if (!empty($filters['tanggal_mulai'])) {
            $query->whereDate('tanggal_penilaian', '>=', $filters['tanggal_mulai']);
        }
        if (!empty($filters['tanggal_akhir'])) {
            $query->whereDate('tanggal_penilaian', '<=', $filters['tanggal_akhir']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }
}
