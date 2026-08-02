<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\PenilaianServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for Siswa Penilaian features.
 *
 * Siswa can:
 * - View their own penilaian results
 * - Cannot create, edit, or delete
 */
class PenilaianController extends Controller
{
    public function __construct(
        private readonly PenilaianServiceInterface $penilaianService,
    ) {}

    /**
     * Display a listing of penilaian for the authenticated siswa.
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

        $penilaianList = $this->penilaianService->getSiswaPenilaianPaginated($siswa->id, [
            'sort_by' => $request->query('sort', 'created_at'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('siswa.penilaian.index', compact('penilaianList'));
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

        return view('siswa.penilaian.show', compact('penilaian'));
    }
}
