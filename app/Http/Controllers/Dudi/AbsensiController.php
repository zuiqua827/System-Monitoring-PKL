<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    public function __construct(
        private readonly AbsensiServiceInterface $absensiService
    ) {}

    public function index(Request $request): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $filters = $request->only(['search', 'tanggal', 'status', 'periode_id']);
        $filters['sort_by'] = $request->query('sort', 'tanggal');
        $filters['sort_direction'] = $request->query('direction', 'desc');

        $absensiList = $this->absensiService->getDudiAbsensiPaginated($dudi->id, $filters);

        // Required for filter dropdowns if we want to show periode (fetch from PeriodePKLRepository)
        $periodeList = \App\Models\PeriodePKL::where('status', 'aktif')->get();

        return view('dudi.absensi.index', compact('absensiList', 'periodeList'));
    }

    public function show(int $id): View
    {
        $dudi = Auth::user()->dudi;
        if (!$dudi) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        $absensi = $this->absensiService->findOrFail($id);
        $absensi->load(['penempatanPKL.siswa.kelas.jurusan', 'penempatanPKL.guru']);

        if ($absensi->penempatanPKL->dudi_id !== $dudi->id) {
            abort(403, 'Anda tidak memiliki akses ke absensi ini.');
        }

        return view('dudi.absensi.show', compact('absensi'));
    }
}
