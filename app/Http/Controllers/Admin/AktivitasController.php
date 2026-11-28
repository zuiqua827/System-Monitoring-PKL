<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAktivitasRequest;
use App\Http\Requests\UpdateAktivitasRequest;
use App\Models\Aktivitas;
use App\Services\Interfaces\AktivitasServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Aktivitas CRUD (Super Admin).
 */
class AktivitasController extends Controller
{
    public function __construct(
        private readonly AktivitasServiceInterface $aktivitasService,
    ) {}

    /**
     * Display a listing of aktivitas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Aktivitas::class);

        $aktivitasList = $this->aktivitasService->getPaginated([
            'search' => $request->query('search'),
            'tanggal' => $request->query('tanggal'),
            'status' => $request->query('status'),
            'periode_id' => $request->query('periode_id'),
            'guru_id' => $request->query('guru_id'),
            'siswa_id' => $request->query('siswa_id'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('admin.aktivitas.index', compact('aktivitasList'));
    }

    /**
     * Show the form for creating a new aktivitas.
     */
    public function create(): View
    {
        $this->authorize('create', Aktivitas::class);

        return view('admin.aktivitas.create');
    }

    /**
     * Store a newly created aktivitas in storage.
     */
    public function store(StoreAktivitasRequest $request): RedirectResponse
    {
        $this->authorize('create', Aktivitas::class);

        try {
            $this->aktivitasService->store($request->validated());

            return redirect()
                ->route('admin.aktivitas.index')
                ->with('success', 'Aktivitas berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified aktivitas.
     */
    public function show(Aktivitas $aktivitas): View
    {
        $this->authorize('view', $aktivitas);

        $aktivitas->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
            'validatedBy',
        ]);

        return view('admin.aktivitas.show', compact('aktivitas'));
    }

    /**
     * Show the form for editing the specified aktivitas.
     */
    public function edit(Aktivitas $aktivitas): View
    {
        $this->authorize('update', $aktivitas);

        $aktivitas->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('admin.aktivitas.edit', compact('aktivitas'));
    }

    /**
     * Update the specified aktivitas in storage.
     */
    public function update(UpdateAktivitasRequest $request, Aktivitas $aktivitas): RedirectResponse
    {
        $this->authorize('update', $aktivitas);

        try {
            $this->aktivitasService->update($aktivitas, $request->validated());

            return redirect()
                ->route('admin.aktivitas.index')
                ->with('success', 'Aktivitas berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified aktivitas from storage (soft delete).
     */
    public function destroy(Aktivitas $aktivitas): RedirectResponse
    {
        $this->authorize('delete', $aktivitas);

        try {
            $this->aktivitasService->destroy($aktivitas);

            return redirect()
                ->route('admin.aktivitas.index')
                ->with('success', 'Aktivitas berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted aktivitas.
     */
    public function restore(int $id): RedirectResponse
    {
        $aktivitas = Aktivitas::withTrashed()->findOrFail($id);

        $this->authorize('restore', $aktivitas);

        try {
            $this->aktivitasService->restore($aktivitas);

            return redirect()
                ->route('admin.aktivitas.index')
                ->with('success', 'Aktivitas berhasil dipulihkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal memulihkan aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete an aktivitas.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $aktivitas = Aktivitas::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $aktivitas);

        try {
            $this->aktivitasService->forceDelete($aktivitas);

            return redirect()
                ->route('admin.aktivitas.index')
                ->with('success', 'Aktivitas berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus permanen aktivitas: ' . $e->getMessage());
        }
    }
}

