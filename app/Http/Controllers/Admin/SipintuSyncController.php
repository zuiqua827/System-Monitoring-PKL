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
 */
class SipintuSyncController extends Controller
{
    public function __construct(
        private readonly SipintuSyncServiceInterface $syncService,
    ) {}

    /**
     * Display the SiPintu synchronization dashboard.
     */
    public function index(): View
    {
        $data = $this->syncService->getDashboardData();

        return view('admin.sipintu-sync.index', [
            'connectionStatus' => $data['connection_status'],
            'connectionMessage' => $data['connection_message'],
            'lastSync' => $data['last_sync'],
            'sipintuStudentCount' => $data['sipintu_student_count'],
            'localStudentCount' => $data['local_student_count'],
            'history' => $data['history'],
        ]);
    }

    /**
     * Trigger a manual synchronization.
     */
    public function sync(Request $request): RedirectResponse
    {
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
