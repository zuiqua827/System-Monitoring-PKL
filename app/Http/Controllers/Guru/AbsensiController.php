<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller for Guru Absensi features (view bimbingan, validate absensi).
 */
class AbsensiController extends Controller
{
    public function __construct(
        private readonly AbsensiServiceInterface $absensiService,
    ) {}

    /**
     * Display a listing of absensi for students under guru's guidance.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Guru|null $guru */
        $guru = $user->guru;

        if ($guru === null) {
            abort(403, 'Data guru tidak ditemukan.');
        }

        $absensis = $this->absensiService->getGuruAbsensiPaginated($guru->id, [
            'search' => $request->query('search'),
            'tanggal' => $request->query('tanggal'),
            'status' => $request->query('status'),
            'periode_id' => $request->query('periode_id'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('guru.absensi.index', compact('absensis'));
    }

    /**
     * Display the specified absensi detail.
     */
    public function show(int $id): View
    {
        $absensi = $this->absensiService->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Guru|null $guru */
        $guru = $user->guru;

        if ($guru === null) {
            abort(403, 'Data guru tidak ditemukan.');
        }

        // Ensure guru only sees absensi of their own bimbingan
        if ($absensi->penempatanPKL->guru_id !== $guru->id) {
            abort(403, 'Anda tidak berhak melihat absensi ini.');
        }

        $absensi->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('guru.absensi.show', compact('absensi'));
    }

    /**
     * Verify/validate an absensi.
     */
    public function verify(Request $request, int $id): RedirectResponse
    {
        $absensi = $this->absensiService->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->authorize('verify', $absensi);

        try {
            $validated = $request->validate([
                'status' => ['required', 'string', 'in:hadir,terlambat,izin,sakit,alpha'],
                'keterangan' => ['nullable', 'string', 'max:1000'],
            ]);

            $this->absensiService->validateAbsensi($absensi, $validated);

            return redirect()
                ->route('guru.absensi.index')
                ->with('success', 'Absensi berhasil divalidasi.');
        } catch (\Exception $e) {
            Log::error('Validasi absensi gagal: ' . $e->getMessage(), [
                'guru_id' => $user->guru?->id,
                'absensi_id' => $id,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Gagal memvalidasi absensi: ' . $e->getMessage());
        }
    }
}

