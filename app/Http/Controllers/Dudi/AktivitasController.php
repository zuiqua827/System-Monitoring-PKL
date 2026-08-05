<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateAktivitasRequest;
use App\Models\Aktivitas;
use App\Services\Interfaces\AktivitasServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AktivitasController extends Controller
{
    public function __construct(
        private readonly AktivitasServiceInterface $aktivitasService
    ) {}

    public function index(Request $request): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $filters = $request->only(['search', 'tanggal', 'status', 'periode_id']);
        $filters['sort_by'] = $request->query('sort', 'tanggal');
        $filters['sort_direction'] = $request->query('direction', 'desc');

        $aktivitasList = $this->aktivitasService->getDudiAktivitasPaginated($dudi->id, $filters);

        $periodeList = \App\Models\PeriodePKL::where('status', 'aktif')->get();

        return view('dudi.aktivitas.index', compact('aktivitasList', 'periodeList'));
    }

    public function show(int $id): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $aktivitas = $this->aktivitasService->findOrFail($id);
        $aktivitas->load(['penempatanPKL.siswa.kelas.jurusan', 'penempatanPKL.guru']);

        if ($aktivitas->penempatanPKL->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak memiliki akses ke aktivitas ini.');
        }

        return view('dudi.aktivitas.show', compact('aktivitas'));
    }

public function update(ValidateAktivitasRequest $request, int $id): RedirectResponse
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $aktivitas = $this->aktivitasService->findOrFail($id);

        if ($aktivitas->penempatanPKL->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak memiliki akses ke aktivitas ini.');
        }

        try {
            $this->aktivitasService->validateAktivitas($aktivitas, $request->validated());
            return redirect()->route('dudi.aktivitas.show', $aktivitas->id)
                ->with('success', 'Status aktivitas berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
