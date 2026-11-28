<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Enums\AktivitasStatus;
use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\Siswa;
use App\Repositories\Interfaces\RekapBulananRepositoryInterface;
use App\Services\Interfaces\RekapBulananServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class RekapBulananService implements RekapBulananServiceInterface
{
    public function __construct(
        private readonly RekapBulananRepositoryInterface $rekapBulananRepository,
        private readonly \App\Services\Interfaces\AbsensiServiceInterface $absensiService,
    ) {}

    // ============================================================
    // REKAP BULANAN SISWA (ABSENSI & AKTIVITAS)
    // ============================================================
    // Perhitungan absensi dipusatkan pada service ini agar
    // seluruh fitur menggunakan sumber perhitungan yang konsisten,
    // seperti rekap bulanan, laporan PKL, dan penilaian.
    //
    // Alur Kerja:
    // 1. Sanitasi bulan (1-12) dan tahun.
    // 2. Mengambil Penempatan PKL aktif siswa pada rentang bulan tersebut.
    // 3. Mengambil rekap absensi melalui AbsensiService (Single Source of Truth).
    // 4. Mengambil seluruh riwayat jurnal aktivitas siswa di bulan tersebut.
    // 5. Menggabungkan data per tanggal untuk tampilan kalender/tabel monitoring.
    // ============================================================

    /**
     * {@inheritDoc}
     */
    public function getMonthlyRecap(Siswa $siswa, int $month, int $year): array
    {

        $timezone = (string) config('app.timezone', 'Asia/Jakarta');

        // Sanitize month and year
        if ($month < 1 || $month > 12) {
            $month = (int) Carbon::now($timezone)->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) Carbon::now($timezone)->year;
        }

        $startDateCarbon = Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth();
        $endDateCarbon = Carbon::createFromDate($year, $month, 1, $timezone)->endOfMonth();

        $startDate = $startDateCarbon->toDateString();
        $endDate = $endDateCarbon->toDateString();

        // 1. Resolve student's penempatan for the month and calculate attendance from single source of truth
        $penempatan = \App\Models\PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', '!=', 'dibatalkan')
            ->with(['dudi', 'periodePKL'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($sub) use ($startDate, $endDate) {
                    $sub->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $endDate);
                })->where(function ($sub) use ($startDate, $endDate) {
                    $sub->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $startDate);
                });
            })
            ->latest('id')
            ->first();

        if (!$penempatan) {
            $penempatan = \App\Models\PenempatanPKL::where('siswa_id', $siswa->id)
                ->where('status', '!=', 'dibatalkan')
                ->with(['dudi', 'periodePKL'])
                ->latest('id')
                ->first();
        }

        if ($penempatan) {
            $absensiRecap = $this->absensiService->getRekapAbsensiBulanan($penempatan, $month, $year);
        } else {
            $absensiRecap = [
                'summary' => [
                    'hadir' => 0,
                    'terlambat' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alfa' => 0,
                    'total_hari' => 0,
                ],
                'by_date' => [],
            ];
        }

        $absensiByDate = $absensiRecap['by_date'];
        $hadir = (int) ($absensiRecap['summary']['hadir'] ?? 0);
        $terlambat = (int) ($absensiRecap['summary']['terlambat'] ?? 0);
        $izin = (int) ($absensiRecap['summary']['izin'] ?? 0);
        $sakit = (int) ($absensiRecap['summary']['sakit'] ?? 0);
        $alfa = (int) ($absensiRecap['summary']['alfa'] ?? 0);
        $totalHariAbsensi = (int) ($absensiRecap['summary']['total_hari'] ?? 0);

        // 2. Fetch activity records for the month
        $aktivitasList = $this->rekapBulananRepository->getAktivitasBySiswaAndDateRange(
            $siswa->id,
            $startDate,
            $endDate
        );

        // 3. Compute Activity Summary & Group by Date
        $aktivitasByDate = [];
        foreach ($aktivitasList as $aktivitas) {
            $dateKey = $aktivitas->tanggal instanceof \DateTimeInterface
                ? $aktivitas->tanggal->format('Y-m-d')
                : Carbon::parse($aktivitas->tanggal)->format('Y-m-d');

            $aktivitasByDate[$dateKey][] = $aktivitas;
        }

        $totalAktivitas = $aktivitasList->count();

        // 4. Build Daily Recap Table Rows (Union of all dates in the month that have attendance or activity)
        $allDates = array_values(array_unique(array_merge(
            array_keys($absensiByDate),
            array_keys($aktivitasByDate)
        )));
        sort($allDates);

        $rekapHarian = [];
        foreach ($allDates as $dateStr) {
            $absInfo = $absensiByDate[$dateStr] ?? null;
            /** @var Absensi|null $abs */
            $abs = $absInfo['absensi'] ?? null;
            /** @var list<Aktivitas> $items */
            $items = $aktivitasByDate[$dateStr] ?? [];

            $carbonDate = Carbon::parse($dateStr, $timezone)->locale('id');

            // Determine status label & badge
            $statusAbsensi = '-';
            $statusRaw = null;
            $badgeColor = 'slate';
            $jamMasuk = '-';
            $jamPulang = '-';
            $statusKeterlambatan = '-';

            if ($absInfo !== null) {
                $statusRaw = strtolower((string) $absInfo['status']);
                $statusAbsensi = $absInfo['status_label'] ?? ucfirst($statusRaw);

                $badgeColor = match ($statusRaw) {
                    'hadir' => 'emerald',
                    'terlambat' => 'amber',
                    'izin' => 'blue',
                    'sakit' => 'orange',
                    'alpha', 'alfa' => 'rose',
                    default => 'slate',
                };

                if ($abs && $abs->jam_masuk) {
                    $jamMasuk = Carbon::parse($abs->jam_masuk)->format('H:i');
                }

                if ($abs && $abs->jam_keluar) {
                    $jamPulang = Carbon::parse($abs->jam_keluar)->format('H:i');
                }

                if ($statusRaw === 'terlambat') {
                    $statusKeterlambatan = ($abs?->keterangan === 'Sangat Terlambat') ? 'Sangat Terlambat' : 'Terlambat';
                } elseif ($statusRaw === 'hadir') {
                    $statusKeterlambatan = 'Tepat Waktu';
                }
            }

            // Map activities details for this date
            $mappedAktivitas = [];
            foreach ($items as $item) {
                // Waktu format
                $waktu = '-';
                if ($item->jam_mulai && $item->jam_selesai) {
                    $waktu = substr((string) $item->jam_mulai, 0, 5).' - '.substr((string) $item->jam_selesai, 0, 5);
                } elseif ($item->jam_mulai) {
                    $waktu = substr((string) $item->jam_mulai, 0, 5);
                }

                $statusEnum = AktivitasStatus::tryFrom((string) $item->status);
                $statusLabel = $statusEnum?->label() ?? ucfirst((string) $item->status);
                $statusBadgeColor = match ($statusEnum?->color() ?? '') {
                    'green' => 'emerald',
                    'yellow' => 'amber',
                    'red' => 'rose',
                    default => 'slate',
                };

                $fotoUrl = null;
                if (!empty($item->foto_kegiatan)) {
                    $fotoUrl = Storage::url($item->foto_kegiatan);
                }

                $catatan = $item->catatan_guru
                    ?? $item->catatan_reviewer
                    ?? $item->rejected_reason
                    ?? null;

                $pembimbingName = $item->validatedBy?->name
                    ?? $item->approvedBy?->name
                    ?? $item->penempatanPKL?->guru?->nama
                    ?? $item->penempatanPKL?->dudi?->nama_perusahaan
                    ?? '-';

                $mappedAktivitas[] = [
                    'id' => $item->id,
                    'tanggal' => $carbonDate->translatedFormat('d F Y'),
                    'nama_siswa' => $siswa->nama,
                    'judul' => $item->judul,
                    'deskripsi' => $item->deskripsi,
                    'waktu' => $waktu,
                    'status' => $statusLabel,
                    'status_raw' => (string) $item->status,
                    'status_color' => $statusBadgeColor,
                    'foto_url' => $fotoUrl,
                    'catatan' => $catatan,
                    'pembimbing' => $pembimbingName,
                    'hasil' => $item->hasil,
                    'kendala' => $item->kendala,
                    'solusi' => $item->solusi,
                ];
            }

            $rekapHarian[] = [
                'tanggal_raw' => $dateStr,
                'tanggal_formatted' => $carbonDate->translatedFormat('d M Y'),
                'tanggal_day' => $carbonDate->translatedFormat('d M'),
                'hari' => $carbonDate->translatedFormat('l'),
                'status_absensi' => $statusAbsensi,
                'status_absensi_raw' => $statusRaw,
                'badge_color' => $badgeColor,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang,
                'status_keterlambatan' => $statusKeterlambatan,
                'jumlah_aktivitas' => count($items),
                'aktivitas' => $mappedAktivitas,
            ];
        }

        return [
            'siswa' => $siswa,
            'month' => $month,
            'year' => $year,
            'month_name' => $startDateCarbon->locale('id')->translatedFormat('F'),
            'period_label' => $startDateCarbon->locale('id')->translatedFormat('F Y'),
            'summary_absensi' => [
                'hadir' => $hadir,
                'terlambat' => $terlambat,
                'izin' => $izin,
                'sakit' => $sakit,
                'alfa' => $alfa,
                'total_hari' => $totalHariAbsensi,
            ],
            'summary_aktivitas' => [
                'total' => $totalAktivitas,
            ],
            'rekap_harian' => $rekapHarian,
            'has_absensi' => $totalHariAbsensi > 0,
            'has_aktivitas' => $totalAktivitas > 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getEligibleSiswaForGuru(int $guruId): Collection
    {
        return $this->rekapBulananRepository->getEligibleSiswaForGuru($guruId);
    }

    /**
     * {@inheritDoc}
     */
    public function getEligibleSiswaForDudi(int $dudiId): Collection
    {
        return $this->rekapBulananRepository->getEligibleSiswaForDudi($dudiId);
    }
}
