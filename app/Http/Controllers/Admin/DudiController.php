<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDudiRequest;
use App\Http\Requests\UpdateDudiRequest;
use App\Models\Dudi;
use App\Services\Interfaces\DudiServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master DUDI CRUD.
 *
 * This controller only acts as a connector between HTTP requests and the Service layer.
 * NO business logic is allowed here.
 *
 * Authorization is handled via DudiPolicy using $this->authorize().
 */
class DudiController extends Controller
{
    public function __construct(
        private readonly DudiServiceInterface $dudiService,
    ) {}

    /**
     * Display a listing of DUDI.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Dudi::class);

        $dudis = $this->dudiService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'nama_perusahaan'),
            sortDirection: $request->query('direction', 'asc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.dudi.index', compact('dudis'));
    }

    /**
     * Show the form for creating a new DUDI.
     */
    public function create(): View
    {
        $this->authorize('create', Dudi::class);

        return view('admin.dudi.create');
    }

    /**
     * Store a newly created DUDI in storage.
     */
    public function store(StoreDudiRequest $request): RedirectResponse
    {
        $this->authorize('create', Dudi::class);

        $this->dudiService->store($request->validated());

        return redirect()
            ->route('admin.dudi.index')
            ->with('success', 'DUDI berhasil ditambahkan.');
    }

    /**
     * Display the specified DUDI.
     */
    public function show(Dudi $dudi): View
    {
        $this->authorize('view', $dudi);

        $dudi->load('user');
        $dudi->loadCount('penempatan');

        return view('admin.dudi.show', compact('dudi'));
    }

    /**
     * Show the form for editing the specified DUDI.
     */
    public function edit(Dudi $dudi): View
    {
        $this->authorize('update', $dudi);

        $dudi->load('user');

        return view('admin.dudi.edit', compact('dudi'));
    }

    /**
     * Update the specified DUDI in storage.
     */
    public function update(UpdateDudiRequest $request, Dudi $dudi): RedirectResponse
    {
        $this->authorize('update', $dudi);

        $this->dudiService->update($dudi, $request->validated());

        return redirect()
            ->route('admin.dudi.index')
            ->with('success', 'DUDI berhasil diperbarui.');
    }

    /**
     * Soft delete the specified DUDI.
     */
    public function destroy(Dudi $dudi): RedirectResponse
    {
        $this->authorize('delete', $dudi);

        $this->dudiService->destroy($dudi);

        return redirect()
            ->route('admin.dudi.index')
            ->with('success', 'DUDI berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted DUDI.
     */
    public function restore(int $id): RedirectResponse
    {
        $dudi = Dudi::withTrashed()->findOrFail($id);

        $this->authorize('restore', $dudi);

        $this->dudiService->restore($dudi);

        return redirect()
            ->route('admin.dudi.index')
            ->with('success', 'DUDI berhasil dipulihkan.');
    }

    /**
     * Permanently delete a DUDI (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $dudi = Dudi::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $dudi);

        $this->dudiService->forceDelete($dudi);

        return redirect()
            ->route('admin.dudi.index')
            ->with('success', 'DUDI berhasil dihapus permanen.');
    }
}
