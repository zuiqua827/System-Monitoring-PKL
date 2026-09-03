<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor_PKL_{{ str_replace(' ', '_', $penilaian->penempatanPKL?->siswa?->nama ?? 'Siswa') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.0cm 1.4cm 1.0cm 1.4cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9.5pt;
            line-height: 1.3;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        /* ── Header Rapor ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-logo {
            max-height: 70px;
            width: auto;
        }
        .header-text {
            text-align: center;
        }
        .header-text .gov {
            font-size: 11.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text .dept {
            font-size: 11.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text .school {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 1px 0;
        }
        .header-text .address {
            font-size: 8pt;
            color: #000000;
            margin-top: 1px;
            line-height: 1.2;
        }

        /* Garis dua kop surat */
        .header-divider {
            border: 0;
            border-top: 2px solid #000000;
            border-bottom: 1px solid #000000;
            height: 2px;
            margin: 3px 0 10px 0;
        }

        /* ── Title Rapor ── */
        .doc-title-container {
            text-align: center;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }

        /* ── Identitas Siswa ── */
        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .identity-table td {
            padding: 2.5px 2px;
            vertical-align: top;
            font-size: 9pt;
        }
        .identity-table .label {
            font-weight: normal;
        }
        .identity-table .sep {
            width: 10px;
            text-align: center;
        }

        /* ── Tabel Penilaian ── */
        .rapor-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .rapor-table th {
            background-color: #ffffff;
            color: #000000;
            font-weight: bold;
            font-size: 9pt;
            text-align: center;
            padding: 4px 4px;
            border: 1px solid #000000;
            text-transform: uppercase;
        }
        .rapor-table td {
            padding: 4px 5px;
            border: 1px solid #000000;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        .rapor-table .col-no { text-align: center; width: 5%; }
        .rapor-table .col-aspek { width: 28%; }
        .rapor-table .col-nilai { text-align: center; width: 10%; font-weight: bold; }
        .rapor-table .col-predikat { text-align: center; width: 10%; font-weight: bold; }
        .rapor-table .col-deskripsi { width: 47%; font-size: 8pt; line-height: 1.2; }

        /* ── Ringkasan Nilai Akhir Box ── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border: 1px solid #000000;
        }
        .summary-table td {
            padding: 5px 7px;
            border: 1px solid #000000;
            font-size: 9pt;
            vertical-align: top;
        }
        .summary-header {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .summary-value {
            font-weight: bold;
            font-size: 10.5pt;
            text-align: center;
        }
        .capaian-text {
            font-size: 8.5pt;
            line-height: 1.3;
            text-align: justify;
        }

        /* ── Rekap Section (Outer Layout) ── */
        .rekap-outer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .rekap-outer-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .section-sub-title {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        /* ── Rekap Absensi Table ── */
        .rekap-absensi-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
        }
        .rekap-absensi-table th {
            background-color: #ffffff;
            color: #000000;
            font-weight: bold;
            font-size: 8.5pt;
            padding: 4px 6px;
            border: 1px solid #000000;
            text-align: left;
        }
        .rekap-absensi-table td {
            padding: 3.5px 6px;
            border: 1px solid #000000;
            font-size: 8.5pt;
        }
        .rekap-absensi-table tfoot th {
            background-color: #f8f8f8;
            border: 1px solid #000000;
            font-size: 8.5pt;
            padding: 4px 6px;
        }

        /* ── Keterangan Penilaian Kehadiran Box ── */
        .keterangan-kehadiran-box {
            border: 1px solid #000000;
            padding: 5px 7px;
            font-size: 8pt;
            line-height: 1.25;
            height: 100%;
            box-sizing: border-box;
        }
        .keterangan-title {
            font-weight: bold;
            font-size: 8.5pt;
            margin-bottom: 4px;
            text-align: center;
            text-transform: uppercase;
        }

        /* ── Catatan Pembimbing Box ── */
        .catatan-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #000000;
        }
        .catatan-table td {
            width: 50%;
            padding: 5px 7px;
            border: 1px solid #000000;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .catatan-title {
            font-weight: bold;
            margin-bottom: 3px;
            text-decoration: underline;
        }

        /* ── Tanda Tangan Table ── */
        .signature-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            font-size: 8.5pt;
        }
        .sign-space {
            height: 50px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .sign-nip {
            font-size: 8pt;
        }
    </style>
</head>
<body>

    @php
        $siswa = $penilaian->penempatanPKL?->siswa;
        $penempatan = $penilaian->penempatanPKL;
        $dudi = $penempatan?->dudi;
        $guru = $penempatan?->guru;
        $periode = $penempatan?->periodePKL;

        // Logo Path with GD check fallback
        $logoPath = file_exists(public_path('images/logo-smk.jpg'))
            ? public_path('images/logo-smk.jpg')
            : (file_exists(public_path('images/logo-smk.png')) ? public_path('images/logo-smk.png') : null);

        // Fetch Rekap Absensi
        $penilaianService = app(\App\Services\Interfaces\PenilaianServiceInterface::class);
        $rekapAbsensi = $penilaianService->getRekapAbsensiData($penempatan?->id ?? 0);

        $getAspectPredikat = function(?int $score) {
            if ($score === null) return '-';
            if ($score >= 95) return 'A+';
            if ($score >= 90) return 'A';
            if ($score >= 80) return 'B';
            if ($score >= 70) return 'C';
            return 'D';
        };

        $getAspectDeskripsi = function(?string $pred) {
            return \App\Services\PenilaianService::getDeskripsiPredikat($pred);
        };

        $aspekPenilaian = [
            [
                'no' => 1,
                'nama' => 'Kehadiran',
                'nilai' => $penilaian->nilai_kehadiran,
            ],
            [
                'no' => 2,
                'nama' => 'Kerja Sama',
                'nilai' => $penilaian->nilai_kerjasama,
            ],
            [
                'no' => 3,
                'nama' => 'Komunikasi',
                'nilai' => $penilaian->nilai_komunikasi,
            ],
            [
                'no' => 4,
                'nama' => 'Problem Solving',
                'nilai' => $penilaian->nilai_problem_solving,
            ],
            [
                'no' => 5,
                'nama' => 'Inisiatif',
                'nilai' => $penilaian->nilai_inisiatif,
            ],
            [
                'no' => 6,
                'nama' => 'Kemampuan Teknis',
                'nilai' => $penilaian->nilai_teknis,
            ],
        ];
    @endphp

    {{-- ─── HEADER SCHOOL ─── --}}
    <table class="header-table">
        <tr>
            <td width="15%" style="text-align: center;">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="Logo SMK" class="header-logo" />
                @endif
            </td>
            <td width="85%">
                <div class="header-text">
                    <div class="gov">PEMERINTAH KABUPATEN JEPARA</div>
                    <div class="dept">DINAS PENDIDIKAN</div>
                    <div class="school">SMK NEGERI 1 BANGSRI</div>
                    <div class="address">
                        Jl. KH. Achmad Fauzan No. 17 Bangsri Jepara 59453 | Telp: (0291) 772321<br>
                        Email: smkn1bangsri@yahoo.co.id | Website: www.smkn1bangsri.sch.id
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="header-divider"></div>

    {{-- ─── DOCUMENT TITLE ─── --}}
    <div class="doc-title-container">
        <div class="doc-title">RAPOR PRAKTIK KERJA LAPANGAN (PKL)</div>
    </div>

    {{-- ─── IDENTITAS PESERTA ─── --}}
    <table class="identity-table">
        <tr>
            <td width="18%" class="label">Nama</td>
            <td width="2%" class="sep">:</td>
            <td width="30%"><strong>{{ $siswa?->nama ?? '-' }}</strong></td>
            <td width="18%" class="label">Tempat PKL (DUDI)</td>
            <td width="2%" class="sep">:</td>
            <td width="30%">{{ $dudi?->nama_perusahaan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIS</td>
            <td class="sep">:</td>
            <td>{{ $siswa?->nis ?? '-' }}</td>
            <td class="label">Guru Pembimbing</td>
            <td class="sep">:</td>
            <td>{{ $guru?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NISN</td>
            <td class="sep">:</td>
            <td>{{ $siswa?->nisn ?? '-' }}</td>
            <td class="label">Pembimbing DUDI</td>
            <td class="sep">:</td>
            <td>{{ $dudi?->penanggung_jawab ?? ($dudi?->pemimpin ?? '-') }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td class="sep">:</td>
            <td>{{ $siswa?->kelas?->nama_kelas ?? ($siswa?->kelas?->nama ?? '-') }}</td>
            <td class="label">Periode PKL</td>
            <td class="sep">:</td>
            <td>{{ $periode?->nama ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Kompetensi Keahlian</td>
            <td class="sep">:</td>
            <td colspan="4">{{ $siswa?->kelas?->jurusan?->nama_jurusan ?? ($siswa?->kelas?->jurusan?->nama ?? '-') }}</td>
        </tr>
    </table>

    {{-- ─── TABEL PENILAIAN (TANPA KOLOM BOBOT) ─── --}}
    <table class="rapor-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-aspek">Aspek Penilaian</th>
                <th class="col-nilai">Nilai</th>
                <th class="col-predikat">Predikat</th>
                <th class="col-deskripsi">Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($aspekPenilaian as $asp)
                @php
                    $pred = $getAspectPredikat($asp['nilai']);
                    $desk = $getAspectDeskripsi($pred);
                @endphp
                <tr>
                    <td class="col-no">{{ $asp['no'] }}</td>
                    <td class="col-aspek">{{ $asp['nama'] }}</td>
                    <td class="col-nilai">{{ $asp['nilai'] ?? '-' }}</td>
                    <td class="col-predikat">{{ $pred }}</td>
                    <td class="col-deskripsi">{{ $desk }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── NILAI AKHIR BOX ─── --}}
    <table class="summary-table">
        <tr>
            <td width="18%" class="summary-header">Nilai Akhir</td>
            <td width="15%" class="summary-value">
                {{ $penilaian->nilai_akhir !== null ? number_format((float)$penilaian->nilai_akhir, 2) : '-' }}
            </td>
            <td width="18%" class="summary-header">Predikat</td>
            <td width="49%" class="summary-value" style="text-align: left; padding-left: 10px;">
                {{ $penilaian->predikat ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="summary-header">Deskripsi Kompetensi</td>
            <td colspan="3" class="capaian-text">
                {{ \App\Services\PenilaianService::getDeskripsiPredikat($penilaian->predikat) }}
            </td>
        </tr>
    </table>

    {{-- ─── REKAP ABSENSI & KETERANGAN PENILAIAN KEHADIRAN ─── --}}
    <table class="rekap-outer-table">
        <tr>
            <td width="57%" style="padding-right: 6px;">
                <div class="section-sub-title">REKAP ABSENSI</div>
                <table class="rekap-absensi-table">
                    <thead>
                        <tr>
                            <th>Keterangan</th>
                            <th style="text-align: center; width: 30%;">Jumlah Hari</th>
                            <th style="text-align: center; width: 30%;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Hadir</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['hadir'] }} hari</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['hadir_pct'] }}%</td>
                        </tr>
                        <tr>
                            <td>Sakit</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['sakit'] }} hari</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['sakit_pct'] }}%</td>
                        </tr>
                        <tr>
                            <td>Izin</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['izin'] }} hari</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['izin_pct'] }}%</td>
                        </tr>
                        <tr>
                            <td>Alpha</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['alpha'] }} hari</td>
                            <td style="text-align: center;">{{ $rekapAbsensi['alpha_pct'] }}%</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total Hari Kerja</th>
                            <th style="text-align: center;">{{ $rekapAbsensi['total_hari'] }} hari</th>
                            <th style="text-align: center;">100%</th>
                        </tr>
                    </tfoot>
                </table>
            </td>
            <td width="43%" style="padding-left: 6px;">
                <div class="keterangan-kehadiran-box">
                    <div class="keterangan-title">KETERANGAN PENILAIAN KEHADIRAN</div>
                    <p style="margin: 0 0 4px 0;">Nilai Kehadiran dihitung otomatis berdasarkan rekap absensi.</p>
                    <div style="margin-bottom: 4px; padding-left: 4px;">
                        &bull; Hadir = 100%<br>
                        &bull; Sakit = 85%<br>
                        &bull; Izin = 70%<br>
                        &bull; Alpha = 0%
                    </div>
                    <p style="margin: 0;">Nilai akhir kehadiran diperoleh dari perhitungan otomatis sistem.</p>
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── CATATAN PEMBIMBING ─── --}}
    <table class="catatan-table">
        <tr>
            <td>
                <div class="catatan-title">Catatan Pembimbing DUDI</div>
                <div>{{ $penilaian->catatan ?: '-' }}</div>
            </td>
            <td>
                <div class="catatan-title">Catatan Guru Pembimbing</div>
                <div>{{ $penilaian->catatan_guru ?: '-' }}</div>
            </td>
        </tr>
    </table>

    {{-- ─── TANDA TANGAN ─── --}}
    <table class="signature-table">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div><strong>Pembimbing DUDI</strong></div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $dudi?->penanggung_jawab ?? ($dudi?->nama_perusahaan ?? '.........................................') }}</div>
            </td>
            <td>
                <div><br></div>
                <div><strong>Guru Pembimbing</strong></div>
                <div class="sign-space"></div>
                <div class="sign-name">{{ $guru?->nama ?? '.........................................' }}</div>
                <div class="sign-nip">NIP. {{ $guru?->nip ?? '-' }}</div>
            </td>
            <td>
                <div>Bangsri, {{ $penilaian->tanggal_penilaian ? $penilaian->tanggal_penilaian->locale('id')->translatedFormat('d F Y') : now()->locale('id')->translatedFormat('d F Y') }}</div>
                <div><strong>Kepala Program Keahlian</strong></div>
                <div class="sign-space"></div>
                <div class="sign-name">.........................................</div>
                <div class="sign-nip">NIP. .........................................</div>
            </td>
        </tr>
    </table>

</body>
</html>
