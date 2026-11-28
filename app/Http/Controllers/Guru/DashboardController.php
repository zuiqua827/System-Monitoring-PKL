<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\DashboardServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Guru Dashboard Controller.
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
     * Display the Guru dashboard.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Guru|null $guru */
        $guru = $user->guru;

        if ($guru === null) {
            abort(403, 'Data guru tidak ditemukan.');
        }

        $stats = $this->dashboardService->getGuruStats($guru->id);
        $charts = $this->dashboardService->getGuruCharts($guru->id);

        return view('guru.dashboard.index', compact('stats', 'charts'));
    }
}
