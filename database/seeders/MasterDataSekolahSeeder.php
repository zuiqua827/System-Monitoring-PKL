<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ensure the PKL Monitoring System only contains the real Grade XII master
 * data for SMK Negeri 1 Bangsri.
 *
 * Creates (or reuses) ONLY these departments:
 *   MPLB, AKL, PPLG, TO, PM
 *
 * Creates (or reuses) ONLY these classes:
 *   XII MPLB 1, XII MPLB 2, XII MPLB 3
 *   XII AKL 1, XII AKL 2
 *   XII PPLG 1, XII PPLG 2
 *   XII TO 1, XII TO 2
 *   XII PM 1, XII PM 2
 *
 * Any other (dummy) departments and classes are soft-deleted.
 *
 * This seeder is idempotent and safe to run multiple times.
 */
class MasterDataSekolahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjaran = '2025/2026';
        $tingkat = 12;

        $jurusans = [
            ['kode' => 'MPLB', 'nama' => 'Manajemen Perkantoran dan Layanan Bisnis', 'deskripsi' => 'Kompetensi keahlian di bidang administrasi perkantoran dan layanan bisnis.'],
            ['kode' => 'AKL', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'deskripsi' => 'Kompetensi keahlian di bidang akuntansi dan keuangan lembaga.'],
            ['kode' => 'PPLG', 'nama' => 'Pengembangan Perangkat Lunak dan Gim', 'deskripsi' => 'Kompetensi keahlian di bidang pengembangan perangkat lunak dan gim.'],
            ['kode' => 'TO', 'nama' => 'Teknik Otomotif', 'deskripsi' => 'Kompetensi keahlian di bidang teknik otomotif.'],
            ['kode' => 'PM', 'nama' => 'Pemasaran', 'deskripsi' => 'Kompetensi keahlian di bidang pemasaran.'],
        ];

        $kelasByJurusan = [
            'MPLB' => ['XII MPLB 1', 'XII MPLB 2', 'XII MPLB 3'],
            'AKL' => ['XII AKL 1', 'XII AKL 2'],
            'PPLG' => ['XII PPLG 1', 'XII PPLG 2'],
            'TO' => ['XII TO 1', 'XII TO 2'],
            'PM' => ['XII PM 1', 'XII PM 2'],
        ];

        DB::transaction(function () use ($tahunAjaran, $tingkat, $jurusans, $kelasByJurusan): void {
            // 1. Ensure the 5 real departments exist (reuse if present).
            foreach ($jurusans as $jurusan) {
                $existing = Jurusan::withTrashed()->where('kode', $jurusan['kode'])->first();

                if ($existing !== null) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->forceFill([
                        'nama' => $jurusan['nama'],
                        'deskripsi' => $jurusan['deskripsi'],
                    ])->save();
                } else {
                    Jurusan::create($jurusan);
                }
            }

            // 2. Ensure the 11 real classes exist (reuse if present).
            foreach ($kelasByJurusan as $kodeJurusan => $rombelList) {
                $jurusan = Jurusan::where('kode', $kodeJurusan)->first();

                if ($jurusan === null) {
                    continue;
                }

                foreach ($rombelList as $namaKelas) {
                    $existing = Kelas::withTrashed()
                        ->where('jurusan_id', $jurusan->id)
                        ->where('nama', $namaKelas)
                        ->where('tahun_ajaran', $tahunAjaran)
                        ->first();

                    if ($existing !== null) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->forceFill(['tingkat' => $tingkat])->save();
                    } else {
                        Kelas::create([
                            'jurusan_id' => $jurusan->id,
                            'nama' => $namaKelas,
                            'tingkat' => $tingkat,
                            'tahun_ajaran' => $tahunAjaran,
                        ]);
                    }
                }
            }

            // 3. Soft-delete any dummy departments (not in the real 5).
            $realKodes = array_column($jurusans, 'kode');
            Jurusan::withoutTrashed()
                ->whereNotIn('kode', $realKodes)
                ->get()
                ->each(function (Jurusan $jurusan): void {
                    $jurusan->delete();
                });

            // 4. Soft-delete any dummy classes (not in the real 11).
            $realNama = array_merge(...array_values($kelasByJurusan));
            Kelas::withoutTrashed()
                ->whereNotIn('nama', $realNama)
                ->get()
                ->each(function (Kelas $kelas): void {
                    $kelas->delete();
                });
        });

        $this->command->info('MasterDataSekolahSeeder: 5 jurusan & kelas XII siap.');
    }
}
