<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenempatanPKLRequest;
use App\Http\Requests\UpdatePenempatanPKLRequest;
use App\Enums\UserRole;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\User;
use App\Services\Interfaces\PenempatanPKLServiceInterface;
use App\Services\Interfaces\SiswaServiceInterface;
use Illuminate\Http\JsonResponse;
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
        private readonly SiswaServiceInterface $siswaService,
    ) {}

    /**
     * AJAX endpoint: search students for the searchable select.
     *
     * Searches by nama, NIS, NISN, kelas, or jurusan. Returns a limited
     * JSON list (no full-table load). Only Super Admin can access.
     */
    public function searchStudents(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user === null || ! $user->hasRole(UserRole::SUPER_ADMIN->value)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $search = (string) $request->query('q', '');

        $students = $this->siswaService->searchForSelect($search);

        return response()->json(['data' => $students]);
    }

public function index(Request $request): View
    {
        $this->authorize('viewAny', PenempatanPKL::class);

        $jurusanId = $request->integer('jurusan_id');
        $kelasId = $request->integer('kelas_id');
        $dudiId = $request->integer('dudi_id');
        $guruId = $request->integer('guru_id');
        $status = $request->query('status');

        $penempatanPkls = $this->penempatanPklService->getPaginated(
            keyword: $request->query('search'),
            sortBy: $request->query('sort', 'created_at'),
            sortDirection: $request->query('direction', 'desc'),
            perPage: (int) $request->query('per_page', '15'),
            jurusanId: $jurusanId ?: null,
            kelasId: $kelasId ?: null,
            dudiId: $dudiId ?: null,
            guruId: $guruId ?: null,
            status: $status ?: null,
        );

        $jurusans = Jurusan::query()->orderBy('nama')->get();
        $kelass = Kelas::query()->orderBy('nama')->get();
        $dudis = Dudi::query()->orderBy('nama_perusahaan')->get();
        $gurus = Guru::query()->orderBy('nama')->get();
        $periodes = PeriodePKL::query()->orderBy('nama')->get();

        return view('admin.penempatan-pkl.index', compact(
            'penempatanPkls',
            'jurusans',
            'kelass',
            'dudis',
            'gurus',
            'periodes',
        ));
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
