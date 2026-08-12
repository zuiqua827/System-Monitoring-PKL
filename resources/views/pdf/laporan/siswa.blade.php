<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Data Siswa PKL</title>
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
        <h1>LAPORAN DATA SISWA PKL</h1>
        <p class="printed-at">Tanggal cetak: {{ $printedAt->format('d/m/Y H:i:s') }}</p>
    </header>

    <section class="filter-summary">
        <p class="filter-title">Filter yang digunakan</p>
        @foreach($appliedFilters as $filter)
            <span class="filter-item"><span class="filter-label">{{ $filter['label'] }}:</span> {{ $filter['value'] }}</span>
        @endforeach
    </section>

    <table>
        <colgroup>
            <col style="width: 3%">
            <col style="width: 6%">
            <col style="width: 7%">
            <col style="width: 11%">
            <col style="width: 6.5%">
            <col style="width: 7.5%">
            <col style="width: 14%">
            <col style="width: 11%">
            <col style="width: 10%">
            <col style="width: 7.5%">
            <col style="width: 7.5%">
            <col style="width: 5%">
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Jurusan</th>
                <th>Tempat PKL / DUDI</th>
                <th>Guru Pembimbing</th>
                <th>Periode PKL</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status PKL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penempatanPkls as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $item->siswa?->nis ?? '-' }}</td>
                    <td>{{ $item->siswa?->nisn ?? '-' }}</td>
                    <td>{{ $item->siswa?->nama ?? '-' }}</td>
                    <td>{{ $item->siswa?->kelas?->nama ?? '-' }}</td>
                    <td>{{ $item->siswa?->kelas?->jurusan?->nama ?? '-' }}</td>
                    <td>{{ $item->dudi?->nama_perusahaan ?? '-' }}</td>
                    <td>{{ $item->guru?->nama ?? '-' }}</td>
                    <td>{{ $item->periodePKL?->nama ?? '-' }}</td>
                    <td class="center">{{ $item->tanggal_mulai?->format('d/m/Y') ?? '-' }}</td>
                    <td class="center">{{ $item->tanggal_selesai?->format('d/m/Y') ?? '-' }}</td>
                    <td class="center">{{ ucfirst($item->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Laporan Data Siswa PKL &bull; Dicetak secara elektronik pada {{ $printedAt->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
