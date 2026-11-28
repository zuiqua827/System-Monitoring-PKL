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

        $kelasDefinitions = [
            10 => [
                'AKL' => ['X AKL 1', 'X AKL 2'],
                'MPLB' => ['X MPLB 1', 'X MPLB 2', 'X MPLB 3'],
                'PM' => ['X PM 1', 'X PM 2'],
                'PPLG' => ['X PPLG 1', 'X PPLG 2'],
                'TO' => ['X TO 1', 'X TO 2'],
            ],
            11 => [
                'AKL' => ['XI AKL 1', 'XI AKL 2'],
                'MPLB' => ['XI MPLB 1', 'XI MPLB 2', 'XI MPLB 3'],
                'PM' => ['XI PM 1', 'XI PM 2'],
                'PPLG' => ['XI PPLG 1', 'XI PPLG 2'],
                'TO' => ['XI TO 1', 'XI TO 2'],
            ],
            12 => [
                'AKL' => ['XII AKL 1', 'XII AKL 2'],
                'MPLB' => ['XII MPLB 1', 'XII MPLB 2', 'XII MPLB 3'],
                'PM' => ['XII PM 1', 'XII PM 2'],
                'PPLG' => ['XII PPLG 1', 'XII PPLG 2'],
                'TO' => ['XII TO 1', 'XII TO 2'],
            ],
        ];

        $total = 0;

        foreach ($kelasDefinitions as $tingkat => $byJurusan) {
            foreach ($byJurusan as $kodeJurusan => $rombelList) {
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
        }

        $this->command->info("Seeder Kelas: {$total} data berhasil dibuat/diperbarui untuk tahun ajaran {$tahunAjaran}.");
    }
}

