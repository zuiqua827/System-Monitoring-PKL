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

        return view('siswa.dashboard.index', compact('siswaData'));
    }
}
