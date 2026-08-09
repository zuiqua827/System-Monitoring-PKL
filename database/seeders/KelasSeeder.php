<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $tahunAjaran = '2025/2026';
        $tingkat = 12;

        $kelasByJurusan = [
            'MPLB' => ['XII MPLB 1', 'XII MPLB 2', 'XII MPLB 3'],
            'AKL' => ['XII AKL 1', 'XII AKL 2'],
            'PPLG' => ['XII PPLG 1', 'XII PPLG 2'],
            'TO' => ['XII TO 1', 'XII TO 2'],
            'PM' => ['XII PM 1', 'XII PM 2'],
        ];

        $total = 0;

        foreach ($kelasByJurusan as $kodeJurusan => $rombelList) {
            $jurusan = Jurusan::where('kode', $kodeJurusan)->first();

            if ($jurusan === null) {
                $this->command->warn("Jurusan dengan kode '{$kodeJurusan}' tidak ditemukan. Dilewati.");
                continue;
            }

            foreach ($rombelList as $namaKelas) {
                Kelas::updateOrCreate(
                    [
                        'jurusan_id' => $jurusan->id,
                        'nama' => $namaKelas,
                        'tahun_ajaran' => $tahunAjaran,
                    ],
                    [
                        'tingkat' => $tingkat,
                    ]
                );

                $total++;
            }
        }

        $this->command->info("Seeder Kelas: {$total} data berhasil dibuat untuk tahun ajaran {$tahunAjaran}.");
    }
}
