<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Absensi;
use Illuminate\Support\Facades\DB;

use App\Services\Interfaces\AbsensiServiceInterface;

class RekapAbsensiService
{
    public function __construct(
        private readonly AbsensiServiceInterface $absensiService
    ) {}

    /**
     * Get Rekapitulasi Absensi for a specific penempatan_pkl.
     * 
     * @param int $penempatanPklId
     * @return array<string, mixed>
     */
    public function getRekap(int $penempatanPklId): array
    {
        $data = $this->absensiService->getRekapAbsensiData($penempatanPklId);

        return [
            'hadir_total' => $data['total_hadir'],
            'hadir_tepat_waktu' => $data['hadir'],
            'terlambat' => $data['terlambat'],
            'sangat_terlambat' => $data['sangat_terlambat'],
            'izin' => $data['izin'],
            'sakit' => $data['sakit'],
            'alfa' => $data['alpha'],
            'alpha' => $data['alpha'],
            'total_hari' => $data['total_hari'],
            'raw_data' => $data,
        ];
    }
}
