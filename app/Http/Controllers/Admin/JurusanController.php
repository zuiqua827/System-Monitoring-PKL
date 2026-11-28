<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJurusanRequest;
use App\Http\Requests\UpdateJurusanRequest;
use App\Models\Jurusan;
use App\Services\Interfaces\JurusanServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Jurusan CRUD.
 *
 * This controller only acts as a connector between HTTP requests and the Service layer.
 * NO business logic is allowed here.
 *
 * Authorization is handled via JurusanPolicy using $this->authorize().
 */
class JurusanController extends Controller
{
    public function __construct(
        private readonly JurusanServiceInterface $jurusanService,
    ) {}

    /**
     * Display a listing of jurusan.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Jurusan::class);

        $jurusans = $this->jurusanService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'kode'),
            sortDirection: $request->query('direction', 'asc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.jurusan.index', compact('jurusans'));
    }

    /**
     * Show the form for creating a new jurusan.
     */
    public function create(): View
    {
        $this->authorize('create', Jurusan::class);

        return view('admin.jurusan.create');
    }

    /**
     * Store a newly created jurusan in storage.
     */
    public function store(StoreJurusanRequest $request): RedirectResponse
    {
        $this->authorize('create', Jurusan::class);

        $this->jurusanService->store($request->validated());

        return redirect()
            ->route('admin.jurusan.index')
            ->with('success', 'Jurusan berhasil ditambahkan.');
    }

    /**
     * Display the specified jurusan.
     */
    public function show(Jurusan $jurusan): View
    {
        $this->authorize('view', $jurusan);

        $jurusan->loadCount('kelas');

        return view('admin.jurusan.show', compact('jurusan'));
    }

    /**
     * Show the form for editing the specified jurusan.
     */
    public function edit(Jurusan $jurusan): View
    {
        $this->authorize('update', $jurusan);

        return view('admin.jurusan.edit', compact('jurusan'));
    }

    /**
     * Update the specified jurusan in storage.
     */
    public function update(UpdateJurusanRequest $request, Jurusan $jurusan): RedirectResponse
    {
        $this->authorize('update', $jurusan);

        $this->jurusanService->update($jurusan, $request->validated());

        return redirect()
            ->route('admin.jurusan.index')
            ->with('success', 'Jurusan berhasil diperbarui.');
    }

    /**
     * Soft delete the specified jurusan.
     */
    public function destroy(Jurusan $jurusan): RedirectResponse
    {
        $this->authorize('delete', $jurusan);

        $this->jurusanService->destroy($jurusan);

        return redirect()
            ->route('admin.jurusan.index')
            ->with('success', 'Jurusan berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted jurusan.
     */
    public function restore(int $id): RedirectResponse
    {
        $jurusan = Jurusan::withTrashed()->findOrFail($id);

        $this->authorize('restore', $jurusan);

        $this->jurusanService->restore($jurusan);

        return redirect()
            ->route('admin.jurusan.index')
            ->with('success', 'Jurusan berhasil dipulihkan.');
    }

    /**
     * Permanently delete a jurusan (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $jurusan = Jurusan::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $jurusan);

        $this->jurusanService->forceDelete($jurusan);

        return redirect()
            ->route('admin.jurusan.index')
            ->with('success', 'Jurusan berhasil dihapus permanen.');
    }
}
