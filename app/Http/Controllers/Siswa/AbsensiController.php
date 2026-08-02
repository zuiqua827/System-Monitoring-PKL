<?php

declare(strict_types=1);

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Models\PenempatanPKL;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Controller for Siswa Absensi features (Check In, Check Out, own absensi list).
 *
 * Handles camera-based check-in/out with GPS and radius validation.
 */
class AbsensiController extends Controller
{
    public function __construct(
        private readonly AbsensiServiceInterface $absensiService,
    ) {}

    /**
     * Display a listing of the siswa's own absensi.
     *
     * Provides:
     * - Today's absensi status (check-in/out)
     * - Watermark data for camera (nama_siswa, nama_dudi, etc.)
     * - Paginated history
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
            ->with(['dudi', 'periodePKL'])
            ->first();

        // Get today's absensi if exists
        $todayAbsensi = null;
        if ($penempatanAktif !== null) {
            $todayAbsensi = $this->absensiService->getTodayAbsensi($penempatanAktif->id);
        }

        // Prepare watermark data for camera
        $watermarkData = null;
        if ($penempatanAktif !== null) {
            $watermarkData = [
                'nama_siswa' => $siswa->nama,
                'nama_dudi' => $penempatanAktif->dudi->nama_perusahaan,
                'nis' => $siswa->nis,
            ];
        }

        // Determine check-in/out status
        $sudahCheckIn = $todayAbsensi !== null && $todayAbsensi->jam_masuk !== null;
        $sudahCheckOut = $todayAbsensi !== null && $todayAbsensi->jam_keluar !== null;

        // Get paginated absensi history
        $absensis = $this->absensiService->getSiswaAbsensiPaginated($siswa->id, [
            'tanggal' => $request->query('tanggal'),
            'status' => $request->query('status'),
            'sort_by' => $request->query('sort', 'tanggal'),
            'sort_direction' => $request->query('direction', 'desc'),
            'per_page' => (int) $request->query('per_page', '15'),
        ]);

        return view('siswa.absensi.index', compact(
            'absensis',
            'penempatanAktif',
            'todayAbsensi',
            'siswa',
            'watermarkData',
            'sudahCheckIn',
            'sudahCheckOut'
        ));
    }

    /**
     * Process Check In with camera photo and GPS.
     */
    public function checkIn(CheckInRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            return redirect()
                ->back()
                ->with('error', 'Data siswa tidak ditemukan.');
        }

        // Find active penempatan
        $penempatanAktif = PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->first();

        if ($penempatanAktif === null) {
            return redirect()
                ->back()
                ->with('error', 'Anda tidak memiliki penempatan PKL yang aktif.');
        }

        $this->authorize('checkIn', $penempatanAktif);

        try {
            $data = $request->validated();

            // Handle file upload (fallback if camera not supported)
            if ($request->hasFile('foto_masuk')) {
                $data['foto_masuk'] = $request->file('foto_masuk')->store('absensi/foto_masuk', 'public');
            }

            // If base64 from camera, keep it in data for service to process
            // If file upload, keep it in data

            $this->absensiService->checkIn($penempatanAktif->id, $data);

            return redirect()
                ->route('siswa.absensi.index')
                ->with('success', 'Check In berhasil!');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Check In gagal: ' . $e->getMessage(), [
                'siswa_id' => $siswa->id,
                'penempatan_pkl_id' => $penempatanAktif->id,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat Check In.');
        }
    }

    /**
     * Process Check Out with camera photo and GPS.
     */
    public function checkOut(CheckOutRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            return redirect()
                ->back()
                ->with('error', 'Data siswa tidak ditemukan.');
        }

        // Find active penempatan
        $penempatanAktif = PenempatanPKL::where('siswa_id', $siswa->id)
            ->where('status', 'aktif')
            ->first();

        if ($penempatanAktif === null) {
            return redirect()
                ->back()
                ->with('error', 'Anda tidak memiliki penempatan PKL yang aktif.');
        }

        $this->authorize('checkOut', $penempatanAktif);

        try {
            $data = $request->validated();

            // Handle file upload (fallback if camera not supported)
            if ($request->hasFile('foto_pulang')) {
                $data['foto_pulang'] = $request->file('foto_pulang')->store('absensi/foto_pulang', 'public');
            }

            $this->absensiService->checkOut($penempatanAktif->id, $data);

            return redirect()
                ->route('siswa.absensi.index')
                ->with('success', 'Check Out berhasil!');
        } catch (\RuntimeException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Check Out gagal: ' . $e->getMessage(), [
                'siswa_id' => $siswa->id,
                'penempatan_pkl_id' => $penempatanAktif->id,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat Check Out.');
        }
    }

    /**
     * Display the specified absensi detail for siswa.
     */
    public function show(int $id): View
    {
        $absensi = $this->absensiService->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var \App\Models\Siswa|null $siswa */
        $siswa = $user->siswa;

        if ($siswa === null) {
            abort(403, 'Data siswa tidak ditemukan.');
        }

        // Ensure siswa only sees own absensi
        if ($absensi->penempatanPKL->siswa_id !== $siswa->id) {
            abort(403, 'Anda tidak berhak melihat absensi ini.');
        }

        $absensi->load([
            'penempatanPKL.siswa',
            'penempatanPKL.guru',
            'penempatanPKL.dudi',
            'penempatanPKL.periodePKL',
        ]);

        return view('siswa.absensi.show', compact('absensi'));
    }
}
