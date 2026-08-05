<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Penilaian;
use App\Services\Interfaces\PenilaianServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for Siswa Penilaian features.
 *
 * Siswa can:
 * - View their own penilaian results
 * - Download their own final penilaian as PDF
 * - Cannot create, edit, or delete
 */
class PenilaianController extends Controller
{
    public function __construct(
        private readonly PenilaianServiceInterface $penilaianService,
    ) {}

    /**
     * Display a listing of penilaian for the authenticated siswa.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            abort(403, 'Data siswa tidak ditemukan.');
        }

        $penilaianList = $this->penilaianService->getSiswaPenilaianPaginated($siswa->id, [
            'sort_by' => $request->query('sort', 'created_at'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

$penempatanAktif = $siswa->penempatan()->where('status', 'aktif')->first();

        return view('siswa.penilaian.index', compact('penilaianList', 'penempatanAktif'));
    }

    /**
     * Display the specified penilaian.
     */
    public function show(int $id): View
    {
        $penilaian = $this->penilaianService->findOrFail($id);

        $this->authorize('view', $penilaian);

        $penilaian->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
            'dinilaiOleh',
        ]);

return view('siswa.penilaian.show', compact('penilaian'));
    }

    /**
     * Download the penilaian as an official PDF (only when final).
     */
    public function downloadPdf(Penilaian $penilaian)
    {
        // Authorization: only the owner (or authorized roles) may view.
        $this->authorize('view', $penilaian);

        // Only finalized penilaian may be printed.
        if ($penilaian->status !== 'final') {
            abort(403, 'Penilaian hanya dapat dicetak setelah status Final.');
        }

        $penilaian->load([
            'penempatanPKL.siswa.kelas.jurusan',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
            'dinilaiOleh',
        ]);

        $siswa = $penilaian->penempatanPKL?->siswa;
        $fileName = 'Penilaian_PKL_' . str_replace(' ', '_', $siswa?->nama ?? 'Siswa') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.penilaian', compact('penilaian'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($fileName);
    }
}
