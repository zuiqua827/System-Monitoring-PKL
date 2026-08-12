@extends('layouts.app')

@section('title', 'Pusat Laporan PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Pusat Laporan</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Laporan PKL</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Pusat rekapitulasi data Siswa, Absensi, Aktivitas, Penempatan, dan Penilaian seluruh SMK.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Laporan Siswa & Penempatan --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm flex flex-col justify-between transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-4.55 6-10a6 6 0 0 0-12 0c0 5.45 6 10 6 10Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">Siswa & Penempatan</h3>
                    <p class="mt-2 text-sm text-slate-500">Data rekapitulasi siswa, jurusan, kelas, beserta status dan lokasi penempatannya.</p>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.laporan.siswa') }}" class="block w-full text-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Tampilkan Laporan</a>
                </div>
            </article>

            {{-- Laporan Absensi --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm flex flex-col justify-between transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">Laporan Absensi</h3>
                    <p class="mt-2 text-sm text-slate-500">Rekap total kehadiran harian, persentase hadir, keterlambatan, izin, sakit dan alpha.</p>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.laporan.absensi') }}" class="block w-full text-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Tampilkan Laporan</a>
                </div>
            </article>

            {{-- Laporan Aktivitas --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm flex flex-col justify-between transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">Laporan Aktivitas</h3>
                    <p class="mt-2 text-sm text-slate-500">Ringkasan pengisian jurnal harian beserta status validasi dari pembimbing dan DUDI.</p>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.laporan.aktivitas') }}" class="block w-full text-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Tampilkan Laporan</a>
                </div>
            </article>

            {{-- Laporan Penilaian --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm flex flex-col justify-between transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 mb-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900">Laporan Penilaian</h3>
                    <p class="mt-2 text-sm text-slate-500">Rekapitulasi nilai akhir, predikat dan detail parameter kompetensi dari DUDI.</p>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.laporan.penilaian') }}" class="block w-full text-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Tampilkan Laporan</a>
                </div>
            </article>
        </div>
    </div>
</div>
@endsection
