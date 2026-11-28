<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuruRequest;
use App\Http\Requests\UpdateGuruRequest;
use App\Models\Guru;
use App\Services\Interfaces\GuruServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for Master Guru CRUD.
 *
 * This controller only acts as a connector between HTTP requests and the Service layer.
 * NO business logic is allowed here.
 *
 * Authorization is handled via GuruPolicy using $this->authorize().
 */
class GuruController extends Controller
{
    public function __construct(
        private readonly GuruServiceInterface $guruService,
    ) {}

    /**
     * Display a listing of guru.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Guru::class);

        $gurus = $this->guruService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'nama'),
            sortDirection: $request->query('direction', 'asc'),
            perPage: (int) $request->query('per_page', '15'),
        );

        return view('admin.guru.index', compact('gurus'));
    }

    /**
     * Show the form for creating a new guru.
     */
    public function create(): View
    {
        $this->authorize('create', Guru::class);

        return view('admin.guru.create');
    }

    /**
     * Store a newly created guru in storage.
     */
    public function store(StoreGuruRequest $request): RedirectResponse
    {
        $this->authorize('create', Guru::class);

        $this->guruService->store($request->validated());

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Guru berhasil ditambahkan.');
    }

    /**
     * Display the specified guru.
     */
    public function show(Guru $guru): View
    {
        $this->authorize('view', $guru);

        $guru->load('user');
        $guru->loadCount('penempatan');

        return view('admin.guru.show', compact('guru'));
    }

    /**
     * Show the form for editing the specified guru.
     */
    public function edit(Guru $guru): View
    {
        $this->authorize('update', $guru);

        $guru->load('user');

        return view('admin.guru.edit', compact('guru'));
    }

    /**
     * Update the specified guru in storage.
     */
    public function update(UpdateGuruRequest $request, Guru $guru): RedirectResponse
    {
        $this->authorize('update', $guru);

        $this->guruService->update($guru, $request->validated());

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Guru berhasil diperbarui.');
    }

    /**
     * Soft delete the specified guru.
     */
    public function destroy(Guru $guru): RedirectResponse
    {
        $this->authorize('delete', $guru);

        $this->guruService->destroy($guru);

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Guru berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted guru.
     */
    public function restore(int $id): RedirectResponse
    {
        $guru = Guru::withTrashed()->findOrFail($id);

        $this->authorize('restore', $guru);

        $this->guruService->restore($guru);

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Guru berhasil dipulihkan.');
    }

    /**
     * Permanently delete a guru (Super Admin only).
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $guru = Guru::withTrashed()->findOrFail($id);

        $this->authorize('forceDelete', $guru);

        $this->guruService->forceDelete($guru);

        return redirect()
            ->route('admin.guru.index')
            ->with('success', 'Guru berhasil dihapus permanen.');
    }
}

