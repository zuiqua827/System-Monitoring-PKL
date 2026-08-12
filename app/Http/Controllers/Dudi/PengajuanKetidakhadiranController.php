<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPengajuanKetidakhadiranRequest;
use App\Models\PengajuanKetidakhadiran;
use App\Services\Interfaces\PengajuanKetidakhadiranServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PengajuanKetidakhadiranController extends Controller
{
    public function __construct(
        private readonly PengajuanKetidakhadiranServiceInterface $pengajuanService,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $dudi = $user->dudi;

        if ($dudi === null) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $pengajuans = $this->pengajuanService->getDudiPengajuanPaginated($dudi->id, [
            'status' => $request->query('status'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('dudi.ketidakhadiran.index', compact('pengajuans'));
    }

    public function show(int $id): View
    {
        $pengajuan = $this->pengajuanService->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $dudi = $user->dudi;

        if ($dudi === null) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        // Authorization: Hanya DUDI yang relevan
        if ($pengajuan->penempatanPKL->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak berhak melihat pengajuan ini.');
        }

        $pengajuan->load(['penempatanPKL.siswa', 'penempatanPKL.guru']);

        return view('dudi.ketidakhadiran.show', compact('pengajuan'));
    }

    public function process(ProcessPengajuanKetidakhadiranRequest $request, int $id): RedirectResponse
    {
        $pengajuan = $this->pengajuanService->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $dudi = $user->dudi;

        // Authorization
        if ($dudi === null || $pengajuan->penempatanPKL->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak berhak memproses pengajuan ini.');
        }

        try {
            $data = $request->validated();
            
            $this->pengajuanService->process(
                $pengajuan,
                $data['status'],
                $data['catatan'] ?? null,
                $user->id
            );

            return redirect()->route('dudi.ketidakhadiran.index')->with('success', 'Pengajuan berhasil diproses.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Gagal memproses pengajuan: ' . $e->getMessage(), ['id' => $id]);
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
        }
    }
}
