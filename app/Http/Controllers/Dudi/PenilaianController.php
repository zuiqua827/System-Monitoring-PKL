<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenilaianRequest;
use App\Http\Requests\UpdatePenilaianRequest;
use App\Models\Penilaian;
use App\Services\Interfaces\PenilaianServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function __construct(
        private readonly PenilaianServiceInterface $penilaianService
    ) {}

    public function index(Request $request): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $filters = $request->only(['search', 'status', 'periode_id']);
        $filters['sort_by'] = $request->query('sort', 'created_at');
        $filters['sort_direction'] = $request->query('direction', 'desc');

        $penilaianList = $this->penilaianService->getDudiPenilaianPaginated($dudi->id, $filters);

        $periodeList = \App\Models\PeriodePKL::where('status', 'aktif')->get();

        return view('dudi.penilaian.index', compact('penilaianList', 'periodeList'));
    }

    public function show(int $id): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $penilaian = $this->penilaianService->findOrFail($id);
        $penilaian->load(['penempatanPKL.siswa.kelas.jurusan', 'penempatanPKL.guru', 'dinilaiOleh']);

        if ($penilaian->penempatanPKL->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak memiliki akses ke penilaian ini.');
        }

        return view('dudi.penilaian.show', compact('penilaian'));
    }

    public function create(): View
    {
        $this->authorize('create', Penilaian::class);

        return view('dudi.penilaian.create');
    }

    public function store(StorePenilaianRequest $request): RedirectResponse
    {
        $this->authorize('create', Penilaian::class);

        try {
            $this->penilaianService->store($request->validated());

            return redirect()
                ->route('dudi.penilaian.index')
                ->with('success', 'Penilaian berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }

    public function edit(int $id): View
    {
        $penilaian = $this->penilaianService->findOrFail($id);

        $this->authorize('update', $penilaian);

        $penilaian->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('dudi.penilaian.edit', compact('penilaian'));
    }

    public function update(UpdatePenilaianRequest $request, int $id): RedirectResponse
    {
        $penilaian = $this->penilaianService->findOrFail($id);

        $this->authorize('update', $penilaian);

        try {
            $this->penilaianService->update($penilaian, $request->validated());

            return redirect()
                ->route('dudi.penilaian.index')
                ->with('success', 'Penilaian berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui penilaian: ' . $e->getMessage());
        }
    }

    public function finalize(int $id): RedirectResponse
    {
        $penilaian = $this->penilaianService->findOrFail($id);

        $this->authorize('finalize', $penilaian);

        try {
            $this->penilaianService->finalize($penilaian);

            return redirect()
                ->route('dudi.penilaian.index')
                ->with('success', 'Penilaian berhasil difinalisasi.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Gagal finalisasi penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Gagal finalisasi penilaian: ' . $e->getMessage());
        }
    }
}
