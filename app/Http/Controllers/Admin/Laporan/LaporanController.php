<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Laporan;

use App\Exports\AbsensiReportExcelExporter;
use App\Http\Controllers\Controller;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PeriodePKL;
use App\Services\Laporan\Interfaces\LaporanServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanServiceInterface $laporanService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.laporan.index');
    }

    public function siswa(Request $request): View
    {
        $filters = $request->only(['periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id', 'status', 'per_page']);
        
        $penempatanPkls = $this->laporanService->getAdminSummary($filters);
        
        $periodes = PeriodePKL::query()->orderBy('nama')->get();
        $jurusans = Jurusan::query()->orderBy('nama')->get();
        $kelass = Kelas::query()->orderBy('nama')->get();
        $gurus = Guru::query()->orderBy('nama')->get();
        $dudis = Dudi::query()->orderBy('nama_perusahaan')->get();
        
        return view('admin.laporan.siswa', compact('penempatanPkls', 'periodes', 'jurusans', 'kelass', 'gurus', 'dudis'));
    }

    public function absensi(Request $request): View
    {
        $filters = $request->only(['periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id', 'status', 'tanggal_mulai', 'tanggal_akhir', 'per_page']);
        
        $report = $this->laporanService->getAdminAbsensiReport($filters);
        $absensis = $report['data'];
        $stats = $report['stats'];
        
        $periodes = PeriodePKL::query()->orderBy('nama')->get();
        $jurusans = Jurusan::query()->orderBy('nama')->get();
        $kelass = Kelas::query()->orderBy('nama')->get();
        $gurus = Guru::query()->orderBy('nama')->get();
        $dudis = Dudi::query()->orderBy('nama_perusahaan')->get();
        
        return view('admin.laporan.absensi', compact('absensis', 'stats', 'periodes', 'jurusans', 'kelass', 'gurus', 'dudis'));
    }

    public function aktivitas(Request $request): View
    {
        $filters = $request->only(['periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id', 'status', 'tanggal_mulai', 'tanggal_akhir', 'per_page']);
        
        $report = $this->laporanService->getAdminAktivitasReport($filters);
        $aktivitas = $report['data'];
        $stats = $report['stats'];
        
        $periodes = PeriodePKL::query()->orderBy('nama')->get();
        $jurusans = Jurusan::query()->orderBy('nama')->get();
        $kelass = Kelas::query()->orderBy('nama')->get();
        $gurus = Guru::query()->orderBy('nama')->get();
        $dudis = Dudi::query()->orderBy('nama_perusahaan')->get();
        
        return view('admin.laporan.aktivitas', compact('aktivitas', 'stats', 'periodes', 'jurusans', 'kelass', 'gurus', 'dudis'));
    }

    public function penilaian(Request $request): View
    {
        $filters = $request->only(['periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id', 'status', 'tanggal_mulai', 'tanggal_akhir', 'per_page']);
        
        $report = $this->laporanService->getAdminPenilaianReport($filters);
        $penilaian = $report['data'];
        $stats = $report['stats'];
        
        $periodes = PeriodePKL::query()->orderBy('nama')->get();
        $jurusans = Jurusan::query()->orderBy('nama')->get();
        $kelass = Kelas::query()->orderBy('nama')->get();
        $gurus = Guru::query()->orderBy('nama')->get();
        $dudis = Dudi::query()->orderBy('nama_perusahaan')->get();
        
        return view('admin.laporan.penilaian', compact('penilaian', 'stats', 'periodes', 'jurusans', 'kelass', 'gurus', 'dudis'));
    }

    public function exportSiswaExcel(Request $request)
    {
        $filters = $request->only(['periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id', 'status']);
        $query = $this->laporanService->getAdminSiswaExportQuery($filters);
        
        if ($query->count() === 0) {
            return back()->with('error', 'Tidak ada data untuk diekspor berdasarkan filter yang dipilih.');
        }

        $fileName = 'laporan-siswa-pkl-' . now()->format('Y-m-d-His') . '.xlsx';
        
        return response()->streamDownload(function () use ($query) {
            $writer = \Spatie\SimpleExcel\SimpleExcelWriter::stream('php://output', 'xlsx');
            
            $no = 1;
            $query->chunk(100, function ($records) use ($writer, &$no) {
                foreach ($records as $item) {
                    $writer->addRow([
                        'No' => $no++,
                        'NIS' => $item->siswa->nis ?? '-',
                        'NISN' => $item->siswa->nisn ?? '-',
                        'Nama Siswa' => $item->siswa->nama ?? '-',
                        'Jenis Kelamin' => $item->siswa->jenis_kelamin ?? '-',
                        'Kelas' => $item->siswa->kelas->nama ?? '-',
                        'Jurusan' => $item->siswa->kelas->jurusan->singkatan ?? '-',
                        'No Telepon' => $item->siswa->no_telepon ?? '-',
                        'Alamat' => $item->siswa->alamat ?? '-',
                        'Tempat PKL / DUDI' => $item->dudi->nama_perusahaan ?? '-',
                        'Alamat DUDI' => $item->dudi->alamat ?? '-',
                        'Guru Pembimbing' => $item->guru->nama ?? '-',
                        'Periode PKL' => $item->periodePKL->nama ?? '-',
                        'Tanggal Mulai' => $item->tanggal_mulai ? \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/Y') : '-',
                        'Tanggal Selesai' => $item->tanggal_selesai ? \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/Y') : '-',
                        'Status PKL' => ucfirst($item->status),
                    ]);
                }
            });
            
            $writer->close();
        }, $fileName);
    }

    public function exportSiswaPdf(Request $request)
    {
        $filters = $request->only([
            'periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id', 'status', 'search',
        ]);
        $report = $this->laporanService->getAdminSiswaPdfReport($filters);

        if ($report['is_over_limit']) {
            return back()->with(
                'error',
                "Data hasil filter melebihi {$report['limit']} baris. Silakan persempit filter sebelum mengekspor PDF.",
            );
        }

        if ($report['data']->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk diekspor berdasarkan filter yang dipilih.');
        }

        $printedAt = now();
        $pdf = Pdf::loadView('pdf.laporan.siswa', [
            'penempatanPkls' => $report['data'],
            'appliedFilters' => $report['applied_filters'],
            'systemName' => (string) config('app.name', 'Sistem Monitoring PKL'),
            'printedAt' => $printedAt,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-siswa-pkl-'.$printedAt->format('Y-m-d-His').'.pdf');
    }

    public function exportAbsensiExcel(Request $request)
    {
        $filters = $request->only([
            'periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id',
            'tanggal_mulai', 'tanggal_akhir', 'status',
        ]);
        $export = $this->laporanService->getAdminAbsensiExport($filters);

        if (!$export['query']->exists()) {
            return back()->with('error', 'Tidak ada data absensi untuk diekspor berdasarkan filter yang dipilih.');
        }

        $fileName = 'laporan-absensi-pkl-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($export): void {
            (new AbsensiReportExcelExporter())->stream($export['query'], $export['stats']);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
