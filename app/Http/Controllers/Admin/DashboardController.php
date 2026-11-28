<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\DashboardServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Super Admin Dashboard Controller.
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
     * Display the Super Admin dashboard.
     */
    public function index(): View
    {
        $stats = $this->dashboardService->getSuperAdminStats();
        $charts = $this->dashboardService->getSuperAdminCharts();
        $monitoring = $this->dashboardService->getSuperAdminMonitoring();
        $recentActivities = $this->dashboardService->getSuperAdminRecentActivity();

        return view('admin.dashboard.index', compact(
            'stats',
            'charts',
            'monitoring',
            'recentActivities'
        ));
    }
}
