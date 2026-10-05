<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Services\Interfaces\RekapBulananServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RekapBulananController extends Controller
{
    public function __construct(
        private readonly RekapBulananServiceInterface $rekapBulananService,
    ) {}

    /**
     * Display monthly recap page for a teacher's guided students.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $guru = $user?->guru;

        if ($guru === null) {
            abort(403, 'Akses ditolak. Profil guru tidak ditemukan.');
        }

        $siswaList = $this->rekapBulananService->getEligibleSiswaForGuru($guru->id);

        $siswaId = $request->query('siswa_id');
        $selectedSiswa = null;

        if ($siswaId !== null && $siswaId !== '') {
            $selectedSiswa = Siswa::query()->find($siswaId);
            if ($selectedSiswa === null) {
                abort(404, 'Data siswa tidak ditemukan.');
            }
            $this->authorize('viewRekapBulanan', $selectedSiswa);
        } else {
            $selectedSiswa = $siswaList->first();
        }

        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($timezone);

        $bulan = (int) $request->query('bulan', $now->month);
        $tahun = (int) $request->query('tahun', $now->year);

        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) $now->month;
        }
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) $now->year;
        }

        $rekap = null;
        if ($selectedSiswa !== null) {
            $rekap = $this->rekapBulananService->getMonthlyRecap($selectedSiswa, $bulan, $tahun);
        }

        return view('guru.rekap-bulanan.index', [
            'siswaList' => $siswaList,
            'selectedSiswa' => $selectedSiswa,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rekap' => $rekap,
        ]);
    }

    /**
     * Display monthly recap for a specific student.
     */
    public function show(Request $request, Siswa $siswa): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $guru = $user?->guru;

        if ($guru === null) {
            abort(403, 'Akses ditolak. Profil guru tidak ditemukan.');
        }

        $this->authorize('viewRekapBulanan', $siswa);

        $siswaList = $this->rekapBulananService->getEligibleSiswaForGuru($guru->id);

        $timezone = (string) config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($timezone);

        $bulan = (int) $request->query('bulan', $now->month);
        $tahun = (int) $request->query('tahun', $now->year);

        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) $now->month;
        }
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) $now->year;
        }

        $rekap = $this->rekapBulananService->getMonthlyRecap($siswa, $bulan, $tahun);

        return view('guru.rekap-bulanan.index', [
            'siswaList' => $siswaList,
            'selectedSiswa' => $siswa,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rekap' => $rekap,
        ]);
    }
}
