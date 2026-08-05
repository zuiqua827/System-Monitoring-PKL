<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Repositories\Interfaces\DashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function countGuru(): int
    {
        return Guru::count();
    }

    public function countSiswa(): int
    {
        return Siswa::count();
    }

    public function countDudi(): int
    {
        return Dudi::count();
    }

    public function countJurusan(): int
    {
        return Jurusan::count();
    }

    public function countKelas(): int
    {
        return Kelas::count();
    }

    public function countPeriodePkl(): int
    {
        return PeriodePKL::count();
    }

    public function countPenempatanPkl(): int
    {
        return PenempatanPKL::count();
    }

    public function countAbsensiHariIni(): int
    {
        return Absensi::whereDate('tanggal', today())->count();
    }

    public function countAktivitasHariIni(): int
    {
        return Aktivitas::whereDate('tanggal', today())->count();
    }

    public function countPenilaian(): int
    {
        return Penilaian::count();
    }

    public function countPklAktif(): int
    {
        return PenempatanPKL::where('status', 'aktif')->count();
    }

    public function countPklSelesai(): int
    {
        return PenempatanPKL::where('status', 'selesai')->count();
    }

    /** @return list<array<string, int|string>> */
    public function getKehadiran7Hari(): array
    {
        $results = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $stats = Absensi::whereDate('tanggal', $date)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                    SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
                ")
                ->first();
            $results[] = [
                'tanggal' => $date->format('Y-m-d'),
                'hadir' => (int) ($stats->hadir ?? 0),
                'terlambat' => (int) ($stats->terlambat ?? 0),
                'izin' => (int) ($stats->izin ?? 0),
                'sakit' => (int) ($stats->sakit ?? 0),
                'alpha' => (int) ($stats->alpha ?? 0),
            ];
        }
        return $results;
    }

    /** @return array<string, int> */
    public function getStatusAbsensi(): array
    {
        $stats = Absensi::selectRaw("
                SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
            ")
            ->first();

        return [
            'Hadir' => (int) ($stats->hadir ?? 0),
            'Terlambat' => (int) ($stats->terlambat ?? 0),
            'Izin' => (int) ($stats->izin ?? 0),
            'Sakit' => (int) ($stats->sakit ?? 0),
            'Alpha' => (int) ($stats->alpha ?? 0),
        ];
    }

    /** @return array<int, array<string, int|string>> */
    public function getPenempatanPerDudi(): array
    {
        return PenempatanPKL::select('dudi_id', DB::raw('COUNT(*) as total'))
            ->with('dudi:id,nama_perusahaan')
            ->groupBy('dudi_id')
            ->get()
            ->map(fn ($item) => [
                'nama_perusahaan' => $item->dudi->nama_perusahaan ?? 'Unknown',
                'total' => (int) $item->getAttribute('total'),
            ])
            ->values()
            ->toArray();
    }

    /** @return array<string, int> */
    public function getPredikatPenilaian(): array
    {
        $stats = Penilaian::selectRaw("
                SUM(CASE WHEN predikat = 'A' THEN 1 ELSE 0 END) as A,
                SUM(CASE WHEN predikat = 'B' THEN 1 ELSE 0 END) as B,
                SUM(CASE WHEN predikat = 'C' THEN 1 ELSE 0 END) as C,
                SUM(CASE WHEN predikat = 'D' THEN 1 ELSE 0 END) as D,
                SUM(CASE WHEN predikat = 'E' THEN 1 ELSE 0 END) as E
            ")
            ->where('status', 'final')
            ->first();

        return [
            'A' => (int) ($stats->A ?? 0),
            'B' => (int) ($stats->B ?? 0),
            'C' => (int) ($stats->C ?? 0),
            'D' => (int) ($stats->D ?? 0),
            'E' => (int) ($stats->E ?? 0),
        ];
    }

    /** @return list<array<string, int|string>> */
    public function getAktivitasMingguan(): array
    {
        $results = [];
        $startOfWeek = now()->startOfWeek();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = Aktivitas::whereDate('tanggal', $date)->count();
            $results[] = [
                'tanggal' => $date->format('Y-m-d'),
                'total' => $count,
            ];
        }
        return $results;
    }

    public function getBelumCheckIn(): int
    {
        $activePenempatanIds = PenempatanPKL::where('status', 'aktif')->pluck('id');
        $checkedInToday = Absensi::whereIn('penempatan_pkl_id', $activePenempatanIds)
            ->whereDate('tanggal', today())
            ->whereNotNull('jam_masuk')
            ->pluck('penempatan_pkl_id');

        return $activePenempatanIds->diff($checkedInToday)->count();
    }

    public function getBelumCheckOut(): int
    {
        return Absensi::whereDate('tanggal', today())
            ->whereNotNull('jam_masuk')
            ->whereNull('jam_keluar')
            ->count();
    }

    public function getAktivitasMenungguValidasi(): int
    {
        return Aktivitas::where('status', 'menunggu_validasi')->count();
    }

    public function getPenilaianDraft(): int
    {
        return Penilaian::where('status', 'draft')->count();
    }

    public function getPklAkanBerakhir(): int
    {
        return PenempatanPKL::where('status', 'aktif')
            ->whereBetween('tanggal_selesai', [today(), today()->addDays(7)])
            ->count();
    }

    public function getPklTerlambatMulai(): int
    {
        return PenempatanPKL::where('status', 'pending')
            ->where('tanggal_mulai', '<', today())
            ->count();
    }

    /** @return list<array<string, string>> */
    public function getRecentActivity(int $limit = 10): array
    {
        $activities = [];

        // Recent check-ins
        $checkIns = Absensi::whereDate('tanggal', today())
            ->whereNotNull('jam_masuk')
            ->with('penempatanPKL.siswa')
            ->latest('jam_masuk')
            ->take($limit)
            ->get()
            ->map(fn ($a) => [
                'waktu' => $a->jam_masuk->format('H:i'),
                'deskripsi' => ($a->penempatanPKL->siswa->nama ?? 'Siswa') . ' Check In',
                'tipe' => 'checkin',
                'user' => $a->penempatanPKL->siswa->nama ?? '-',
            ]);

        $activities = array_merge($activities, $checkIns->toArray());

        // Recent check-outs
        $checkOuts = Absensi::whereDate('tanggal', today())
            ->whereNotNull('jam_keluar')
            ->with('penempatanPKL.siswa')
            ->latest('jam_keluar')
            ->take($limit)
            ->get()
            ->map(fn ($a) => [
                'waktu' => $a->jam_keluar->format('H:i'),
                'deskripsi' => ($a->penempatanPKL->siswa->nama ?? 'Siswa') . ' Check Out',
                'tipe' => 'checkout',
                'user' => $a->penempatanPKL->siswa->nama ?? '-',
            ]);

        $activities = array_merge($activities, $checkOuts->toArray());

        // Recent aktivitas
        $aktivitas = Aktivitas::latest()
            ->take($limit)
            ->with('penempatanPKL.siswa')
            ->get()
            ->map(fn ($a) => [
                'waktu' => $a->created_at->format('H:i'),
                'deskripsi' => ($a->penempatanPKL->siswa->nama ?? 'Siswa') . ' membuat aktivitas: ' . $a->judul,
                'tipe' => 'aktivitas',
                'user' => $a->penempatanPKL->siswa->nama ?? '-',
            ]);

        $activities = array_merge($activities, $aktivitas->toArray());

        // Recent penilaian
        $penilaian = Penilaian::latest()
            ->take($limit)
            ->with('penempatanPKL.siswa', 'dinilaiOleh')
            ->get()
            ->map(fn ($p) => [
                'waktu' => $p->created_at->format('H:i'),
                'deskripsi' => 'Penilaian ' . ($p->penempatanPKL->siswa->nama ?? 'Siswa') . ' oleh ' . ($p->dinilaiOleh->name ?? 'Guru'),
                'tipe' => 'penilaian',
                'user' => $p->dinilaiOleh->name ?? 'Guru',
            ]);

        $activities = array_merge($activities, $penilaian->toArray());

        // Sort by waktu descending
        usort($activities, fn ($a, $b) => strcmp($b['waktu'], $a['waktu']));

        return array_slice($activities, 0, $limit);
    }

    public function countSiswaBimbingan(int $guruId): int
    {
        return PenempatanPKL::where('guru_id', $guruId)
            ->where('status', 'aktif')
            ->count();
    }

    public function countAbsensiHariIniByGuru(int $guruId): int
    {
        return Absensi::whereDate('tanggal', today())
            ->whereHas('penempatanPKL', fn ($q) => $q->where('guru_id', $guruId))
            ->count();
    }

    public function countAktivitasMenungguValidasiByGuru(int $guruId): int
    {
        return Aktivitas::where('status', 'menunggu_validasi')
            ->whereHas('penempatanPKL', fn ($q) => $q->where('guru_id', $guruId))
            ->count();
    }

    public function countPenilaianDraftByGuru(int $guruId): int
    {
        return Penilaian::where('status', 'draft')
            ->whereHas('penempatanPKL', fn ($q) => $q->where('guru_id', $guruId))
            ->count();
    }

    /** @return list<array<string, int|string>> */
    public function getKehadiran7HariByGuru(int $guruId): array
    {
        $results = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $stats = Absensi::whereDate('tanggal', $date)
                ->whereHas('penempatanPKL', fn ($q) => $q->where('guru_id', $guruId))
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat
                ")
                ->first();
            $results[] = [
                'tanggal' => $date->format('Y-m-d'),
                'hadir' => (int) ($stats->hadir ?? 0),
                'terlambat' => (int) ($stats->terlambat ?? 0),
            ];
        }
        return $results;
    }

    /** @return array<string, int> */
    public function getStatusAktivitasByGuru(int $guruId): array
    {
        $stats = Aktivitas::whereHas('penempatanPKL', fn ($q) => $q->where('guru_id', $guruId))
            ->selectRaw("
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'menunggu_validasi' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as disetujui,
                SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
            ")
            ->first();

        return [
            'Draft' => (int) ($stats->draft ?? 0),
            'Menunggu' => (int) ($stats->menunggu ?? 0),
            'Disetujui' => (int) ($stats->disetujui ?? 0),
            'Ditolak' => (int) ($stats->ditolak ?? 0),
        ];
    }

    /** @return array<int, array<string, float|string>> */
    public function getNilaiSiswaByGuru(int $guruId): array
    {
        return Penilaian::where('status', 'final')
            ->whereHas('penempatanPKL', fn ($q) => $q->where('guru_id', $guruId))
            ->with('penempatanPKL.siswa')
            ->get()
            ->map(fn ($p) => [
                'nama_siswa' => $p->penempatanPKL->siswa->nama ?? 'Unknown',
                'nilai_akhir' => (float) ($p->nilai_akhir ?? 0),
                'predikat' => $p->predikat ?? '-',
            ])
            ->values()
            ->toArray();
    }

    public function countSiswaPklByDudi(int $dudiId): int
    {
        return PenempatanPKL::where('dudi_id', $dudiId)
            ->where('status', 'aktif')
            ->count();
    }

    public function countAbsensiHariIniByDudi(int $dudiId): int
    {
        return Absensi::whereDate('tanggal', today())
            ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
            ->count();
    }

    public function countAktivitasHariIniByDudi(int $dudiId): int
    {
        return Aktivitas::whereDate('tanggal', today())
            ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
            ->count();
    }

    /** @return list<array<string, int|string>> */
    public function getAbsensi7HariByDudi(int $dudiId): array
    {
        $results = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $stats = Absensi::whereDate('tanggal', $date)
                ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'hadir' OR status = 'terlambat' THEN 1 ELSE 0 END) as hadir
                ")
                ->first();
            $results[] = [
                'tanggal' => $date->format('Y-m-d'),
                'hadir' => (int) ($stats->hadir ?? 0),
                'total' => (int) ($stats->total ?? 0),
            ];
        }
        return $results;
    }

    /** @return list<array<string, int|string>> */
    public function getAktivitas7HariByDudi(int $dudiId): array
    {
        $results = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $count = Aktivitas::whereDate('tanggal', $date)
                ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
                ->count();
            $results[] = [
                'tanggal' => $date->format('Y-m-d'),
                'total' => $count,
            ];
        }
        return $results;
    }

    public function countAktivitasMenungguValidasiByDudi(int $dudiId): int
    {
        return Aktivitas::where('status', 'menunggu_validasi')
            ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
            ->count();
    }

    /** @return list<array<string, mixed>> */
    public function getRecentSiswaByDudi(int $dudiId, int $limit = 5): array
    {
        return PenempatanPKL::where('dudi_id', $dudiId)
            ->where('status', 'aktif')
            ->with(['siswa.kelas.jurusan'])
            ->latest('created_at')
            ->take($limit)
            ->get()
            ->toArray();
    }

    /** @return list<array<string, string>> */
    public function getRecentActivityByDudi(int $dudiId, int $limit = 10): array
    {
        $activities = [];

        // Recent check-ins
        $checkIns = Absensi::whereDate('tanggal', today())
            ->whereNotNull('jam_masuk')
            ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
            ->with('penempatanPKL.siswa')
            ->latest('jam_masuk')
            ->take($limit)
            ->get()
            ->map(fn ($a) => [
                'waktu' => $a->jam_masuk->format('H:i'),
                'deskripsi' => ($a->penempatanPKL->siswa->nama ?? 'Siswa') . ' Check In',
                'tipe' => 'checkin',
                'user' => $a->penempatanPKL->siswa->nama ?? '-',
            ]);

        $activities = array_merge($activities, $checkIns->toArray());

        // Recent check-outs
        $checkOuts = Absensi::whereDate('tanggal', today())
            ->whereNotNull('jam_keluar')
            ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
            ->with('penempatanPKL.siswa')
            ->latest('jam_keluar')
            ->take($limit)
            ->get()
            ->map(fn ($a) => [
                'waktu' => $a->jam_keluar->format('H:i'),
                'deskripsi' => ($a->penempatanPKL->siswa->nama ?? 'Siswa') . ' Check Out',
                'tipe' => 'checkout',
                'user' => $a->penempatanPKL->siswa->nama ?? '-',
            ]);

        $activities = array_merge($activities, $checkOuts->toArray());

        // Recent aktivitas
        $aktivitas = Aktivitas::latest()
            ->take($limit)
            ->whereHas('penempatanPKL', fn ($q) => $q->where('dudi_id', $dudiId))
            ->with('penempatanPKL.siswa')
            ->get()
            ->map(fn ($a) => [
                'waktu' => $a->created_at->format('H:i'),
                'deskripsi' => ($a->penempatanPKL->siswa->nama ?? 'Siswa') . ' membuat aktivitas: ' . $a->judul,
                'tipe' => 'aktivitas',
                'user' => $a->penempatanPKL->siswa->nama ?? '-',
            ]);

        $activities = array_merge($activities, $aktivitas->toArray());

        // Sort by waktu descending
        usort($activities, fn ($a, $b) => strcmp($b['waktu'], $a['waktu']));

        return array_slice($activities, 0, $limit);
    }

    /** @return array<string, mixed> */
    public function getSiswaDashboardData(int $siswaId): array
    {
        $penempatan = PenempatanPKL::where('siswa_id', $siswaId)
            ->with(['dudi', 'periodePKL', 'penilaian'])
            ->first();

        if (!$penempatan) {
            return [
                'has_penempatan' => false,
                'penempatan' => null,
            ];
        }

        $todayAbsensi = Absensi::where('penempatan_pkl_id', $penempatan->id)
            ->whereDate('tanggal', today())
            ->first();

        $aktivitasHariIni = Aktivitas::where('penempatan_pkl_id', $penempatan->id)
            ->whereDate('tanggal', today())
            ->count();

        $totalAktivitas = Aktivitas::where('penempatan_pkl_id', $penempatan->id)->count();

        $totalAbsensi = Absensi::where('penempatan_pkl_id', $penempatan->id)->count();
        $hadirCount = Absensi::where('penempatan_pkl_id', $penempatan->id)
            ->whereIn('status', ['hadir', 'terlambat'])
            ->count();
        $persentaseKehadiran = $totalAbsensi > 0 ? round(($hadirCount / $totalAbsensi) * 100, 1) : 0;

        // Progress PKL
        $tanggalMulai = $penempatan->tanggal_mulai;
        $tanggalSelesai = $penempatan->tanggal_selesai;
        $progress = 0;
        $hariBerjalan = 0;
        $totalHari = 0;
        if ($tanggalMulai && $tanggalSelesai) {
            $totalHari = $tanggalMulai->diffInDays($tanggalSelesai) ?: 1;
            $hariBerjalan = max(0, $tanggalMulai->diffInDays(now(), false));
            $progress = min(100, round(($hariBerjalan / $totalHari) * 100, 1));
        }

        return [
            'has_penempatan' => true,
            'penempatan' => $penempatan,
            'todayAbsensi' => $todayAbsensi,
            'sudahCheckIn' => $todayAbsensi && $todayAbsensi->jam_masuk !== null,
            'sudahCheckOut' => $todayAbsensi && $todayAbsensi->jam_keluar !== null,
            'aktivitasHariIni' => $aktivitasHariIni,
            'totalAktivitas' => $totalAktivitas,
            'totalAbsensi' => $totalAbsensi,
            'persentaseKehadiran' => $persentaseKehadiran,
            'progress' => $progress,
            'hariBerjalan' => $hariBerjalan,
            'totalHari' => $totalHari,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ];
    }
}
