<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin controller for the "Sinkronisasi SiPintu" feature.
 *
 * Thin controller: delegates all business logic to SipintuSyncService.
 * GET index method never executes external HTTP requests, ensuring fast,
 * non-blocking rendering without execution time limits.
 */
class SipintuSyncController extends Controller
{
    public function __construct(
        private readonly SipintuSyncServiceInterface $syncService,
    ) {}

    /**
     * Display the SiPintu synchronization dashboard.
     * Uses ONLY local DB counts and logs to render instantly.
     */
    public function index(): View
    {
        $data = $this->syncService->getDashboardData();
        $connResult = session('connectionTestResult');

        return view('admin.sipintu-sync.index', [
            'connectionStatus' => is_array($connResult)
                ? ($connResult['success'] ? 'connected' : (($connResult['error_type'] ?? null) === 'configuration' ? 'not_configured' : 'error'))
                : $data['connection_status'],
            'connectionMessage' => is_array($connResult) ? ($connResult['message'] ?? $data['connection_message']) : $data['connection_message'],
            'connectionDetail' => is_array($connResult) ? ($connResult['detail'] ?? null) : ($data['connection_detail'] ?? null),
            'connectionTroubleshooting' => is_array($connResult) ? ($connResult['troubleshooting'] ?? null) : ($data['connection_troubleshooting'] ?? null),
            'lastSync' => $data['last_sync'],
            'sipintuStudentCount' => $data['sipintu_student_count'],
            'sipintuTeacherCount' => $data['sipintu_teacher_count'],
            'localStudentCount' => $data['local_student_count'],
            'localTeacherCount' => $data['local_teacher_count'],
            'classroomMappingCount' => $data['classroom_mapping_count'],
            'history' => $data['history'],
            'preview' => null,
        ]);
    }

    /**
     * Run a READ-ONLY preview / dry-run of the sync when requested by user.
     *
     * Does NOT modify any data. Only classifies the upcoming sync into the
     * 7 categories. Returns the same dashboard view with the preview.
     */
    public function preview(): View
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        $data = $this->syncService->getDashboardData();
        $preview = $this->syncService->preview();

        return view('admin.sipintu-sync.index', [
            'connectionStatus' => $data['connection_status'],
            'connectionMessage' => $data['connection_message'],
            'connectionDetail' => $data['connection_detail'] ?? null,
            'connectionTroubleshooting' => $data['connection_troubleshooting'] ?? null,
            'lastSync' => $data['last_sync'],
            'sipintuStudentCount' => $data['sipintu_student_count'],
            'sipintuTeacherCount' => $data['sipintu_teacher_count'],
            'localStudentCount' => $data['local_student_count'],
            'localTeacherCount' => $data['local_teacher_count'],
            'classroomMappingCount' => $data['classroom_mapping_count'],
            'history' => $data['history'],
            'preview' => [
                'success' => $preview['success'],
                'message' => $preview['message'],
                'students' => $preview['students'],
                'teachers' => $preview['teachers'],
                'duration_ms' => $preview['duration_ms'],
            ],
        ]);
    }

    /**
     * Run a live connection test without starting a synchronization.
     */
    public function testConnection(): RedirectResponse
    {
        $result = $this->syncService->testConnection();
        $message = $result['message'];

        if (! empty($result['detail'])) {
            $message .= ' — Rincian: '.$result['detail'];
        } elseif ($result['http_status'] !== null) {
            $message .= ' (HTTP '.$result['http_status'].').';
        }

        if (! empty($result['troubleshooting'])) {
            $message .= ' Solusi: '.$result['troubleshooting'];
        }

        return redirect()
            ->route('admin.sipintu-sync.index')
            ->with($result['success'] ? 'success' : 'error', $message)
            ->with('connectionTestResult', $result);
    }

    /**
     * Trigger a manual synchronization.
     */
    public function sync(Request $request): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '512M');

        /** @var User $admin */
        $admin = $request->user();

        $result = $this->syncService->runSync($admin);

        if ($result['success']) {
            return redirect()
                ->route('admin.sipintu-sync.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('admin.sipintu-sync.index')
            ->with('error', $result['message']);
    }
}
