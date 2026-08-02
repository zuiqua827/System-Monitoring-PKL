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
        <div class="grid gap-5 sm:grid-cols-3">
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
