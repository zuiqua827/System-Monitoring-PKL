@extends('layouts.app')

@section('title', 'Dashboard DUDI')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Dashboard DUDI</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Monitoring perkembangan PKL di perusahaan Anda</p>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid gap-5 sm:grid-cols-4">
            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Siswa PKL</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['total_siswa_pkl'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 19c0-2.2-1.8-4-4-4s-4 1.8-4 4M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6.5 6.5c0-1.5-.8-2.75-2-3.45M5.5 18.5c0-1.5.8-2.75 2-3.45" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Total siswa PKL di perusahaan</p>
            </article>

            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Absensi Hari Ini</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['absensi_hari_ini'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4M5.5 9.5h13M7 20h10a2.5 2.5 0 0 0 2.5-2.5v-10A2.5 2.5 0 0 0 17 5H7a2.5 2.5 0 0 0-2.5 2.5v10A2.5 2.5 0 0 0 7 20Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Siswa yang hadir hari ini</p>
            </article>

            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Aktivitas Hari Ini</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['aktivitas_hari_ini'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Jurnal kegiatan hari ini</p>
            </article>

            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Menunggu Validasi</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-amber-600">{{ $stats['aktivitas_menunggu_validasi'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Aktivitas perlu diverifikasi</p>
            </article>
        </div>

        {{-- Charts --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h3 class="text-base font-bold text-slate-900">Tren Absensi (7 Hari)</h3>
                <p class="mt-1 text-sm text-slate-500">Grafik kehadiran siswa</p>
                <div class="mt-6">
                    <canvas id="absensiChart" height="200"></canvas>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h3 class="text-base font-bold text-slate-900">Tren Aktivitas (7 Hari)</h3>
                <p class="mt-1 text-sm text-slate-500">Grafik aktivitas harian</p>
                <div class="mt-6">
                    <canvas id="aktivitasChart" height="200"></canvas>
                </div>
            </article>
        </div>

        {{-- Recent Sections --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Recent Activities --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm flex flex-col">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Aktivitas Terbaru</h3>
                    <p class="mt-1 text-sm text-slate-500">Log aktivitas siswa terkini di perusahaan Anda</p>
                </div>
                <div class="p-6">
                    @if(count($recentActivities) > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($recentActivities as $loopIndex => $activity)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                        @if($activity['tipe'] === 'checkin') bg-emerald-500
                                                        @elseif($activity['tipe'] === 'checkout') bg-rose-500
                                                        @else bg-blue-500 @endif
                                                    ">
                                                        @if($activity['tipe'] === 'checkin')
                                                            <svg class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                            </svg>
                                                        @elseif($activity['tipe'] === 'checkout')
                                                            <svg class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H3.75A.75.75 0 013 10z" clip-rule="evenodd" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-slate-500">
                                                            <span class="font-medium text-slate-900">{{ $activity['user'] }}</span>
                                                            {{ str_replace($activity['user'], '', $activity['deskripsi']) }}
                                                        </p>
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-slate-500">
                                                        <time datetime="{{ $activity['waktu'] }}">{{ $activity['waktu'] }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-sm text-slate-500">Belum ada aktivitas terbaru.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent Siswa --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm flex flex-col">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Siswa PKL Terbaru</h3>
                    <p class="mt-1 text-sm text-slate-500">Siswa yang baru ditempatkan di perusahaan Anda</p>
                </div>
                <div class="p-6 flex-1">
                    @if(count($recentSiswa) > 0)
                        <ul role="list" class="divide-y divide-slate-100">
                            @foreach($recentSiswa as $siswa)
                                <li class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600 font-bold">
                                            {{ substr($siswa['siswa']['nama'] ?? 'S', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $siswa['siswa']['nama'] ?? '-' }}</p>
                                            <p class="text-xs text-slate-500">{{ $siswa['siswa']['kelas']['nama'] ?? '-' }} - {{ $siswa['siswa']['kelas']['jurusan']['nama'] ?? '-' }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('dudi.siswa.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Detail</a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <p class="text-sm text-slate-500">Belum ada siswa PKL.</p>
                        </div>
                    @endif
                </div>
                <div class="border-t border-slate-100 px-6 py-4 bg-slate-50 rounded-b-2xl">
                    <a href="{{ route('dudi.siswa.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat Semua Siswa &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('absensiChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: {!! json_encode(array_map(fn($d) => \Carbon\Carbon::parse($d['tanggal'])->format('d/m'), $charts['absensi_7_hari'])) !!},
            datasets: [{label: 'Hadir', data: {!! json_encode(array_map(fn($d) => $d['hadir'], $charts['absensi_7_hari'])) !!}, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4}]
        },
        options: {responsive: true, plugins: {legend: {position: 'bottom'}}, scales: {y: {beginAtZero: true}}}
    });

    new Chart(document.getElementById('aktivitasChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_map(fn($d) => \Carbon\Carbon::parse($d['tanggal'])->format('d/m'), $charts['aktivitas_7_hari'])) !!},
            datasets: [{label: 'Aktivitas', data: {!! json_encode(array_map(fn($d) => $d['total'], $charts['aktivitas_7_hari'])) !!}, backgroundColor: '#6366f1'}]
        },
        options: {responsive: true, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true}}}
    });
});
</script>
@endpush
