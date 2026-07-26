<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKelasRequest;
use App\Http\Requests\UpdateKelasRequest;
use App\Models\Kelas;
use App\Services\Interfaces\KelasServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Kelas CRUD.
 *
 * This controller only acts as a connector between HTTP requests and the Service layer.
 * NO business logic is allowed here.
 *
 * Authorization is handled via KelasPolicy using $this->authorize().
 */
class KelasController extends Controller
{
    public function __construct(
        private readonly KelasServiceInterface $kelasService,
    ) {}

    /**
     * Display a listing of kelas.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Kelas::class);

        $kelass = $this->kelasService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'nama'),
            sortDirection: $request->query('direction', 'asc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.kelas.index', compact('kelass'));
    }

    /**
     * Show the form for creating a new kelas.
     */
    public function create(): View
    {
        $this->authorize('create', Kelas::class);

        return view('admin.kelas.create');
    }

    /**
     * Store a newly created kelas in storage.
     */
    public function store(StoreKelasRequest $request): RedirectResponse
    {
        $this->authorize('create', Kelas::class);

        $this->kelasService->store($request->validated());

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    /**
     * Display the specified kelas.
     */
    public function show(Kelas $kela): View
    {
        $this->authorize('view', $kela);

        $kela->load('jurusan');
        $kela->loadCount('siswa');

        return view('admin.kelas.show', compact('kela'));
    }

    /**
     * Show the form for editing the specified kelas.
     */
    public function edit(Kelas $kela): View
    {
        $this->authorize('update', $kela);

        $kela->load('jurusan');

        return view('admin.kelas.edit', compact('kela'));
    }

    /**
     * Update the specified kelas in storage.
     */
    public function update(UpdateKelasRequest $request, Kelas $kela): RedirectResponse
    {
        $this->authorize('update', $kela);

        $this->kelasService->update($kela, $request->validated());

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    /**
     * Soft delete the specified kelas.
     */
    public function destroy(Kelas $kela): RedirectResponse
    {
        $this->authorize('delete', $kela);

        $this->kelasService->destroy($kela);

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted kelas.
     */
    public function restore(int $id): RedirectResponse
    {
        $kelas = Kelas::withTrashed()->findOrFail($id);

        $this->authorize('restore', $kelas);

        $this->kelasService->restore($kelas);

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil dipulihkan.');
    }

    /**
     * Permanently delete a kelas (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $kelas = Kelas::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $kelas);

        $this->kelasService->forceDelete($kelas);

        return redirect()
            ->route('admin.kelas.index')
            ->with('success', 'Kelas berhasil dihapus permanen.');
    }
}
