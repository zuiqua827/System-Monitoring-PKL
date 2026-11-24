<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Dudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller untuk mengelola pemetaan manual akun identitas SIPINTU ke DUDI SIMONGAN.
 *
 * Kebijakan Keamanan:
 * 1. Super Admin ONLY (dilindungi middleware role:Super Admin).
 * 2. Pemetaan menggunakan SIPINTU User ID yang stabil dan unik (1:1 strict).
 * 3. Ketika pemetaan dibuat/diubah/dibatalkan, sso_enabled OTOMATIS di-reset ke false.
 * 4. Pembatalan pemetaan (unmap) TIDAK menghapus akun User, data DUDI, atau relasi PKL.
 * 5. Setiap aksi dicatat dalam tabel audit_logs.
 */
class SipintuDudiMappingController extends Controller
{
    /**
     * Tampilkan halaman daftar pemetaan identitas DUDI SIPINTU.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');

        $query = Dudi::query()->with('user');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('nama_perusahaan', 'like', "%{$search}%")
                    ->orWhere('penanggung_jawab', 'like', "%{$search}%")
                    ->orWhere('email_perusahaan', 'like', "%{$search}%")
                    ->orWhere('sipintu_user_id', 'like', "%{$search}%")
                    ->orWhere('sipintu_id', 'like', "%{$search}%")
                    ->orWhere('sipintu_email', 'like', "%{$search}%");
            });
        }

        if ($status === 'mapped') {
            $query->where(function ($q): void {
                $q->whereNotNull('sipintu_user_id')
                    ->orWhereNotNull('sipintu_id');
            });
        } elseif ($status === 'unmapped') {
            $query->whereNull('sipintu_user_id')
                ->whereNull('sipintu_id');
        } elseif ($status === 'sso_active') {
            $query->where('sso_enabled', true);
        } elseif ($status === 'sso_inactive') {
            $query->where('sso_enabled', false);
        }

        $dudis = $query->orderBy('nama_perusahaan', 'asc')->paginate(15)->withQueryString();

        $totalDudi = Dudi::query()->count();
        $connectedDudi = Dudi::query()->where(function ($q): void {
            $q->whereNotNull('sipintu_user_id')
                ->orWhereNotNull('sipintu_id');
        })->count();
        $unconnectedDudi = Dudi::query()->whereNull('sipintu_user_id')
            ->whereNull('sipintu_id')
            ->count();
        $ssoActive = Dudi::query()->where('sso_enabled', true)->count();

        return view('admin.sipintu-dudi-mapping.index', [
            'dudis' => $dudis,
            'totalDudi' => $totalDudi,
            'connectedDudi' => $connectedDudi,
            'unconnectedDudi' => $unconnectedDudi,
            'ssoActive' => $ssoActive,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Simpan atau perbarui pemetaan identitas SIPINTU untuk DUDI tertentu.
     * Mendukung pemetaan SIPINTU User ID (untuk SSO) dan sipintu_id (Master Company ID).
     */
    public function store(Request $request, Dudi $dudi): RedirectResponse
    {
        $validated = $request->validate([
            'sipintu_user_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('dudi', 'sipintu_user_id')->ignore($dudi->id),
            ],
            'sipintu_id' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('dudi', 'sipintu_id')->ignore($dudi->id),
            ],
            'sipintu_email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ], [
            'sipintu_user_id.unique' => 'SIPINTU User ID ini sudah dipetakan ke DUDI lain. Satu identitas akun SIPINTU hanya boleh dipetakan ke satu DUDI.',
            'sipintu_id.unique' => 'ID Master Perusahaan SIPINTU ini sudah terdaftar pada DUDI lain.',
            'sipintu_email.email' => 'Format email SIPINTU tidak valid.',
        ]);

        if (empty($validated['sipintu_user_id']) && empty($validated['sipintu_id'])) {
            return back()->withErrors([
                'sipintu_user_id' => 'Minimal salah satu dari SIPINTU User ID (untuk SSO) atau ID Perusahaan SIPINTU (untuk Master Data) harus diisi.',
            ]);
        }

        $oldValues = [
            'sipintu_id' => $dudi->sipintu_id,
            'sipintu_user_id' => $dudi->sipintu_user_id,
            'sipintu_email' => $dudi->sipintu_email,
            'sso_enabled' => $dudi->sso_enabled,
        ];

        if (isset($validated['sipintu_user_id'])) {
            $newSipintuUserId = trim((string) $validated['sipintu_user_id']);
            $dudi->sipintu_user_id = $newSipintuUserId !== '' ? $newSipintuUserId : null;
        }

        if (isset($validated['sipintu_id'])) {
            $newSipintuId = trim((string) $validated['sipintu_id']);
            $dudi->sipintu_id = $newSipintuId !== '' ? $newSipintuId : null;
        }

        if (array_key_exists('sipintu_email', $validated)) {
            $newSipintuEmail = ! empty($validated['sipintu_email']) ? strtolower(trim((string) $validated['sipintu_email'])) : null;
            $dudi->sipintu_email = $newSipintuEmail;
        }

        // Reset sso_enabled to false on mapping changes per security rule 4
        $dudi->sso_enabled = false;
        $dudi->save();

        $this->recordAudit(
            action: 'sipintu_dudi_mapping.updated',
            recordId: $dudi->id,
            oldValues: $oldValues,
            newValues: [
                'sipintu_id' => $dudi->sipintu_id,
                'sipintu_user_id' => $dudi->sipintu_user_id,
                'sipintu_email' => $dudi->sipintu_email,
                'sso_enabled' => false,
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.sipintu-dudi-mapping.index')
            ->with('success', "Identitas SIPINTU untuk DUDI \"{$dudi->nama_perusahaan}\" berhasil dipetakan.");
    }

    /**
     * Batalkan pemetaan identitas SIPINTU (unmap).
     *
     * TIDAK MENGHAPUS DUDI ATAU USER ATAU PENEMPATAN PKL.
     */
    public function destroy(Request $request, Dudi $dudi): RedirectResponse
    {
        $oldValues = [
            'sipintu_id' => $dudi->sipintu_id,
            'sipintu_user_id' => $dudi->sipintu_user_id,
            'sipintu_email' => $dudi->sipintu_email,
            'sso_enabled' => $dudi->sso_enabled,
        ];

        $target = $request->input('target', 'sso');

        if ($target === 'company') {
            $dudi->sipintu_id = null;
            $msg = "Pemetaan ID Perusahaan SIPINTU untuk DUDI \"{$dudi->nama_perusahaan}\" berhasil dibatalkan.";
        } else {
            $dudi->sipintu_user_id = null;
            $dudi->sipintu_email = null;
            $dudi->sso_enabled = false;
            $msg = "Pemetaan akun SSO SIPINTU untuk DUDI \"{$dudi->nama_perusahaan}\" berhasil dibatalkan. Akses SSO dinonaktifkan. Data DUDI dan penempatan PKL tetap utuh dan aman.";
        }

        $dudi->save();

        $this->recordAudit(
            action: 'sipintu_dudi_mapping.deleted',
            recordId: $dudi->id,
            oldValues: $oldValues,
            newValues: [
                'sipintu_id' => $dudi->sipintu_id,
                'sipintu_user_id' => $dudi->sipintu_user_id,
                'sipintu_email' => $dudi->sipintu_email,
                'sso_enabled' => $dudi->sso_enabled,
            ],
            request: $request,
        );

        return redirect()
            ->route('admin.sipintu-dudi-mapping.index')
            ->with('success', $msg);
    }

    /**
     * Toggle persetujuan/aktivasi SSO DUDI oleh Super Admin.
     */
    public function toggleSso(Request $request, Dudi $dudi): RedirectResponse
    {
        if ($dudi->sso_enabled) {
            // Menonaktifkan SSO
            $dudi->sso_enabled = false;
            $dudi->save();

            $this->recordAudit(
                action: 'sipintu_dudi_mapping.sso_deactivated',
                recordId: $dudi->id,
                oldValues: ['sso_enabled' => true],
                newValues: ['sso_enabled' => false],
                request: $request,
            );

            return redirect()
                ->route('admin.sipintu-dudi-mapping.index')
                ->with('success', "Akses login SSO untuk DUDI \"{$dudi->nama_perusahaan}\" telah dinonaktifkan.");
        }

        // Validasi pra-aktivasi SSO
        if (empty($dudi->sipintu_user_id)) {
            return redirect()
                ->route('admin.sipintu-dudi-mapping.index')
                ->with('error', "Gagal mengaktifkan SSO: DUDI \"{$dudi->nama_perusahaan}\" belum dipetakan ke SIPINTU User ID.");
        }

        if (! $dudi->status_aktif) {
            return redirect()
                ->route('admin.sipintu-dudi-mapping.index')
                ->with('error', "Gagal mengaktifkan SSO: Status operasional DUDI \"{$dudi->nama_perusahaan}\" sedang nonaktif.");
        }

        if (! $dudi->user || ! $dudi->user->hasRole('DUDI')) {
            return redirect()
                ->route('admin.sipintu-dudi-mapping.index')
                ->with('error', "Gagal mengaktifkan SSO: Akun pengguna lokal untuk DUDI \"{$dudi->nama_perusahaan}\" tidak valid atau tidak memiliki peran DUDI.");
        }

        $dudi->sso_enabled = true;
        $dudi->save();

        $this->recordAudit(
            action: 'sipintu_dudi_mapping.sso_activated',
            recordId: $dudi->id,
            oldValues: ['sso_enabled' => false],
            newValues: ['sso_enabled' => true],
            request: $request,
        );

        return redirect()
            ->route('admin.sipintu-dudi-mapping.index')
            ->with('success', "Akses login SSO untuk DUDI \"{$dudi->nama_perusahaan}\" berhasil diverifikasi dan diaktifkan.");
    }

    /**
     * Catat riwayat perubahan ke tabel audit_logs.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function recordAudit(string $action, int $recordId, array $oldValues, array $newValues, Request $request): void
    {
        try {
            AuditLog::query()->create([
                'user_id' => Auth::id(),
                'action' => $action,
                'table_name' => 'dudi',
                'record_id' => $recordId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ]);
        } catch (\Throwable) {
            // Pencatatan audit tidak boleh menghentikan transaksi utama
        }
    }
}
