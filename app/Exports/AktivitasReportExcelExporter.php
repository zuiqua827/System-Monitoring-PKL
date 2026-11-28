<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Spatie\SimpleExcel\SimpleExcelWriter;

final class AktivitasReportExcelExporter
{
    /**
     * Write the report directly to the response stream without materializing
     * the full result set in memory.
     *
     * @param Builder<\App\Models\Aktivitas> $query
     * @param array<string, int> $stats
     */
    public function stream(Builder $query, array $stats): void
    {
        $writer = SimpleExcelWriter::create('php://output', 'xlsx');

        $writer->addRow($this->row([
            'No' => 'REKAP LAPORAN AKTIVITAS PKL',
        ]));

        foreach ([
            'total_siswa' => 'Total Siswa',
            'total_aktivitas' => 'Total Aktivitas',
            'pending' => 'Menunggu Validasi',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ] as $key => $label) {
            $writer->addRow($this->row([
                'No' => $label,
                'Tanggal' => $stats[$key] ?? 0,
            ]));
        }

        $writer->addRow($this->row([]));
        $writer->addRow($this->row([
            'No' => 'No',
            'Tanggal' => 'Tanggal',
            'NIS' => 'NIS',
            'Nama Siswa' => 'Nama Siswa',
            'Kelas' => 'Kelas',
            'Jurusan' => 'Jurusan',
            'DUDI' => 'DUDI',
            'Guru Pembimbing' => 'Guru Pembimbing',
            'Periode PKL' => 'Periode PKL',
            'Jam Mulai' => 'Jam Mulai',
            'Jam Selesai' => 'Jam Selesai',
            'Judul Aktivitas' => 'Judul Aktivitas',
            'Deskripsi' => 'Deskripsi',
            'Status Aktivitas' => 'Status Aktivitas',
        ]));

        $number = 1;
        $query->chunk(500, function ($records) use ($writer, &$number): void {
            foreach ($records as $aktivitas) {
                $penempatan = $aktivitas->penempatanPKL;

                $writer->addRow($this->row([
                    'No' => $number++,
                    'Tanggal' => $aktivitas->tanggal?->format('d/m/Y') ?? '-',
                    'NIS' => $penempatan?->siswa?->nis ?? '-',
                    'Nama Siswa' => $penempatan?->siswa?->nama ?? '-',
                    'Kelas' => $penempatan?->siswa?->kelas?->nama ?? '-',
                    'Jurusan' => $penempatan?->siswa?->kelas?->jurusan?->nama ?? '-',
                    'DUDI' => $penempatan?->dudi?->nama_perusahaan ?? '-',
                    'Guru Pembimbing' => $penempatan?->guru?->nama ?? '-',
                    'Periode PKL' => $penempatan?->periodePKL?->nama ?? '-',
                    'Jam Mulai' => $aktivitas->waktu_mulai?->format('H:i') ?? '-',
                    'Jam Selesai' => $aktivitas->waktu_selesai?->format('H:i') ?? '-',
                    'Judul Aktivitas' => $aktivitas->judul_aktivitas ?? '-',
                    'Deskripsi' => $aktivitas->deskripsi ?? '-',
                    'Status Aktivitas' => ucfirst($aktivitas->status),
                ]));
            }
        });

        $writer->close();
    }

    /** @return array<string, int|string> */
    private function row(array $values): array
    {
        return array_replace([
            'No' => '',
            'Tanggal' => '',
            'NIS' => '',
            'Nama Siswa' => '',
            'Kelas' => '',
            'Jurusan' => '',
            'DUDI' => '',
            'Guru Pembimbing' => '',
            'Periode PKL' => '',
            'Jam Mulai' => '',
            'Jam Selesai' => '',
            'Judul Aktivitas' => '',
            'Deskripsi' => '',
            'Status Aktivitas' => '',
        ], $values);
    }
}
