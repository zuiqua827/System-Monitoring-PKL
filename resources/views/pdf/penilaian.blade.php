<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Penilaian PKL - {{ $penilaian->penempatanPKL?->siswa?->nama ?? 'Siswa' }}</title>
    <style>
        @page {
            margin: 2.5cm 1.5cm 2.5cm 1.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #0f4c81;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .header-logo {
            width: 72px;
            height: 72px;
            vertical-align: middle;
            margin-right: 12px;
            float: left;
        }
        .header-text {
            text-align: center;
            font-size: 10pt;
        }
        .header-text .title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            color: #0f4c81;
        }
        .header-text .sub {
            font-size: 10pt;
            margin-top: 2px;
        }
        .header-text .address {
            font-size: 9pt;
            color: #555;
            margin-top: 1px;
        }

        /* ── Document title ── */
        .doc-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 18px 0 16px 0;
            letter-spacing: 1px;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 10pt;
            margin-bottom: 22px;
        }

        /* ── Info sections ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 11pt;
        }
        .info-table .label {
            width: 160px;
            font-weight: 600;
        }
        .info-table .separator {
            width: 16px;
            text-align: center;
        }

        /* ── Assessment components ── */
        .assessment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
        }
        .assessment-table thead th {
            background: #0f4c81;
            color: #ffffff;
            font-weight: 700;
            font-size: 10pt;
            text-align: center;
            padding: 8px 6px;
            border: 1px solid #0f4c81;
        }
        .assessment-table tbody td {
            padding: 7px 8px;
            border: 1px solid #cccccc;
            font-size: 10.5pt;
        }
        .assessment-table tbody td:first-child {
            font-weight: 600;
        }
        .assessment-table tbody td:last-child {
            text-align: center;
            font-weight: 600;
        }
        .assessment-table tbody tr:nth-child(even) {
            background: #f4f7fc;
        }

        /* ── Final score block ── */
        .final-score {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 22px 0;
        }
        .final-score td {
            border: 1px solid #cccccc;
            padding: 8px 10px;
            font-size: 11pt;
        }
        .final-score .label {
            font-weight: 600;
            width: 160px;
            background: #f0f4fa;
        }
        .final-score .value {
            font-weight: 700;
            text-align: center;
        }
        .final-score .value.big {
            font-size: 13pt;
        }

        /* ── Notes / Comments ── */
        .comments {
            margin: 14px 0 18px 0;
        }
        .comments .label {
            font-weight: 600;
            font-size: 11pt;
            margin-bottom: 4px;
        }
        .comments .content {
            border: 1px solid #cccccc;
            padding: 10px 12px;
            min-height: 40px;
            font-size: 10.5pt;
            background: #fafafa;
        }

        /* ── Signature area ── */
        .signature-area {
            width: 100%;
            margin-top: 30px;
            margin-bottom: 16px;
        }
        .signature-area td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 0 20px;
            font-size: 10pt;
        }
        .signature-area .sign-line {
            margin-top: 54px;
            margin-bottom: 4px;
        }
        .signature-area .name {
            font-weight: 700;
            font-size: 10.5pt;
        }
        .signature-area .role {
            font-size: 9pt;
            color: #666;
        }

        /* ── Footer ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5pt;
            color: #999;
            border-top: 1px solid #cccccc;
            padding-top: 6px;
        }

        /* ── Utilities ── */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: 700; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        hr { border: none; border-top: 1px solid #e0e0e0; margin: 10px 0; }
    </style>
</head>
<body>

{{-- ─── SCHOOL HEADER ─── --}}
    <table class="header-table">
        <tr>
            <td style="width:84px; vertical-align:middle; text-align:center;">
<img src="{{ public_path('images/simongan-logo.svg') }}" alt="SIMONGAN" style="width:64px;height:64px;object-fit:contain;">
            </td>
            <td style="vertical-align:middle;">
                <div class="header-text">
                    <div class="title">PEMERINTAH PROVINSI JAWA TENGAH</div>
                    <div class="title" style="font-size:16pt;">SMK NEGERI 1 BANGSRI</div>
                    <div class="sub">Bidang Keahlian: Teknologi Informasi &amp; Komunikasi</div>
                    <div class="address">
                        Jl. Raya Bangsri, Kab. Jepara, Jawa Tengah<br>
                        Telp. (0291) 123456 &bull; Email: info@smkn1bangsri.sch.id
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── DOCUMENT TITLE ─── --}}
    <div class="doc-title">LAPORAN HASIL PENILAIAN PRAKTIK KERJA LAPANGAN (PKL)</div>
    <div class="doc-subtitle">Nomor: {{ $penilaian->penempatanPKL?->nomor_surat ?? 'PKL/' . $penilaian->id . '/SMKN1BDG/' . date('Y') }}</div>

    {{-- ─── STUDENT DATA ─── --}}
    <table class="info-table">
        <tr><td colspan="3" style="font-weight:700; font-size:11pt; padding-bottom:6px;">A. DATA SISWA</td></tr>
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->siswa?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nomor Induk Siswa (NIS)</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->siswa?->nis ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NISN</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->siswa?->nisn ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kelas / Jurusan</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->siswa?->kelas?->nama ?? '-' }} / {{ $penilaian->penempatanPKL?->siswa?->kelas?->jurusan?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->periodePKL?->tahun_ajaran ?? '-' }}</td>
        </tr>
    </table>

    <hr>

    {{-- ─── PKL PLACEMENT ─── --}}
    <table class="info-table">
        <tr><td colspan="3" style="font-weight:700; font-size:11pt; padding-bottom:6px;">B. DATA PENEMPATAN</td></tr>
        <tr>
            <td class="label">Nama Perusahaan (DUDI)</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Alamat DUDI</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->dudi?->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Guru Pembimbing</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Periode PKL</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->periodePKL?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Pelaksanaan</td>
            <td class="separator">:</td>
            <td>{{ $penilaian->penempatanPKL?->tanggal_mulai ? $penilaian->penempatanPKL->tanggal_mulai->format('d/m/Y') : '-' }}
                s.d.
                {{ $penilaian->penempatanPKL?->tanggal_selesai ? $penilaian->penempatanPKL->tanggal_selesai->format('d/m/Y') : '-' }}</td>
        </tr>
    </table>

    <hr>

    {{-- ─── ASSESSMENT COMPONENTS ─── --}}
    <table class="info-table">
        <tr><td colspan="3" style="font-weight:700; font-size:11pt; padding-bottom:6px;">C. HASIL PENILAIAN</td></tr>
    </table>

    <table class="assessment-table">
        <thead>
            <tr>
                <th style="width:60%; text-align:left; padding-left:12px;">Komponen Penilaian</th>
                <th style="width:40%;">Nilai (0 - 100)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1. Kehadiran</td>
                <td>{{ $penilaian->nilai_kehadiran ?? '-' }}</td>
            </tr>
            <tr>
                <td>2. Kerja Sama</td>
                <td>{{ $penilaian->nilai_kerjasama ?? '-' }}</td>
            </tr>
            <tr>
                <td>3. Komunikasi</td>
                <td>{{ $penilaian->nilai_komunikasi ?? '-' }}</td>
            </tr>
            <tr>
                <td>4. Problem Solving</td>
                <td>{{ $penilaian->nilai_problem_solving ?? '-' }}</td>
            </tr>
            <tr>
                <td>5. Keterampilan Teknis</td>
                <td>{{ $penilaian->nilai_teknis ?? '-' }}</td>
            </tr>
            <tr>
                <td>6. Inisiatif</td>
                <td>{{ $penilaian->nilai_inisiatif ?? '-' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ─── FINAL SCORE ─── --}}
    <table class="final-score">
        <tr>
            <td class="label">Rata-rata (Nilai Akhir)</td>
            <td class="value big">{{ $penilaian->nilai_akhir !== null ? number_format($penilaian->nilai_akhir, 2) : '-' }}</td>
            <td class="label" style="width:110px;">Predikat</td>
            <td class="value" style="font-size:13pt;">{{ $penilaian->predikat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Status Penilaian</td>
            <td class="value">{{ $penilaian->status === 'final' ? 'FINAL' : 'DRAFT' }}</td>
            <td class="label">Tanggal Penilaian</td>
            <td class="value">{{ $penilaian->tanggal_penilaian ? $penilaian->tanggal_penilaian->format('d/m/Y') : now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    @php
        $predikatLabel = match($penilaian->predikat) {
            'A' => 'Sangat Baik',
            'B' => 'Baik',
            'C' => 'Cukup',
            'D' => 'Kurang',
            'E' => 'Sangat Kurang',
            default => '-',
        };
    @endphp
    <p style="font-size:10pt; text-align:center; margin-top:-10px;">
        Predikat: <strong>{{ $penilaian->predikat ?? '-' }}</strong> ({{ $predikatLabel }})
    </p>

    <hr>

    {{-- ─── COMMENTS ─── --}}
    @if($penilaian->catatan || $penilaian->catatan_guru)
    <div class="comments">
        <div class="label">D. CATATAN PEMBIMBING</div>
        <div class="content">
            @if($penilaian->catatan)
                <strong>Catatan DUDI:</strong><br>
                {{ $penilaian->catatan }}
            @endif
            @if($penilaian->catatan && $penilaian->catatan_guru)
                <br><br>
            @endif
            @if($penilaian->catatan_guru)
                <strong>Catatan Guru Pembimbing:</strong><br>
                {{ $penilaian->catatan_guru }}
            @endif
        </div>
    </div>
    @endif

    {{-- ─── SIGNATURE ─── --}}
    <table class="signature-area">
        <tr>
            <td>
                <div>DUDI / Pembimbing Lapangan,</div>
                <div class="sign-line" style="margin-top:60px;">_______________</div>
                <div class="name">{{ $penilaian->penempatanPKL?->dudi?->penanggung_jawab ?? $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</div>
                <div class="role">Pembimbing Lapangan</div>
            </td>
            <td>
                <div>Bandung, {{ now()->locale('id')->translatedFormat('d F Y') }}</div>
                <div class="sign-line">_______________</div>
                <div class="name">{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</div>
                <div class="role">Guru Pembimbing PKL</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:22px;">
                <div>Mengetahui,</div>
                <div style="margin-top:8px;">Kepala SMK Negeri 1 Bandung</div>
                <div class="sign-line" style="margin-top:48px;">_______________</div>
                <div class="name">Drs. H. Agus Supriatna, M.Pd.</div>
                <div class="role">NIP. 19680512 199403 1 008</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen ini dicetak secara elektronik pada {{ now()->locale('id')->translatedFormat('l, d F Y H:i:s') }} &bull;
Sistem Monitoring Lapangan &bull; SMK Negeri 1 Bangsri
    </div>

</body>
</html>

