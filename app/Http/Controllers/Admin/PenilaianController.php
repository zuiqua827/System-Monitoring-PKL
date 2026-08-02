<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenilaianRequest;
use App\Http\Requests\UpdatePenilaianRequest;
use App\Models\Penilaian;
use App\Services\Interfaces\PenilaianServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller for Admin Penilaian CRUD.
 *
 * Super Admin has full access:
 * - View all penilaian
 * - Create, Edit, Delete, Restore, Force Delete
 */
class PenilaianController extends Controller
{
    public function __construct(
        private readonly PenilaianServiceInterface $penilaianService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Penilaian::class);

        $penilaianList = $this->penilaianService->getPaginated([
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'guru_id' => $request->query('guru_id'),
            'periode_id' => $request->query('periode_id'),
            'sort_by' => $request->query('sort', 'created_at'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('admin.penilaian.index', compact('penilaianList'));
    }

    public function create(): View
    {
        $this->authorize('create', Penilaian::class);

        return view('admin.penilaian.create');
    }

    public function store(StorePenilaianRequest $request): RedirectResponse
    {
        $this->authorize('create', Penilaian::class);

        try {
            $this->penilaianService->store($request->validated());

            return redirect()
                ->route('admin.penilaian.index')
                ->with('success', 'Penilaian berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan penilaian: ' . $e->getMessage());
        }
    }

    public function show(Penilaian $penilaian): View
    {
        $this->authorize('view', $penilaian);

        $penilaian->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
            'dinilaiOleh',
        ]);

        return view('admin.penilaian.show', compact('penilaian'));
    }

    public function edit(Penilaian $penilaian): View
    {
        $this->authorize('update', $penilaian);

        $penilaian->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('admin.penilaian.edit', compact('penilaian'));
    }

    public function update(UpdatePenilaianRequest $request, Penilaian $penilaian): RedirectResponse
    {
        $this->authorize('update', $penilaian);

        try {
            $this->penilaianService->update($penilaian, $request->validated());

            return redirect()
                ->route('admin.penilaian.index')
                ->with('success', 'Penilaian berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui penilaian: ' . $e->getMessage());
        }
    }

    public function destroy(Penilaian $penilaian): RedirectResponse
    {
        $this->authorize('delete', $penilaian);

        try {
            $this->penilaianService->destroy($penilaian);

            return redirect()
                ->route('admin.penilaian.index')
                ->with('success', 'Penilaian berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus penilaian.');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        $penilaian = Penilaian::withTrashed()->findOrFail($id);

        $this->authorize('restore', $penilaian);

        try {
            $this->penilaianService->restore($penilaian);

            return redirect()
                ->route('admin.penilaian.index')
                ->with('success', 'Penilaian berhasil dipulihkan.');
        } catch (\Exception $e) {
            Log::error('Gagal memulihkan penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Gagal memulihkan penilaian.');
        }
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $penilaian = Penilaian::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $penilaian);

        try {
            $this->penilaianService->forceDelete($penilaian);

            return redirect()
                ->route('admin.penilaian.index')
                ->with('success', 'Penilaian berhasil dihapus permanen.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus permanen penilaian: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus permanen penilaian.');
        }
    }
}
