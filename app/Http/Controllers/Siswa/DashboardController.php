<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\DashboardServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Siswa Dashboard Controller.
 *
 * Read-only dashboard. No business logic.
 * Uses DashboardService -> DashboardRepository pattern.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService,
    ) {}

    /**
     * Display the Siswa dashboard.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            abort(403, 'Data siswa tidak ditemukan.');
        }

        $siswaData = $this->dashboardService->getSiswaStats($siswa->id);

        $penempatanAktif = $siswaData['has_penempatan'] ? $siswaData['penempatan'] : null;
        $sudahCheckIn = $siswaData['sudahCheckIn'] ?? false;
        
        $stats = [
            'total_absensi' => $siswaData['totalAbsensi'] ?? 0,
            'total_aktivitas' => $siswaData['totalAktivitas'] ?? 0,
            'kehadiran_persen' => $siswaData['persentaseKehadiran'] ?? 0,
            'progress_persen' => $siswaData['progress'] ?? 0,
        ];
        
        if (isset($penempatanAktif->penilaian)) {
            $stats['nilai_akhir'] = $penempatanAktif->penilaian->nilai_akhir ?? null;
            $stats['predikat'] = $penempatanAktif->penilaian->predikat ?? null;
        }

        return view('siswa.dashboard.index', compact('penempatanAktif', 'sudahCheckIn', 'stats'));
    }
}
