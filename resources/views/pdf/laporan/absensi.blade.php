<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi PKL</title>
    <style>
        @page {
            margin: 14mm 10mm 16mm 10mm;
        }

        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.4pt;
            line-height: 1.25;
        }

        .report-header {
            border-bottom: 1.5px solid #1d4ed8;
            margin-bottom: 9px;
            padding-bottom: 7px;
        }

        .system-name {
            color: #1d4ed8;
            font-size: 9pt;
            font-weight: bold;
            margin: 0 0 3px;
        }

        h1 {
            font-size: 14pt;
            letter-spacing: .3px;
            margin: 0;
        }

        .printed-at {
            color: #475569;
            font-size: 7.2pt;
            margin: 3px 0 0;
        }

        .filter-summary {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            margin-bottom: 9px;
            padding: 6px 8px;
        }

        .filter-title {
            font-weight: bold;
            margin: 0 0 3px;
        }

        .filter-item {
            display: inline;
            margin-right: 14px;
        }

        .filter-label {
            color: #475569;
            font-weight: bold;
        }
        
        .stats-summary {
            background: #e0f2fe;
            border: 1px solid #7dd3fc;
            margin-bottom: 9px;
            padding: 6px 8px;
        }

        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #94a3b8;
            overflow-wrap: break-word;
            padding: 4px 3px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 6.5pt;
            text-align: center;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .center {
            text-align: center;
        }

        .footer {
            bottom: -9mm;
            color: #64748b;
            font-size: 6.8pt;
            left: 0;
            position: fixed;
            right: 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="report-header">
        <p class="system-name">{{ $systemName }}</p>
        <h1>LAPORAN ABSENSI PKL</h1>
        <p class="printed-at">Tanggal cetak: {{ $printedAt->format('d/m/Y H:i:s') }}</p>
    </header>

    <section class="filter-summary">
        <p class="filter-title">Filter yang digunakan</p>
        @foreach($appliedFilters as $filter)
            <span class="filter-item"><span class="filter-label">{{ $filter['label'] }}:</span> {{ $filter['value'] }}</span>
        @endforeach
    </section>
    
    <section class="stats-summary">
        <p class="filter-title">Rekap Absensi</p>
        <span class="filter-item"><span class="filter-label">Total Siswa:</span> {{ $stats['total_siswa'] ?? 0 }}</span>
        <span class="filter-item"><span class="filter-label">Total Absensi:</span> {{ $stats['total_absensi'] ?? 0 }}</span>
        <span class="filter-item"><span class="filter-label">Hadir:</span> {{ $stats['hadir'] ?? 0 }}</span>
        <span class="filter-item"><span class="filter-label">Terlambat:</span> {{ $stats['terlambat'] ?? 0 }}</span>
        <span class="filter-item"><span class="filter-label">Izin:</span> {{ $stats['izin'] ?? 0 }}</span>
        <span class="filter-item"><span class="filter-label">Sakit:</span> {{ $stats['sakit'] ?? 0 }}</span>
        <span class="filter-item"><span class="filter-label">Alpha:</span> {{ $stats['alpha'] ?? 0 }}</span>
    </section>

    <table>
        <colgroup>
            <col style="width: 3%">
            <col style="width: 7%">
            <col style="width: 8%">
            <col style="width: 14%">
            <col style="width: 8%">
            <col style="width: 12%">
            <col style="width: 12%">
            <col style="width: 7%">
            <col style="width: 7%">
            <col style="width: 7%">
            <col style="width: 15%">
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>DUDI</th>
                <th>Guru Pembimbing</th>
                <th>Status</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($absensis as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $item->penempatanPKL?->siswa?->nis ?? '-' }}</td>
                    <td>{{ $item->penempatanPKL?->siswa?->nama ?? '-' }}</td>
                    <td>{{ $item->penempatanPKL?->siswa?->kelas?->nama ?? '-' }}</td>
                    <td>{{ $item->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</td>
                    <td>{{ $item->penempatanPKL?->guru?->nama ?? '-' }}</td>
                    <td class="center">{{ ucfirst($item->status) }}</td>
                    <td class="center">{{ $item->jam_masuk ? \Carbon\Carbon::parse($item->jam_masuk)->format('H:i') : '-' }}</td>
                    <td class="center">{{ $item->jam_pulang ? \Carbon\Carbon::parse($item->jam_pulang)->format('H:i') : '-' }}</td>
                    <td>{{ $item->keterangan ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Laporan Absensi PKL &bull; Dicetak secara elektronik pada {{ $printedAt->format('d/m/Y H:i:s') }} &bull; Halaman <span class="pagenum"></span>
    </div>
</body>
</html>
