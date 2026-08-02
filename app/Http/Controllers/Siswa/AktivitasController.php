<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAktivitasRequest;
use App\Http\Requests\UpdateAktivitasRequest;
use App\Models\Aktivitas;
use App\Models\PenempatanPKL;
use App\Services\Interfaces\AktivitasServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller for Siswa Aktivitas features.
 *
 * Siswa can:
 * - Create aktivitas
 * - Edit own draft aktivitas
 * - Delete own draft aktivitas
 * - Submit for validation
 * - View own aktivitas history
 */
class AktivitasController extends Controller
{
    public function __construct(
        private readonly AktivitasServiceInterface $aktivitasService,
    ) {}

    /**
     * Display a listing of the siswa's own aktivitas.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            abort(403, 'Data siswa tidak ditemukan.');
        }

        // Get active penempatan
        $penempatanAktif = PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->first();

        // Get paginated aktivitas history
        $aktivitasList = $this->aktivitasService->getSiswaAktivitasPaginated($siswa->id, [
            'tanggal' => $request->query('tanggal'),
            'status' => $request->query('status'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('siswa.aktivitas.index', compact(
            'aktivitasList',
            'penempatanAktif',
            'siswa'
        ));
    }

    /**
     * Show the form for creating a new aktivitas.
     */
    public function create(): View
    {
        $this->authorize('create', Aktivitas::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            abort(403, 'Data siswa tidak ditemukan.');
        }

        $penempatanAktif = PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->first();

        if ($penempatanAktif === null) {
            abort(403, 'Anda tidak memiliki penempatan PKL yang aktif.');
        }

        return view('siswa.aktivitas.create', compact('penempatanAktif', 'siswa'));
    }

    /**
     * Store a newly created aktivitas.
     */
    public function store(StoreAktivitasRequest $request): RedirectResponse
    {
        $this->authorize('create', Aktivitas::class);

        try {
            $data = $request->validated();

            // Handle photo upload
            if ($request->hasFile('foto_kegiatan')) {
                $data['foto_kegiatan'] = $request->file('foto_kegiatan');
            }

            $this->aktivitasService->store($data);

            return redirect()
                ->route('siswa.aktivitas.index')
                ->with('success', 'Aktivitas berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Siswa tambah aktivitas gagal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menambahkan aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified aktivitas.
     */
    public function show(int $id): View
    {
        $aktivitas = $this->aktivitasService->findOrFail($id);

        $this->authorize('view', $aktivitas);

        $aktivitas->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
            'validatedBy',
        ]);

        return view('siswa.aktivitas.show', compact('aktivitas'));
    }

    /**
     * Show the form for editing the specified aktivitas.
     */
    public function edit(int $id): View
    {
        $aktivitas = $this->aktivitasService->findOrFail($id);

        $this->authorize('update', $aktivitas);

        $aktivitas->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('siswa.aktivitas.edit', compact('aktivitas'));
    }

    /**
     * Update the specified aktivitas.
     */
    public function update(UpdateAktivitasRequest $request, int $id): RedirectResponse
    {
        $aktivitas = $this->aktivitasService->findOrFail($id);

        $this->authorize('update', $aktivitas);

        try {
            $data = $request->validated();

            // Handle photo upload
            if ($request->hasFile('foto_kegiatan')) {
                $data['foto_kegiatan'] = $request->file('foto_kegiatan');
            }

            $this->aktivitasService->update($aktivitas, $data);

            return redirect()
                ->route('siswa.aktivitas.index')
                ->with('success', 'Aktivitas berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Siswa update aktivitas gagal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified aktivitas (soft delete).
     */
    public function destroy(int $id): RedirectResponse
    {
        $aktivitas = $this->aktivitasService->findOrFail($id);

        $this->authorize('delete', $aktivitas);

        try {
            $this->aktivitasService->destroy($aktivitas);

            return redirect()
                ->route('siswa.aktivitas.index')
                ->with('success', 'Aktivitas berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus aktivitas: ' . $e->getMessage());
        }
    }

    /**
     * Submit aktivitas for validation.
     */
    public function submit(int $id): RedirectResponse
    {
        $aktivitas = $this->aktivitasService->findOrFail($id);

        $this->authorize('submit', $aktivitas);

        try {
            $this->aktivitasService->submit($aktivitas);

            return redirect()
                ->route('siswa.aktivitas.index')
                ->with('success', 'Aktivitas berhasil dikirim untuk divalidasi.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Submit aktivitas gagal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Gagal mengirim aktivitas.');
        }
    }
}

