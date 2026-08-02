@extends('layouts.app')

@section('title', 'Dashboard Guru')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Dashboard Guru</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-500">Monitoring perkembangan PKL siswa bimbingan</p>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Siswa Bimbingan</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['total_siswa_bimbingan'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 19c0-2.2-1.8-4-4-4s-4 1.8-4 4M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6.5 6.5c0-1.5-.8-2.75-2-3.45M5.5 18.5c0-1.5.8-2.75 2-3.45" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Total siswa yang dibimbing</p>
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
                <p class="mt-4 text-sm text-slate-500">Siswa yang sudah absen hari ini</p>
            </article>

            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Menunggu Validasi</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['aktivitas_menunggu_validasi'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Aktivitas perlu divalidasi</p>
            </article>

            <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Penilaian Draft</p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $stats['penilaian_draft'] }}</p>
                    </div>
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-4 text-sm text-slate-500">Penilaian yang belum final</p>
            </article>
        </div>

        {{-- Charts --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h3 class="text-base font-bold text-slate-900">Kehadiran 7 Hari</h3>
                <p class="mt-1 text-sm text-slate-500">Grafik kehadiran siswa bimbingan</p>
                <div class="mt-6">
                    <canvas id="attendanceChart" height="200"></canvas>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h3 class="text-base font-bold text-slate-900">Status Aktivitas</h3>
                <p class="mt-1 text-sm text-slate-500">Distribusi status aktivitas siswa</p>
                <div class="mt-6">
                    <canvas id="aktivitasChart" height="200"></canvas>
                </div>
            </article>
        </div>

        {{-- Nilai Siswa Bimbingan --}}
        <article class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Nilai Siswa Bimbingan</h3>
                <p class="mt-1 text-sm text-slate-500">Daftar nilai akhir siswa bimbingan</p>
            </div>
            @if(count($charts['nilai_siswa']) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Siswa</th>
                                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nilai Akhir</th>
                                <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Predikat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($charts['nilai_siswa'] as $nilai)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $nilai['nama_siswa'] }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-900">{{ $nilai['nilai_akhir'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold
                                        @if($nilai['predikat'] == 'A') bg-emerald-100 text-emerald-800
                                        @elseif($nilai['predikat'] == 'B') bg-blue-100 text-blue-800
                                        @elseif($nilai['predikat'] == 'C') bg-amber-100 text-amber-800
                                        @elseif($nilai['predikat'] == 'D') bg-orange-100 text-orange-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $nilai['predikat'] }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                        </svg>
                    </div>
                    <p class="mt-4 text-sm font-bold text-slate-700">Belum ada penilaian final</p>
                    <p class="mt-1 text-sm text-slate-500">Penilaian akan muncul setelah guru membuat penilaian final.</p>
                </div>
            @endif
        </article>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx1 = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_map(fn($d) => \Carbon\Carbon::parse($d['tanggal'])->format('d/m'), $charts['attendance_7_hari'])) !!},
            datasets: [
                {label: 'Hadir', data: {!! json_encode(array_map(fn($d) => $d['hadir'], $charts['attendance_7_hari'])) !!}, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', tension: 0.4},
                {label: 'Terlambat', data: {!! json_encode(array_map(fn($d) => $d['terlambat'], $charts['attendance_7_hari'])) !!}, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.4}
            ]
        },
        options: {responsive: true, plugins: {legend: {position: 'bottom'}}, scales: {y: {beginAtZero: true}}}
    });

    var ctx2 = document.getElementById('aktivitasChart').getContext('2d');
    new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_keys($charts['status_aktivitas'])) !!},
            datasets: [{data: {!! json_encode(array_values($charts['status_aktivitas'])) !!}, backgroundColor: ['#6b7280', '#f59e0b', '#10b981', '#ef4444']}]
        },
        options: {responsive: true, plugins: {legend: {position: 'bottom'}}}
    });
});
</script>
@endpush
