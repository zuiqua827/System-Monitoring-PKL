<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\PenempatanPKL;
use App\Services\Interfaces\AbsensiServiceInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAlfaAbsensi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absensi:mark-alfa {--date= : Tanggal spesifik YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis menandai siswa Alfa jika tidak check-in dan tidak ada izin/sakit pada hari PKL aktif.';

    /**
     * Execute the console command.
     */
    public function handle(AbsensiServiceInterface $absensiService): int
    {
        $dateStr = $this->option('date');
        $targetDate = $dateStr ? Carbon::parse($dateStr)->startOfDay() : now()->subDay()->startOfDay();
        $dateFormatted = $targetDate->format('Y-m-d');
        
        // Lewati weekend (Sabtu/Minggu) jika diasumsikan PKL hanya Senin-Jumat
        // Tapi ini bisa tergantung kebijakan. Kita buat skip minggu saja by default.
        // A running day must never be marked Alfa before it has ended.
        if (!$targetDate->lt(Carbon::today(config('app.timezone')))) {
            $this->info("Tanggal {$dateFormatted} belum selesai. Tidak ada proses Alfa.");
            return 0;
        }

        $this->info("Memulai proses pengecekan Alfa untuk tanggal: {$dateFormatted}");

        $penempatans = PenempatanPKL::where('status', 'aktif')
            ->where(function ($q) use ($dateFormatted) {
                $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $dateFormatted);
            })
            ->where(function ($q) use ($dateFormatted) {
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $dateFormatted);
            })
            ->get();

        $countAlfa = 0;

        foreach ($penempatans as $penempatan) {
            // Cek apakah ada record absensi
            $rekap = $absensiService->getRekapPresensi($penempatan->id);
            $isBolos = in_array($dateFormatted, $rekap['bolos'], true);

            if ($isBolos && !Absensi::where('penempatan_pkl_id', $penempatan->id)
                ->whereDate('tanggal', $dateFormatted)
                ->exists()) {
                Absensi::create([
                    'penempatan_pkl_id' => $penempatan->id,
                    'tanggal' => $dateFormatted,
                    'status' => 'alpha',
                    'keterangan' => 'Sistem Otomatis (Tidak ada check-in/pengajuan)',
                ]);
                $countAlfa++;
            }
        }

        $this->info("Proses selesai. {$countAlfa} siswa ditandai sebagai Alfa pada {$dateFormatted}.");
        return 0;
    }
}
