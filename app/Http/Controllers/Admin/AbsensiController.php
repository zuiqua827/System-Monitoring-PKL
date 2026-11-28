<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsensiRequest;
use App\Http\Requests\UpdateAbsensiRequest;
use App\Models\Absensi;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Absensi CRUD (Super Admin).
 */
class AbsensiController extends Controller
{
    public function __construct(
        private readonly AbsensiServiceInterface $absensiService,
    ) {}

    /**
     * Display a listing of the absensi.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Absensi::class);

        $absensis = $this->absensiService->getPaginated([
            'search' => $request->query('search'),
            'tanggal' => $request->query('tanggal'),
            'status' => $request->query('status'),
            'periode_id' => $request->query('periode_id'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('admin.absensi.index', compact('absensis'));
    }

    /**
     * Show the form for creating a new absensi.
     */
    public function create(): View
    {
        $this->authorize('create', Absensi::class);

        return view('admin.absensi.create');
    }

    /**
     * Store a newly created absensi in storage.
     */
    public function store(StoreAbsensiRequest $request): RedirectResponse
    {
        $this->authorize('create', Absensi::class);

        try {
            $this->absensiService->store($request->validated());

            return redirect()
                ->route('admin.absensi.index')
                ->with('success', 'Absensi berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan absensi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified absensi.
     */
    public function show(Absensi $absensi): View
    {
        $this->authorize('view', $absensi);

        $absensi->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('admin.absensi.show', compact('absensi'));
    }

    /**
     * Show the form for editing the specified absensi.
     */
    public function edit(Absensi $absensi): View
    {
        $this->authorize('update', $absensi);

        $absensi->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('admin.absensi.edit', compact('absensi'));
    }

    /**
     * Update the specified absensi in storage.
     */
    public function update(UpdateAbsensiRequest $request, Absensi $absensi): RedirectResponse
    {
        $this->authorize('update', $absensi);

        try {
            $this->absensiService->update($absensi, $request->validated());

            return redirect()
                ->route('admin.absensi.index')
                ->with('success', 'Absensi berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui absensi: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified absensi from storage (soft delete).
     */
    public function destroy(Absensi $absensi): RedirectResponse
    {
        $this->authorize('delete', $absensi);

        try {
            $this->absensiService->destroy($absensi);

            return redirect()
                ->route('admin.absensi.index')
                ->with('success', 'Absensi berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus absensi: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted absensi.
     */
    public function restore(int $id): RedirectResponse
    {
        $absensi = Absensi::withTrashed()->findOrFail($id);

        $this->authorize('restore', $absensi);

        try {
            $this->absensiService->restore($absensi);

            return redirect()
                ->route('admin.absensi.index')
                ->with('success', 'Absensi berhasil dipulihkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal memulihkan absensi: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete an absensi.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $absensi = Absensi::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $absensi);

        try {
            $this->absensiService->forceDelete($absensi);

            return redirect()
                ->route('admin.absensi.index')
                ->with('success', 'Absensi berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus permanen absensi: ' . $e->getMessage());
        }
    }
}

