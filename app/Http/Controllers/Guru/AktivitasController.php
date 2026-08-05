<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateAktivitasRequest;
use App\Models\Aktivitas;
use App\Services\Interfaces\AktivitasServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller for Guru Aktivitas features.
 *
 * Guru can:
 * - View aktivitas of students under their guidance
 * - Validate (approve/reject) aktivitas
 * - Cannot edit/delete aktivitas content
 */
class AktivitasController extends Controller
{
    public function __construct(
        private readonly AktivitasServiceInterface $aktivitasService,
    ) {}

    /**
     * Display a listing of aktivitas for students under guru's guidance.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Guru|null $guru */
        $guru = $user->guru;

        if ($guru === null) {
            abort(403, 'Data guru tidak ditemukan.');
        }

        $aktivitasList = $this->aktivitasService->getGuruAktivitasPaginated($guru->id, [
            'search' => $request->query('search'),
            'tanggal' => $request->query('tanggal'),
            'status' => $request->query('status'),
            'periode_id' => $request->query('periode_id'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('guru.aktivitas.index', compact('aktivitasList'));
    }

    /**
     * Display the specified aktivitas detail.
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

        return view('guru.aktivitas.show', compact('aktivitas'));
    }

}
