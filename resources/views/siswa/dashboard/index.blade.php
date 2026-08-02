@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Dashboard Siswa</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Monitoring aktivitas PKL Anda</p>
        </div>

        @if(isset($penempatanAktif) && $penempatanAktif)
            {{-- Status Cards --}}
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Status Check In</p>
                            <p class="mt-3 text-3xl font-extrabold tracking-tight {{ $sudahCheckIn ? 'text-emerald-600' : 'text-amber-600' }}">{{ $sudahCheckIn ? 'Sudah' : 'Belum' }}</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $sudahCheckIn ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                @if($sudahCheckIn)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                @endif
                            </svg>
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">{{ $sudahCheckIn ? 'Anda sudah check in hari ini' : 'Anda belum check in hari ini' }}</p>
                </article>

                <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Total Absensi</p>
                            <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['total_absensi'] ?? 0 }}</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4M5.5 9.5h13M7 20h10a2.5 2.5 0 0 0 2.5-2.5v-10A2.5 2.5 0 0 0 17 5H7a2.5 2.5 0 0 0-2.5 2.5v10A2.5 2.5 0 0 0 7 20Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Total kehadiran Anda</p>
                </article>

                <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Total Aktivitas</p>
                            <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['total_aktivitas'] ?? 0 }}</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Jurnal kegiatan Anda</p>
                </article>

                <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">Kehadiran</p>
                            <p class="mt-3 text-3xl font-extrabold tracking-tight {{ ($stats['kehadiran_persen'] ?? 0) >= 75 ? 'text-emerald-600' : 'text-red-600' }}">{{ $stats['kehadiran_persen'] ?? 0 }}%</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">Persentase kehadiran</p>
                </article>
            </div>

            {{-- Progress & Info --}}
            <div class="mt-6 grid gap-6 lg:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm lg:col-span-2">
                    <h3 class="text-base font-bold text-slate-900">Progres PKL</h3>
                    <p class="mt-1 text-sm text-slate-500">Perkembangan PKL Anda</p>
                    @if(isset($stats['progress_persen']))
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-semibold text-slate-700">Progress</span>
                                <span class="text-sm font-bold text-slate-900">{{ $stats['progress_persen'] }}%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $stats['progress_persen'] }}%"></div>
                            </div>
                        </div>
                    @endif
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Mulai</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $penempatanAktif->tanggal_mulai ? \Carbon\Carbon::parse($penempatanAktif->tanggal_mulai)->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Selesai</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $penempatanAktif->tanggal_selesai ? \Carbon\Carbon::parse($penempatanAktif->tanggal_selesai)->format('d/m/Y') : '-' }}</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                    <h3 class="text-base font-bold text-slate-900">Info DUDI</h3>
                    <p class="mt-1 text-sm text-slate-500">Tempat PKL Anda</p>
                    <div class="mt-6 space-y-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perusahaan</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $penempatanAktif->dudi?->nama_perusahaan ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $penempatanAktif->dudi?->alamat ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pembimbing</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ $penempatanAktif->guru?->nama ?? '-' }}</p>
                        </div>
                    </div>
                </article>
            </div>

            {{-- Nilai Akhir --}}
            @if(isset($stats['nilai_akhir']))
            <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h3 class="text-base font-bold text-slate-900">Nilai PKL</h3>
                <p class="mt-1 text-sm text-slate-500">Nilai akhir PKL Anda</p>
                <div class="mt-6 flex items-center gap-6">
                    <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-blue-50">
                        <span class="text-4xl font-extrabold text-blue-600">{{ $stats['nilai_akhir'] }}</span>
                    </div>
                    <div>
                        <span class="inline-flex items-center rounded-full px-4 py-1.5 text-sm font-bold
                            @if($stats['predikat'] == 'A') bg-emerald-100 text-emerald-800
                            @elseif($stats['predikat'] == 'B') bg-blue-100 text-blue-800
                            @elseif($stats['predikat'] == 'C') bg-amber-100 text-amber-800
                            @elseif($stats['predikat'] == 'D') bg-orange-100 text-orange-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ $stats['predikat'] }}
                        </span>
                        <p class="mt-1 text-sm text-slate-500">Predikat</p>
                    </div>
                </div>
            </article>
            @endif
        @else
            {{-- Empty State --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-16 text-center shadow-card-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">Belum Ada Penempatan PKL</h3>
                <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">Anda belum memiliki penempatan PKL yang aktif. Silakan hubungi guru pembimbing Anda.</p>
            </div>
        @endif
    </div>
</div>
@endsection
