<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiswaRequest;
use App\Http\Requests\UpdateSiswaRequest;
use App\Models\Siswa;
use App\Services\Interfaces\SiswaServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Siswa CRUD.
 *
 * This controller only acts as a connector between HTTP requests and the Service layer.
 * NO business logic is allowed here.
 *
 * Authorization is handled via SiswaPolicy using $this->authorize().
 */
class SiswaController extends Controller
{
    public function __construct(
        private readonly SiswaServiceInterface $siswaService,
    ) {}

    /**
     * Display a listing of siswa.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Siswa::class);

        $siswas = $this->siswaService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'nama'),
            sortDirection: $request->query('direction', 'asc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.siswa.index', compact('siswas'));
    }

    /**
     * Show the form for creating a new siswa.
     */
    public function create(): View
    {
        $this->authorize('create', Siswa::class);

        return view('admin.siswa.create');
    }

    /**
     * Store a newly created siswa in storage.
     */
    public function store(StoreSiswaRequest $request): RedirectResponse
    {
        $this->authorize('create', Siswa::class);

        $this->siswaService->store($request->validated());

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Siswa berhasil ditambahkan.');
    }

    /**
     * Display the specified siswa.
     */
    public function show(Siswa $siswa): View
    {
        $this->authorize('view', $siswa);

        $siswa->load(['user', 'kelas']);
        $siswa->loadCount('penempatan');

        return view('admin.siswa.show', compact('siswa'));
    }

    /**
     * Show the form for editing the specified siswa.
     */
    public function edit(Siswa $siswa): View
    {
        $this->authorize('update', $siswa);

        $siswa->load(['user', 'kelas']);

        return view('admin.siswa.edit', compact('siswa'));
    }

    /**
     * Update the specified siswa in storage.
     */
    public function update(UpdateSiswaRequest $request, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);

        $this->siswaService->update($siswa, $request->validated());

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Siswa berhasil diperbarui.');
    }

    /**
     * Soft delete the specified siswa.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        $this->authorize('delete', $siswa);

        $this->siswaService->destroy($siswa);

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Siswa berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted siswa.
     */
    public function restore(int $id): RedirectResponse
    {
        $siswa = Siswa::withTrashed()->findOrFail($id);

        $this->authorize('restore', $siswa);

        $this->siswaService->restore($siswa);

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Siswa berhasil dipulihkan.');
    }

    /**
     * Permanently delete a siswa (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $siswa = Siswa::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $siswa);

        $this->siswaService->forceDelete($siswa);

        return redirect()
            ->route('admin.siswa.index')
            ->with('success', 'Siswa berhasil dihapus permanen.');
    }
}