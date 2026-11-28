<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengajuanKetidakhadiranRequest;
use App\Models\PenempatanPKL;
use App\Services\Interfaces\PengajuanKetidakhadiranServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PengajuanKetidakhadiranController extends Controller
{
    public function __construct(
        private readonly PengajuanKetidakhadiranServiceInterface $pengajuanService,
    ) {}

    /**
     * Tampilkan halaman daftar pengajuan & form.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $siswa = $user->siswa;

        if ($siswa === null) {
            abort(403, 'Data siswa tidak ditemukan.');
        }

        // Get active penempatan
        $penempatanAktif = PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->first();

        $pengajuans = $this->pengajuanService->getSiswaPengajuanPaginated($siswa->id, [
            'status' => $request->query('status'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('siswa.ketidakhadiran.index', compact('pengajuans', 'penempatanAktif'));
    }

    /**
     * Simpan pengajuan ketidakhadiran.
     */
    public function store(StorePengajuanKetidakhadiranRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $siswa = $user->siswa;

        if ($siswa === null) {
            return redirect()->back()->with('error', 'Data siswa tidak ditemukan.');
        }

        $penempatanAktif = PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->first();

        if ($penempatanAktif === null) {
            return redirect()->back()->with('error', 'Anda tidak memiliki penempatan PKL yang aktif.');
        }
        
        // Cek validasi tanggal agar dalam range periode PKL
        $tanggalPengajuan = \Carbon\Carbon::parse($request->input('tanggal'));
        $tanggalMulai = \Carbon\Carbon::parse($penempatanAktif->tanggal_mulai ?? $penempatanAktif->periodePKL->tanggal_mulai);
        $tanggalSelesai = \Carbon\Carbon::parse($penempatanAktif->tanggal_selesai ?? $penempatanAktif->periodePKL->tanggal_selesai);
        
        if ($tanggalPengajuan->lt($tanggalMulai) || $tanggalPengajuan->gt($tanggalSelesai)) {
            return redirect()->back()->with('error', 'Tanggal pengajuan di luar masa PKL Anda.');
        }

        try {
            $data = $request->validated();
            $data['penempatan_pkl_id'] = $penempatanAktif->id;

            if ($request->hasFile('lampiran')) {
                $data['lampiran'] = $request->file('lampiran')->store('pengajuan_ketidakhadiran', 'public');
            }

            $this->pengajuanService->storePengajuan($data);

            return redirect()->route('siswa.ketidakhadiran.index')->with('success', 'Pengajuan ketidakhadiran berhasil dikirim.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            Log::error('Gagal mengirim pengajuan ketidakhadiran: ' . $e->getMessage(), [
                'siswa_id' => $siswa->id,
            ]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan pengajuan.')->withInput();
        }
    }
}
