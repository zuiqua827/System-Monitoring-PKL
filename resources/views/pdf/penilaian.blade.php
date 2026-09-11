<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rapor_PKL_{{ str_replace(' ', '_', $penilaian->penempatanPKL?->siswa?->nama ?? 'Siswa') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0.8cm 1.2cm 0.8cm 1.2cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            line-height: 1.2;
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
            max-height: 60px;
            width: auto;
        }
        .header-text {
            text-align: center;
        }
        .header-text .gov {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text .dept {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-text .school {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 1px 0;
        }
        .header-text .address {
            font-size: 7.5pt;
            color: #000000;
            margin-top: 1px;
            line-height: 1.15;
        }

        /* Garis dua kop surat */
        .header-divider {
            border: 0;
            border-top: 2px solid #000000;
            border-bottom: 1px solid #000000;
            height: 2px;
            margin: 3px 0 6px 0;
        }

        /* ── Title Rapor ── */
        .doc-title-container {
            text-align: center;
            margin-bottom: 6px;
        }
        .doc-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }

        /* ── Identitas Siswa ── */
        .identity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .identity-table td {
            padding: 1.5px 2px;
            vertical-align: top;
            font-size: 8.5pt;
        }
        .identity-table .label {
            font-weight: normal;
        }
        .identity-table .sep {
            width: 8px;
            text-align: center;
        }

        /* ── Tabel Penilaian ── */
        .rapor-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .rapor-table th {
            background-color: #ffffff;
            color: #000000;
            font-weight: bold;
            font-size: 8.5pt;
            text-align: center;
            padding: 3px 4px;
            border: 1px solid #000000;
            text-transform: uppercase;
        }
        .rapor-table td {
            padding: 3px 4px;
            border: 1px solid #000000;
            font-size: 8pt;
            vertical-align: middle;
        }
        .rapor-table .col-no { text-align: center; width: 4%; }
        .rapor-table .col-aspek { width: 22%; font-weight: bold; }
        .rapor-table .col-nilai { text-align: center; width: 8%; font-weight: bold; }
        .rapor-table .col-predikat { text-align: center; width: 10%; font-weight: bold; }
        .rapor-table .col-deskripsi { width: 56%; font-size: 7.8pt; line-height: 1.15; }

        /* ── Ringkasan Nilai Akhir Box ── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            border: 1px solid #000000;
        }
        .summary-table td {
            padding: 3.5px 6px;
            border: 1px solid #000000;
            font-size: 8.5pt;
            vertical-align: top;
        }
        .summary-header {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .summary-value {
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
        }
        .capaian-text {
            font-size: 8pt;
            line-height: 1.2;
            text-align: justify;
        }

        /* ── Bottom Info Layout (Catatan DUDI & Rekap Kehadiran) ── */
        .bottom-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .bottom-info-table td {
            vertical-align: top;
            padding: 0;
        }

        .catatan-box {
            border: 1px solid #000000;
            padding: 4px 6px;
            font-size: 8pt;
            box-sizing: border-box;
        }
        .catatan-title {
            font-weight: bold;
            font-size: 8.5pt;
            margin-bottom: 2px;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .ketidakhadiran-box {
            border: 1px solid #000000;
            padding: 4px 6px;
            font-size: 8pt;
            box-sizing: border-box;
        }
        .ketidakhadiran-title {
            font-weight: bold;
            font-size: 8.5pt;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .ketidakhadiran-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ketidakhadiran-table td {
            padding: 1px 0;
            font-size: 8pt;
        }

        /* ── Tanda Tangan Table ── */
        .signature-table {
            width: 100%;
            margin-top: 6px;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
            font-size: 8pt;
        }
        .sign-space {
            height: 38px;
        }
        .sign-name {
            font-weight: bold;
            text-decoration: underline;
        }
        .sign-nip {
            font-size: 7.5pt;
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

        // Fetch Rekap Absensi using penempatan_pkl_id context
        $penilaianService = app(\App\Services\Interfaces\PenilaianServiceInterface::class);
        $rekapAbsensi = $penilaianService->getRekapAbsensiData($penempatan?->id ?? 0);
        $nilaiKehadiran = $penilaian->nilai_kehadiran ?? $penilaianService->calculateKehadiranScore($penempatan?->id ?? 0);

        $aspekPenilaian = [
            [
                'no' => 1,
                'key' => 'kehadiran',
                'nama' => 'Kehadiran',
                'nilai' => $nilaiKehadiran,
            ],
            [
                'no' => 2,
                'key' => 'kerjasama',
                'nama' => 'Kerja Sama',
                'nilai' => $penilaian->nilai_kerjasama,
            ],
            [
                'no' => 3,
                'key' => 'komunikasi',
                'nama' => 'Komunikasi',
                'nilai' => $penilaian->nilai_komunikasi,
            ],
            [
                'no' => 4,
                'key' => 'problem_solving',
                'nama' => 'Problem Solving',
                'nilai' => $penilaian->nilai_problem_solving,
            ],
            [
                'no' => 5,
                'key' => 'teknis',
                'nama' => 'Teknis',
                'nilai' => $penilaian->nilai_teknis,
            ],
            [
                'no' => 6,
                'key' => 'inisiatif',
                'nama' => 'Inisiatif',
                'nilai' => $penilaian->nilai_inisiatif,
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
        <div class="doc-title">HASIL PENILAIAN PRAKTIK KERJA LAPANGAN (PKL)</div>
    </div>

    {{-- ─── IDENTITAS PESERTA DIDIK ─── --}}
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

    {{-- ─── HASIL PENILAIAN (TABEL 6 ASPEK) ─── --}}
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
                    $pred = \App\Services\PenilaianService::calculatePredikatStatic($asp['nilai']) ?? '-';
                    $desk = \App\Services\PenilaianService::getDeskripsiAspek($asp['key'], $asp['nilai']);
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
            <td width="18%" class="summary-header">Predikat Nilai Akhir</td>
            <td width="49%" class="summary-value" style="text-align: left; padding-left: 10px;">
                {{ $penilaian->predikat ?? '-' }}
            </td>
        </tr>
        <tr>
            <td class="summary-header">Deskripsi Nilai Akhir</td>
            <td colspan="3" class="capaian-text">
                {{ \App\Services\PenilaianService::getDeskripsiPredikat($penilaian->predikat) }}
            </td>
        </tr>
    </table>

    {{-- ─── CATATAN DUDI & REKAP KEHADIRAN (LAYOUT BERDAMPINGAN) ─── --}}
    <table class="bottom-info-table">
        <tr>
            <td width="64%" style="padding-right: 6px;">
                <div class="catatan-box">
                    <div class="catatan-title">CATATAN DUDI</div>
                    <div style="font-size: 8pt; line-height: 1.25;">{{ $penilaian->catatan ?: 'Belum ada catatan dari DUDI.' }}</div>
                </div>
            </td>
            <td width="36%">
                <div class="ketidakhadiran-box">
                    <div class="ketidakhadiran-title">REKAP KEHADIRAN</div>
                    <table class="ketidakhadiran-table">
                        <tr>
                            <td width="45%">Hadir</td>
                            <td width="10%" style="text-align: center;">:</td>
                            <td width="45%">{{ $rekapAbsensi['hadir'] }} hari</td>
                        </tr>
                        @if(($rekapAbsensi['terlambat'] ?? 0) > 0)
                        <tr>
                            <td>Terlambat</td>
                            <td style="text-align: center;">:</td>
                            <td>{{ $rekapAbsensi['terlambat'] }} hari</td>
                        </tr>
                        @endif
                        @if(($rekapAbsensi['sangat_terlambat'] ?? 0) > 0)
                        <tr>
                            <td>Sangat Terlambat</td>
                            <td style="text-align: center;">:</td>
                            <td>{{ $rekapAbsensi['sangat_terlambat'] }} hari</td>
                        </tr>
                        @endif
                        <tr>
                            <td>Sakit</td>
                            <td style="text-align: center;">:</td>
                            <td>{{ $rekapAbsensi['sakit'] }} hari</td>
                        </tr>
                        <tr>
                            <td>Izin</td>
                            <td style="text-align: center;">:</td>
                            <td>{{ $rekapAbsensi['izin'] }} hari</td>
                        </tr>
                        <tr>
                            <td>Alpha</td>
                            <td style="text-align: center;">:</td>
                            <td>{{ $rekapAbsensi['alpha'] }} hari</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ─── PENGESAHAN ─── --}}
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
