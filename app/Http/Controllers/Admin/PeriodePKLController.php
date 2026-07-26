<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodePKLRequest;
use App\Http\Requests\UpdatePeriodePKLRequest;
use App\Models\PeriodePKL;
use App\Services\Interfaces\PeriodePKLServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Periode PKL CRUD.
 *
 * This controller only acts as a connector between HTTP requests and the Service layer.
 * NO business logic is allowed here.
 *
 * Authorization is handled via PeriodePKLPolicy using $this->authorize().
 */
class PeriodePKLController extends Controller
{
    public function __construct(
        private readonly PeriodePKLServiceInterface $periodePklService,
    ) {}

    /**
     * Display a listing of periode PKL.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PeriodePKL::class);

        $periodePkls = $this->periodePklService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'nama'),
            sortDirection: $request->query('direction', 'asc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.periode-pkl.index', compact('periodePkls'));
    }

    /**
     * Show the form for creating a new periode PKL.
     */
    public function create(): View
    {
        $this->authorize('create', PeriodePKL::class);

        return view('admin.periode-pkl.create');
    }

    /**
     * Store a newly created periode PKL in storage.
     */
    public function store(StorePeriodePKLRequest $request): RedirectResponse
    {
        $this->authorize('create', PeriodePKL::class);

        $this->periodePklService->store($request->validated());

        return redirect()
            ->route('admin.periode-pkl.index')
            ->with('success', 'Periode PKL berhasil ditambahkan.');
    }

    /**
     * Display the specified periode PKL.
     */
    public function show(PeriodePKL $periodePkl): View
    {
        $this->authorize('view', $periodePkl);

        $periodePkl->loadCount('penempatan');

        return view('admin.periode-pkl.show', compact('periodePkl'));
    }

    /**
     * Show the form for editing the specified periode PKL.
     */
    public function edit(PeriodePKL $periodePkl): View
    {
        $this->authorize('update', $periodePkl);

        return view('admin.periode-pkl.edit', compact('periodePkl'));
    }

    /**
     * Update the specified periode PKL in storage.
     */
    public function update(UpdatePeriodePKLRequest $request, PeriodePKL $periodePkl): RedirectResponse
    {
        $this->authorize('update', $periodePkl);

        $this->periodePklService->update($periodePkl, $request->validated());

        return redirect()
            ->route('admin.periode-pkl.index')
            ->with('success', 'Periode PKL berhasil diperbarui.');
    }

    /**
     * Soft delete the specified periode PKL.
     */
    public function destroy(PeriodePKL $periodePkl): RedirectResponse
    {
        $this->authorize('delete', $periodePkl);

        $this->periodePklService->destroy($periodePkl);

        return redirect()
            ->route('admin.periode-pkl.index')
            ->with('success', 'Periode PKL berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted periode PKL.
     */
    public function restore(int $id): RedirectResponse
    {
        $periodePkl = PeriodePKL::withTrashed()->findOrFail($id);

        $this->authorize('restore', $periodePkl);

        $this->periodePklService->restore($periodePkl);

        return redirect()
            ->route('admin.periode-pkl.index')
            ->with('success', 'Periode PKL berhasil dipulihkan.');
    }

    /**
     * Permanently delete a periode PKL (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $periodePkl = PeriodePKL::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $periodePkl);

        $this->periodePklService->forceDelete($periodePkl);

        return redirect()
            ->route('admin.periode-pkl.index')
            ->with('success', 'Periode PKL berhasil dihapus permanen.');
    }
}
