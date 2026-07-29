<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenempatanPKLRequest;
use App\Http\Requests\UpdatePenempatanPKLRequest;
use App\Models\PenempatanPKL;
use App\Services\Interfaces\PenempatanPKLServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Penempatan PKL CRUD.
 */
class PenempatanPKLController extends Controller
{
    public function __construct(
        private readonly PenempatanPKLServiceInterface $penempatanPklService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PenempatanPKL::class);

        $penempatanPkls = $this->penempatanPklService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'created_at'),
            sortDirection: $request->query('direction', 'desc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.penempatan-pkl.index', compact('penempatanPkls'));
    }

    public function create(): View
    {
        $this->authorize('create', PenempatanPKL::class);

        return view('admin.penempatan-pkl.create');
    }

    public function store(StorePenempatanPKLRequest $request): RedirectResponse
    {
        $this->authorize('create', PenempatanPKL::class);

        $this->penempatanPklService->store($request->validated());

        return redirect()
            ->route('admin.penempatan-pkl.index')
            ->with('success', 'Penempatan PKL berhasil ditambahkan.');
    }

    public function show(PenempatanPKL $penempatanPkl): View
    {
        $this->authorize('view', $penempatanPkl);

        $penempatanPkl->load(['siswa', 'guru', 'dudi', 'periodePKL', 'dibuatOleh', 'approvedBy']);
        $penempatanPkl->loadCount(['absensi', 'aktivitas']);

        return view('admin.penempatan-pkl.show', compact('penempatanPkl'));
    }

    public function edit(PenempatanPKL $penempatanPkl): View
    {
        $this->authorize('update', $penempatanPkl);

        $penempatanPkl->load(['siswa', 'guru', 'dudi', 'periodePKL']);

        return view('admin.penempatan-pkl.edit', compact('penempatanPkl'));
    }

    public function update(UpdatePenempatanPKLRequest $request, PenempatanPKL $penempatanPkl): RedirectResponse
    {
        $this->authorize('update', $penempatanPkl);

        $this->penempatanPklService->update($penempatanPkl, $request->validated());

        return redirect()
            ->route('admin.penempatan-pkl.index')
            ->with('success', 'Penempatan PKL berhasil diperbarui.');
    }

    public function destroy(PenempatanPKL $penempatanPkl): RedirectResponse
    {
        $this->authorize('delete', $penempatanPkl);

        $this->penempatanPklService->destroy($penempatanPkl);

        return redirect()
            ->route('admin.penempatan-pkl.index')
            ->with('success', 'Penempatan PKL berhasil dihapus.');
    }

    public function restore(int $id): RedirectResponse
    {
        $penempatanPkl = PenempatanPKL::withTrashed()->findOrFail($id);

        $this->authorize('restore', $penempatanPkl);

        $this->penempatanPklService->restore($penempatanPkl);

        return redirect()
            ->route('admin.penempatan-pkl.index')
            ->with('success', 'Penempatan PKL berhasil dipulihkan.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $penempatanPkl = PenempatanPKL::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $penempatanPkl);

        $this->penempatanPklService->forceDelete($penempatanPkl);

        return redirect()
            ->route('admin.penempatan-pkl.index')
            ->with('success', 'Penempatan PKL berhasil dihapus permanen.');
    }
}
