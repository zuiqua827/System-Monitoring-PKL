<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenilaianRequest;
use App\Http\Requests\UpdatePenilaianRequest;
use App\Models\Penilaian;
use App\Services\Interfaces\PenilaianServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller for Guru Penilaian features.
 *
 * Guru can:
 * - View penilaian of students under their guidance
 * - Create penilaian for their students
 * - Edit draft penilaian
 * - Finalize penilaian (cannot edit after final)
 */
class PenilaianController extends Controller
{
    public function __construct(
        private readonly PenilaianServiceInterface $penilaianService,
    ) {}

    /**
     * Display a listing of penilaian for students under guru's guidance.
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

        $penilaianList = $this->penilaianService->getGuruPenilaianPaginated($guru->id, [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'periode_id' => $request->query('periode_id'),
            'sort_by' => $request->query('sort', 'created_at'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('guru.penilaian.index', compact('penilaianList'));
    }

    /**
     * Display the specified penilaian.
     */
    public function show(int $id): View
    {
        $penilaian = $this->penilaianService->findOrFail($id);

        $this->authorize('view', $penilaian);

        $penilaian->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
            'dinilaiOleh',
        ]);

        return view('guru.penilaian.show', compact('penilaian'));
    }
}
