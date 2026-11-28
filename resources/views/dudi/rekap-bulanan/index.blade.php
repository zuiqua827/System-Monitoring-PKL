@extends('layouts.app')

@section('title', 'Rekap Bulanan Siswa PKL')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="{
    openModal: false,
    activeDay: null,
    previewImage: null,
    openDetail(day) {
        this.activeDay = day;
        this.openModal = true;
    }
}">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">DUDI</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Rekap Bulanan Siswa PKL</h1>
                <p class="mt-2 text-sm text-slate-500">Monitoring rekapitulasi kehadiran dan catatan aktivitas bulanan siswa yang ditempatkan di perusahaan Anda</p>
            </div>
            @if($selectedSiswa)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-sm">
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Periode: {{ $rekap['period_label'] ?? '' }}
                    </span>
                </div>
            @endif
        </div>

        {{-- Filter Section --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm sm:p-5">
            <form method="GET" action="{{ route('dudi.rekap-bulanan.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {{-- Filter Siswa --}}
                <div>
                    <label for="siswa_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Pilih Siswa</label>
                    <select id="siswa_id" name="siswa_id" class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @forelse($siswaList as $s)
                            <option value="{{ $s->id }}" {{ $selectedSiswa && $selectedSiswa->id === $s->id ? 'selected' : '' }}>
                                {{ $s->nama }} ({{ $s->nis }}) {{ $s->kelas ? ' - '.$s->kelas->nama : '' }}
                            </option>
                        @empty
                            <option value="">Tidak ada siswa di perusahaan</option>
                        @endforelse
                    </select>
                </div>

                {{-- Filter Bulan --}}
                <div>
                    <label for="bulan" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Bulan</label>
                    <select id="bulan" name="bulan" class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @php
                            $namaBulan = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                        @endphp
                        @foreach($namaBulan as $mNum => $mName)
                            <option value="{{ $mNum }}" {{ (int)$bulan === $mNum ? 'selected' : '' }}>
                                {{ $mName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Tahun --}}
                <div>
                    <label for="tahun" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tahun</label>
                    <select id="tahun" name="tahun" class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @php
                            $currentYear = (int) date('Y');
                            $years = range($currentYear - 2, $currentYear + 2);
                        @endphp
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Tampilkan Rekap
                    </button>
                    @if(request()->anyFilled(['siswa_id', 'bulan', 'tahun']))
                        <a href="{{ route('dudi.rekap-bulanan.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            @if($selectedSiswa)
                {{-- Siswa Information Banner --}}
                <div class="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-bold text-sm">
                            {{ strtoupper(substr($selectedSiswa->nama, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-sm">{{ $selectedSiswa->nama }}</p>
                            <p class="text-slate-500">NIS: {{ $selectedSiswa->nis }} &bull; Kelas: {{ $selectedSiswa->kelas?->nama ?? '-' }} ({{ $selectedSiswa->kelas?->jurusan?->nama ?? '-' }})</p>
                        </div>
                    </div>
                    @php
                        $penempatanAktif = $selectedSiswa->penempatan->first();
                    @endphp
                    @if($penempatanAktif)
                        <div class="text-right">
                            <p class="font-medium text-slate-800">Guru Pembimbing: {{ $penempatanAktif->guru?->nama ?? '-' }}</p>
                            <p class="text-slate-500">Periode: {{ $penempatanAktif->periodePKL?->nama ?? '-' }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if(!$selectedSiswa)
            {{-- No Student Available --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-card-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Belum ada siswa yang ditempatkan</h3>
                <p class="mt-1 text-sm text-slate-500">Tidak ada siswa PKL yang terdaftar aktif di perusahaan Anda saat ini.</p>
            </div>
        @else
            {{-- Summary Cards (Cards Hadir, Terlambat, Izin, Sakit, Alfa, Total Hari, Total Aktivitas) --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-7">
                {{-- Card Hadir --}}
                <div class="rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Hadir</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-emerald-950 sm:text-3xl">
                        {{ $rekap['summary_absensi']['hadir'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Hari Masuk / Hadir</p>
                </div>

                {{-- Card Terlambat --}}
                <div class="rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Terlambat</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-amber-950 sm:text-3xl">
                        {{ $rekap['summary_absensi']['terlambat'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Keterlambatan</p>
                </div>

                {{-- Card Izin --}}
                <div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-700">Izin</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-blue-950 sm:text-3xl">
                        {{ $rekap['summary_absensi']['izin'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Hari Izin</p>
                </div>

                {{-- Card Sakit --}}
                <div class="rounded-2xl border border-orange-100 bg-gradient-to-br from-orange-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-orange-700">Sakit</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-100 text-orange-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-orange-950 sm:text-3xl">
                        {{ $rekap['summary_absensi']['sakit'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Hari Sakit</p>
                </div>

                {{-- Card Alfa --}}
                <div class="rounded-2xl border border-rose-100 bg-gradient-to-br from-rose-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-700">Alfa</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-rose-950 sm:text-3xl">
                        {{ $rekap['summary_absensi']['alfa'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Tanpa Keterangan</p>
                </div>

                {{-- Card Total Hari Absensi --}}
                <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Total Hari</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                        {{ $rekap['summary_absensi']['total_hari'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Total Tercatat</p>
                </div>

                {{-- Card Total Aktivitas --}}
                <div class="col-span-2 sm:col-span-3 lg:col-span-1 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/50 to-white p-4 shadow-card-sm transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-700">Aktivitas</span>
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-bold tracking-tight text-indigo-950 sm:text-3xl">
                        {{ $rekap['summary_aktivitas']['total'] }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Total Aktivitas</p>
                </div>
            </div>

            {{-- Empty State Notifications if absensi or aktivitas is empty --}}
            @if(!$rekap['has_absensi'])
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Belum ada data absensi pada periode ini.</span>
                </div>
            @endif

            @if(!$rekap['has_aktivitas'])
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Belum ada aktivitas pada periode ini.</span>
                </div>
            @endif

            {{-- Daily Recap Table (Rekap Harian) --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Rekap Harian Kegiatan & Kehadiran</h2>
                        <p class="text-xs text-slate-500">Rincian absensi dan aktivitas siswa per tanggal untuk periode {{ $rekap['period_label'] }}</p>
                    </div>
                    <div class="text-xs font-medium text-slate-500">
                        Total {{ count($rekap['rekap_harian']) }} hari dengan catatan data
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] divide-y divide-slate-200 text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="w-14 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">No</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-500">Absensi</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-500">Jam Masuk</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-500">Jam Pulang</th>
                                <th class="px-4 py-3.5 text-xs font-bold uppercase tracking-wider text-slate-500">Status Keterlambatan</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Jumlah Aktivitas</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Aksi Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rekap['rekap_harian'] as $index => $day)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="px-4 py-3.5 text-center text-xs text-slate-400 font-medium">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3.5">
                                        <p class="text-sm font-semibold text-slate-900">{{ $day['tanggal_day'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $day['hari'] }}</p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if($day['status_absensi_raw'] === 'hadir')
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                                Hadir
                                            </span>
                                        @elseif($day['status_absensi_raw'] === 'terlambat')
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">
                                                Terlambat
                                            </span>
                                        @elseif($day['status_absensi_raw'] === 'izin')
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">
                                                Izin
                                            </span>
                                        @elseif($day['status_absensi_raw'] === 'sakit')
                                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-1 text-xs font-bold text-orange-800">
                                                Sakit
                                            </span>
                                        @elseif($day['status_absensi_raw'] === 'alpha' || $day['status_absensi_raw'] === 'alfa')
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800">
                                                Alfa
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-sm font-mono text-slate-700">
                                        {{ $day['jam_masuk'] }}
                                    </td>
                                    <td class="px-4 py-3.5 text-sm font-mono text-slate-700">
                                        {{ $day['jam_pulang'] }}
                                    </td>
                                    <td class="px-4 py-3.5 text-xs">
                                        @if($day['status_keterlambatan'] === 'Tepat Waktu')
                                            <span class="inline-flex items-center text-emerald-700 font-medium">
                                                <svg class="mr-1 h-3.5 w-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                Tepat Waktu
                                            </span>
                                        @elseif($day['status_keterlambatan'] === 'Sangat Terlambat')
                                            <span class="inline-flex items-center text-rose-700 font-semibold">
                                                <svg class="mr-1 h-3.5 w-3.5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Sangat Terlambat
                                            </span>
                                        @elseif($day['status_keterlambatan'] === 'Terlambat')
                                            <span class="inline-flex items-center text-amber-700 font-medium">
                                                <svg class="mr-1 h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Terlambat
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        @if($day['jumlah_aktivitas'] > 0)
                                            <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                                {{ $day['jumlah_aktivitas'] }} Aktivitas
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-400">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <button
                                            type="button"
                                            @click="openDetail({{ json_encode($day) }})"
                                            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        >
                                            <svg class="mr-1.5 h-3.5 w-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-700">Belum ada data kegiatan atau kehadiran pada periode ini.</p>
                                            <p class="text-xs text-slate-400">Silakan pilih bulan atau tahun lain pada filter di atas.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Detail Aktivitas --}}
    <div
        x-show="openModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6"
        style="display: none;"
    >
        <div class="flex min-h-full items-center justify-center">
            <div
                @click.away="openModal = false"
                class="w-full max-w-3xl rounded-2xl bg-white shadow-2xl overflow-hidden transform transition-all"
            >
                {{-- Modal Header --}}
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/70 px-6 py-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Detail Aktivitas Harian</span>
                        <h3 class="text-lg font-bold text-slate-900" x-text="activeDay ? activeDay.tanggal_formatted : ''"></h3>
                        <p class="text-xs text-slate-500">
                            Siswa: <span class="font-semibold text-slate-700">{{ $selectedSiswa->nama ?? '' }}</span>
                            &bull; Status Kehadiran: <span class="font-semibold text-slate-700" x-text="activeDay ? activeDay.status_absensi : '-'"></span>
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="openModal = false"
                        class="rounded-xl p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-700 transition"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                    <template x-if="activeDay && activeDay.aktivitas && activeDay.aktivitas.length > 0">
                        <div class="space-y-4">
                            <template x-for="(akt, idx) in activeDay.aktivitas" :key="akt.id || idx">
                                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-xs font-bold" x-text="idx + 1"></span>
                                            <h4 class="text-base font-bold text-slate-900" x-text="akt.judul"></h4>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
                                                :class="{
                                                    'bg-emerald-100 text-emerald-800': akt.status_color === 'emerald',
                                                    'bg-amber-100 text-amber-800': akt.status_color === 'amber',
                                                    'bg-rose-100 text-rose-800': akt.status_color === 'rose',
                                                    'bg-slate-100 text-slate-800': akt.status_color === 'slate'
                                                }"
                                                x-text="akt.status"
                                            ></span>
                                            <span class="text-xs font-mono font-medium text-slate-500" x-text="'Waktu: ' + akt.waktu"></span>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Deskripsi Kegiatan</p>
                                        <p class="mt-1 text-sm text-slate-700 whitespace-pre-line" x-text="akt.deskripsi"></p>
                                    </div>

                                    <template x-if="akt.hasil">
                                        <div class="rounded-lg bg-slate-50 p-3 text-xs">
                                            <span class="font-bold text-slate-700">Hasil:</span>
                                            <span class="text-slate-600 ml-1" x-text="akt.hasil"></span>
                                        </div>
                                    </template>

                                    <template x-if="akt.kendala || akt.solusi">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                            <div class="rounded-lg bg-amber-50/70 border border-amber-200/50 p-2.5" x-show="akt.kendala">
                                                <span class="font-bold text-amber-800">Kendala:</span>
                                                <span class="text-amber-900 ml-1" x-text="akt.kendala"></span>
                                            </div>
                                            <div class="rounded-lg bg-emerald-50/70 border border-emerald-200/50 p-2.5" x-show="akt.solusi">
                                                <span class="font-bold text-emerald-800">Solusi:</span>
                                                <span class="text-emerald-900 ml-1" x-text="akt.solusi"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="akt.foto_url">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Bukti / Foto Kegiatan</p>
                                            <a :href="akt.foto_url" target="_blank" class="inline-block group relative rounded-xl overflow-hidden border border-slate-200">
                                                <img :src="akt.foto_url" alt="Foto Aktivitas" class="h-32 w-48 object-cover transition group-hover:scale-105" />
                                                <span class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-semibold transition">
                                                    Lihat Foto
                                                </span>
                                            </a>
                                        </div>
                                    </template>

                                    <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-2">
                                        <div>
                                            <span class="text-slate-400">Pembimbing:</span>
                                            <span class="font-medium text-slate-700 ml-1" x-text="akt.pembimbing"></span>
                                        </div>
                                        <template x-if="akt.catatan">
                                            <div class="text-right">
                                                <span class="text-slate-400">Catatan:</span>
                                                <span class="font-medium text-slate-700 ml-1" x-text="akt.catatan"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!activeDay || !activeDay.aktivitas || activeDay.aktivitas.length === 0">
                        <div class="py-8 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <h4 class="mt-3 text-sm font-semibold text-slate-800">Tidak ada aktivitas pada tanggal ini</h4>
                            <p class="mt-1 text-xs text-slate-500">Siswa tidak mencatatkan jurnal kegiatan harian untuk tanggal ini.</p>
                        </div>
                    </template>
                </div>

                {{-- Modal Footer --}}
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-3.5 text-right">
                    <button
                        type="button"
                        @click="openModal = false"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
