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
        $conn = $this->resolveConnectionState($data, session('connectionTestResult'));

        return view('admin.sipintu-sync.index', [
            'connectionStatus' => $conn['status'],
            'connectionMessage' => $conn['message'],
            'connectionDetail' => $conn['detail'],
            'connectionTroubleshooting' => $conn['troubleshooting'],
            'endpointsDiagnosis' => $conn['endpoints'],
            'lastSync' => $data['last_sync'],
            'sipintuStudentCount' => $data['sipintu_student_count'],
            'sipintuTeacherCount' => $data['sipintu_teacher_count'],
            'sipintuDudiCount' => $data['sipintu_dudi_count'],
            'localStudentCount' => $data['local_student_count'],
            'localTeacherCount' => $data['local_teacher_count'],
            'localDudiCount' => $data['local_dudi_count'],
            'classroomMappingCount' => $data['classroom_mapping_count'],
            'history' => $data['history'],
            'preview' => null,
        ]);
    }

    /**
     * Resolve granular connection status and diagnosis details.
     *
     * @param  array<string, mixed>  $dashboardData
     * @param  array<string, mixed>|null  $sessionResult
     * @return array<string, mixed>
     */
    private function resolveConnectionState(array $dashboardData, ?array $sessionResult): array
    {
        $cachedResult = \Illuminate\Support\Facades\Cache::get('sipintu_live_diagnostics');
        $effective = $sessionResult ?? $cachedResult;

        if (is_array($effective) && isset($effective['success'])) {
            $isSuccess = (bool) $effective['success'];
            $errorType = $effective['error_type'] ?? null;
            $hasWarning = ! empty($effective['endpoint_warning']);
            $httpStatus = $effective['http_status'] ?? null;

            if ($isSuccess) {
                $status = $hasWarning ? 'partial' : 'connected';
            } elseif ($errorType === 'configuration') {
                $status = 'not_configured';
            } elseif ($httpStatus === 401 || $httpStatus === 403 || $errorType === 'authentication' || $errorType === 'permission') {
                $status = 'authentication_failed';
            } elseif ($httpStatus === 404 || $errorType === 'endpoint') {
                $status = 'endpoint_unavailable';
            } else {
                $status = 'gateway_unreachable';
            }

            return [
                'status' => $status,
                'message' => $effective['message'] ?? $dashboardData['connection_message'],
                'detail' => $effective['detail'] ?? null,
                'troubleshooting' => $effective['troubleshooting'] ?? null,
                'endpoints' => $effective['endpoints'] ?? null,
            ];
        }

        return [
            'status' => $dashboardData['connection_status'], // 'ready' or 'not_configured'
            'message' => $dashboardData['connection_message'],
            'detail' => $dashboardData['connection_detail'] ?? null,
            'troubleshooting' => $dashboardData['connection_troubleshooting'] ?? null,
            'endpoints' => null,
        ];
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
            'sipintuDudiCount' => $data['sipintu_dudi_count'],
            'localStudentCount' => $data['local_student_count'],
            'localTeacherCount' => $data['local_teacher_count'],
            'localDudiCount' => $data['local_dudi_count'],
            'classroomMappingCount' => $data['classroom_mapping_count'],
            'history' => $data['history'],
            'preview' => [
                'success' => $preview['success'],
                'message' => $preview['message'],
                'students' => $preview['students'],
                'teachers' => $preview['teachers'],
                'dudis' => $preview['dudis'] ?? null,
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

        $entity = strtolower((string) $request->input('entity', 'all'));

        if ($entity === 'guru') {
            return redirect()
                ->route('admin.sipintu-sync.index')
                ->with('error', 'Endpoint API Guru SiPintu (/api/v1/sijuna/teachers) belum tersedia (HTTP 404). Data guru lokal tidak diubah.');
        }

        if ($entity === 'dudi') {
            return redirect()
                ->route('admin.sipintu-sync.index')
                ->with('error', 'Endpoint API Master DUDI SiPintu (/api/v1/sijuna/dudi) belum tersedia (HTTP 404). Data DUDI lokal tidak diubah.');
        }

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
