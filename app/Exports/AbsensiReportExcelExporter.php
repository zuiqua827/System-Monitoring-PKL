<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Spatie\SimpleExcel\SimpleExcelWriter;

final class AbsensiReportExcelExporter
{
    /**
     * Write the report directly to the response stream without materializing
     * the full result set in memory.
     *
     * @param Builder<\App\Models\Absensi>|iterable<\App\Models\Absensi>|AbsensiExportStreamer $query
     * @param array<string, int> $stats
     */
    public function stream(Builder|iterable|AbsensiExportStreamer $query, array $stats): void
    {
        $writer = SimpleExcelWriter::create('php://output', 'xlsx');

        $writer->addRow($this->row([
            'No' => 'REKAP LAPORAN ABSENSI PKL',
        ]));

        foreach ([
            'total_siswa' => 'Total Siswa',
            'total_absensi' => 'Total Absensi',
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha' => 'Alfa',
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
            'Status Absensi' => 'Status Absensi',
            'Jam Masuk' => 'Jam Masuk',
            'Jam Pulang' => 'Jam Pulang',
            'Keterangan' => 'Keterangan',
        ]));

        $number = 1;
        if (method_exists($query, 'chunk')) {
            $query->chunk(500, function ($records) use ($writer, &$number): void {
                foreach ($records as $absensi) {
                    $this->writeRecordRow($writer, $absensi, $number++);
                }
            });
        } else {
            foreach ($query as $absensi) {
                $this->writeRecordRow($writer, $absensi, $number++);
            }
        }

        $writer->close();
    }

    private function writeRecordRow(SimpleExcelWriter $writer, $absensi, int $number): void
    {
        $penempatan = $absensi->penempatanPKL;

        $writer->addRow($this->row([
            'No' => $number,
            'Tanggal' => $absensi->tanggal?->format('d/m/Y') ?? '-',
            'NIS' => $penempatan?->siswa?->nis ?? '-',
            'Nama Siswa' => $penempatan?->siswa?->nama ?? '-',
            'Kelas' => $penempatan?->siswa?->kelas?->nama ?? '-',
            'Jurusan' => $penempatan?->siswa?->kelas?->jurusan?->nama ?? '-',
            'DUDI' => $penempatan?->dudi?->nama_perusahaan ?? '-',
            'Guru Pembimbing' => $penempatan?->guru?->nama ?? '-',
            'Periode PKL' => $penempatan?->periodePKL?->nama ?? '-',
            'Status Absensi' => in_array(strtolower((string) $absensi->status), ['alpha', 'alfa'], true) ? 'Alfa' : ucfirst((string) $absensi->status),
            'Jam Masuk' => $absensi->jam_masuk?->format('H:i') ?? '-',
            'Jam Pulang' => $absensi->jam_keluar?->format('H:i') ?? '-',
            'Keterangan' => $absensi->keterangan ?? '-',
        ]));
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
            'Status Absensi' => '',
            'Jam Masuk' => '',
            'Jam Pulang' => '',
            'Keterangan' => '',
        ], $values);
    }
}
