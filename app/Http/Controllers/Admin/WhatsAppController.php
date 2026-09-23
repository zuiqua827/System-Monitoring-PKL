<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppLog;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function __construct(
        private readonly WhatsAppServiceInterface $waService,
    ) {
    }

    /**
     * Display WhatsApp Gateway and notification configuration dashboard.
     */
    public function index(): View
    {
        $settings = $this->waService->getSettings();
        $gatewayStatus = $this->waService->getGatewayStatus();

        $today = Carbon::today(config('app.timezone'));

        $stats = [
            'sent_today' => WhatsAppLog::whereDate('tanggal', $today)->where('status', WhatsAppLog::STATUS_SENT)->count(),
            'pending_today' => WhatsAppLog::whereDate('tanggal', $today)->where('status', WhatsAppLog::STATUS_PENDING)->count(),
            'failed_today' => WhatsAppLog::whereDate('tanggal', $today)->where('status', WhatsAppLog::STATUS_FAILED)->count(),
            'skipped_today' => WhatsAppLog::whereDate('tanggal', $today)->where('status', WhatsAppLog::STATUS_SKIPPED)->count(),
            'total_logs' => WhatsAppLog::count(),
            'last_sent' => WhatsAppLog::where('status', WhatsAppLog::STATUS_SENT)->latest('sent_at')->value('sent_at'),
            'last_failed' => WhatsAppLog::where('status', WhatsAppLog::STATUS_FAILED)->latest('failed_at')->value('failed_at') ?? WhatsAppLog::where('status', WhatsAppLog::STATUS_FAILED)->latest('created_at')->value('created_at'),
        ];

        $stats['total_today'] = $stats['sent_today'] + $stats['failed_today'];
        $stats['success_rate'] = $stats['total_today'] > 0 ? round(($stats['sent_today'] / $stats['total_today']) * 100, 1) : 0;

        $recentLogs = WhatsAppLog::with(['siswa', 'penempatan.dudi'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.whatsapp.index', [
            'settings' => $settings,
            'gatewayStatus' => $gatewayStatus,
            'stats' => $stats,
            'recentLogs' => $recentLogs,
        ]);
    }

    /**
     * Ajax endpoint for polling gateway status & QR code in real-time.
     */
    public function statusAjax(): JsonResponse
    {
        $status = $this->waService->getGatewayStatus();
        return response()->json($status);
    }

    /**
     * Update WhatsApp notification settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'master_enabled' => ['nullable'],
            'reminder_enabled' => ['nullable'],
            'late_enabled' => ['nullable'],
            'offset_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'reminder_template' => ['required', 'string'],
            'late_template' => ['required', 'string'],
            'gateway_url' => ['required', 'url'],
            'api_key' => ['nullable', 'string', 'max:100'],
        ]);

        $this->waService->updateSettings([
            'master_enabled' => $request->boolean('master_enabled'),
            'reminder_enabled' => $request->boolean('reminder_enabled'),
            'late_enabled' => $request->boolean('late_enabled'),
            'offset_minutes' => (int) $validated['offset_minutes'],
            'reminder_template' => $validated['reminder_template'],
            'late_template' => $validated['late_template'],
            'gateway_url' => $validated['gateway_url'],
            'api_key' => $validated['api_key'] ?? null,
        ]);

        return redirect()
            ->route('admin.whatsapp.index')
            ->with('success', 'Pengaturan notifikasi WhatsApp berhasil diperbarui.');
    }

    /**
     * Send a manual test message via the WhatsApp Gateway.
     */
    public function sendTest(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->waService->sendTestMessage(
            (string) $request->input('phone'),
            (string) $request->input('message')
        );

        if ($result['success']) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', 'Pesan uji coba berhasil dikirim ke ' . $request->input('phone'));
        }

        return redirect()
            ->route('admin.whatsapp.index')
            ->with('error', 'Gagal mengirim pesan uji coba: ' . ($result['error'] ?? 'Terjadi kesalahan pada gateway.'));
    }

    /**
     * Disconnect/logout current session on WhatsApp Gateway.
     */
    public function disconnect(): RedirectResponse
    {
        $result = $this->waService->disconnectGateway();

        if ($result['success']) {
            return redirect()
                ->route('admin.whatsapp.index')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('admin.whatsapp.index')
            ->with('error', $result['message']);
    }

    /**
     * Manually trigger scan and send reminders immediately.
     */
    public function scanNow(Request $request): RedirectResponse
    {
        Artisan::call('whatsapp:attendance-reminders', ['--force' => true]);
        $output = Artisan::output();

        return redirect()
            ->route('admin.whatsapp.index')
            ->with('success', 'Pemindaian manual selesai dijalankan: ' . trim($output));
    }

    /**
     * Display paginated WhatsApp notification logs.
     */
    public function logs(Request $request): View
    {
        $query = WhatsAppLog::with(['siswa', 'penempatan.dudi'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('type')) {
            $query->where('message_type', $request->query('type'));
        }

        if ($request->filled('date')) {
            $query->whereDate('tanggal', $request->query('date'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('recipient_phone', 'like', $search)
                  ->orWhere('message_content', 'like', $search)
                  ->orWhereHas('siswa', function ($sq) use ($search) {
                      $sq->where('nama', 'like', $search)->orWhere('nis', 'like', $search);
                  });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Summary statistics (Global)
        $summary = [
            'total' => WhatsAppLog::count(),
            'sent' => WhatsAppLog::where('status', WhatsAppLog::STATUS_SENT)->count(),
            'failed' => WhatsAppLog::where('status', WhatsAppLog::STATUS_FAILED)->count(),
            'pending' => WhatsAppLog::where('status', WhatsAppLog::STATUS_PENDING)->count(),
        ];

        return view('admin.whatsapp.logs', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => [
                'status' => $request->query('status'),
                'type' => $request->query('type'),
                'date' => $request->query('date'),
                'search' => $request->query('search'),
            ],
        ]);
    }
}
