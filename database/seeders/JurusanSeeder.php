<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

/**
 * Seed master data Jurusan (Program Keahlian).
 *
 * Menggunakan updateOrCreate() agar tidak duplikat jika dijalankan berulang.
 */
class JurusanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jurusans = [
            [
                'kode' => 'MPLB',
                'nama' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'deskripsi' => 'Kompetensi keahlian di bidang administrasi perkantoran dan layanan bisnis.',
            ],
            [
                'kode' => 'AKL',
                'nama' => 'Akuntansi dan Keuangan Lembaga',
                'deskripsi' => 'Kompetensi keahlian di bidang akuntansi dan keuangan lembaga.',
            ],
            [
                'kode' => 'PPLG',
                'nama' => 'Pengembangan Perangkat Lunak dan Gim',
                'deskripsi' => 'Kompetensi keahlian di bidang pengembangan perangkat lunak dan gim.',
            ],
            [
                'kode' => 'TO',
                'nama' => 'Teknik Otomotif',
                'deskripsi' => 'Kompetensi keahlian di bidang teknik otomotif.',
            ],
            [
                'kode' => 'PM',
                'nama' => 'Pemasaran',
                'deskripsi' => 'Kompetensi keahlian di bidang pemasaran.',
            ],
        ];

        foreach ($jurusans as $jurusan) {
            Jurusan::updateOrCreate(
                ['kode' => $jurusan['kode']],
                [
                    'nama' => $jurusan['nama'],
                    'deskripsi' => $jurusan['deskripsi'],
                ]
            );
        }

        $this->command->info('Seeder Jurusan: ' . count($jurusans) . ' data berhasil dibuat.');
    }
}
