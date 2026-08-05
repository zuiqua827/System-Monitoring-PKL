<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\PenempatanPKLServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaController extends Controller
{
    public function __construct(
        private readonly PenempatanPKLServiceInterface $penempatanPKLService
    ) {}

    public function index(Request $request): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $keyword = $request->query('search');
        $sortBy = $request->query('sort', 'created_at');
        $sortDirection = $request->query('direction', 'desc');

        $siswaList = $this->penempatanPKLService->getDudiSiswaPaginated(
            $dudi->id,
            $keyword,
            $sortBy,
            $sortDirection,
            15
        );

        return view('dudi.siswa.index', compact('siswaList'));
    }

    public function show(int $id): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $penempatan = $this->penempatanPKLService->findOrFail($id);

        if ($penempatan->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak memiliki akses ke data siswa ini.');
        }

        $penempatan->load(['siswa.kelas.jurusan', 'guru', 'periodePKL']);

        return view('dudi.siswa.show', compact('penempatan'));
    }
}
