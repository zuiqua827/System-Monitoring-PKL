<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Absensi;
use Illuminate\Support\Facades\DB;

class RekapAbsensiService
{
    /**
     * Get Rekapitulasi Absensi for a specific penempatan_pkl.
     * 
     * @param int $penempatanPklId
     * @return array<string, int>
     */
    public function getRekap(int $penempatanPklId): array
    {
        $rekap = Absensi::where('penempatan_pkl_id', $penempatanPklId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $hadirNormal = $rekap['hadir'] ?? 0;
        $terlambat = $rekap['terlambat'] ?? 0;

        return [
            'hadir_total' => $hadirNormal + $terlambat, // Terlambat tetap dianggap hadir
            'hadir_tepat_waktu' => $hadirNormal,
            'terlambat' => $terlambat,
            'izin' => $rekap['izin'] ?? 0,
            'sakit' => $rekap['sakit'] ?? 0,
            'alfa' => $rekap['alfa'] ?? 0,
            'total_hari' => array_sum($rekap)
        ];
    }
}
