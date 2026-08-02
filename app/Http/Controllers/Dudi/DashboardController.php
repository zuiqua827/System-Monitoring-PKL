<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\DashboardServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * DUDI Dashboard Controller.
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
     * Display the DUDI dashboard.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Dudi|null $dudi */
        $dudi = $user->dudi;

        if ($dudi === null) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $stats = $this->dashboardService->getDudiStats($dudi->id);
        $charts = $this->dashboardService->getDudiCharts($dudi->id);

        return view('dudi.dashboard.index', compact('stats', 'charts'));
    }
}
