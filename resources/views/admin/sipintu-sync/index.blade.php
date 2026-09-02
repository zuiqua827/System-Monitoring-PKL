@extends('layouts.app')

@section('title', 'Sinkronisasi SiPintu')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="{ syncing: false }">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">{{ session('error') }}</div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Integrasi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Sinkronisasi SiPintu</h1>
                <p class="mt-2 text-sm text-slate-500">Sinkronkan data siswa dari gateway SiPintu ke sistem SIPKL.</p>
            </div>

        {{-- Sync / Preview Buttons --}}
            <div class="flex flex-col gap-3 sm:flex-row">
                <form method="POST" action="{{ route('admin.sipintu-sync.test-connection') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        Test Connection
                    </button>
                </form>
                <a href="{{ route('admin.sipintu-sync.preview') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-5 py-2.5 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Preview (Dry Run)
                </a>
                <form method="POST" action="{{ route('admin.sipintu-sync.sync') }}" @submit.prevent="
                    if (!confirm('Mulai sinkronisasi data siswa dari SiPintu? Disarankan jalankan Preview terlebih dahulu.')) { return; }
                    syncing = true;
                    $el.submit();
                ">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="syncing"
                    >
                        <svg x-show="!syncing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <svg x-show="syncing" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span x-show="!syncing">Mulai Sinkronisasi</span>
                        <span x-show="syncing" x-cloak>Menyinkronkan...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Connection + Stats Cards --}}
        @php
            $connBadge = match ($connectionStatus) {
                'connected' => ['label' => 'Terhubung', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                'not_configured' => ['label' => 'Belum Dikonfigurasi', 'class' => 'bg-amber-50 text-amber-700 ring-amber-100'],
                default => ['label' => 'Gagal Terhubung', 'class' => 'bg-red-50 text-red-700 ring-red-100'],
            };
        @endphp

        {{-- Connection Diagnostics Alert (If connection failed or details available) --}}
        @if ($connectionStatus !== 'connected' || !empty($connectionDetail) || !empty($connectionTroubleshooting))
            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-5 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <div class="rounded-xl bg-rose-100 p-2.5 text-rose-600 shrink-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-rose-900">Diagnosis Koneksi SiPintu</h3>
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 uppercase tracking-wider">
                                Status: {{ $connBadge['label'] }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-rose-800">{{ $connectionMessage }}</p>

                        @if (!empty($connectionDetail))
                            <div class="rounded-xl border border-rose-200/80 bg-white/80 p-3.5 text-xs text-rose-950 font-mono break-all">
                                <span class="font-bold text-rose-900 font-sans block mb-1">Penyebab / Detail Error API:</span>
                                {{ $connectionDetail }}
                            </div>
                        @endif

                        @if (!empty($connectionTroubleshooting))
                            <div class="flex items-start gap-2 text-xs font-medium text-rose-900 pt-1">
                                <svg class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 2.625a3.375 3.375 0 00-3.75-3.375m-3.75 3.375a3.375 3.375 0 013.75-3.375m0 0V9a2.25 2.25 0 012.25-2.25h.375m-2.625 0H9.375A2.25 2.25 0 007.125 9v1.875" />
                                </svg>
                                <span><strong>Saran Perbaikan:</strong> {{ $connectionTroubleshooting }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-5 sm:grid-cols-1 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Connection Status --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <div class="flex flex-col sm:flex-row gap-4 sm: sm: items-start">
                    <p class="text-sm font-semibold text-slate-500">Status Koneksi</p>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $connBadge['class'] }}">{{ $connBadge['label'] }}</span>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ $connectionMessage }}</p>
                @if (!empty($connectionDetail))
                    <p class="mt-2 text-xs font-mono text-rose-600 truncate" title="{{ $connectionDetail }}">
                        Detail: {{ $connectionDetail }}
                    </p>
                @endif
            </article>

            {{-- Last Sync --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <p class="text-sm font-semibold text-slate-500">Sinkronisasi Terakhir</p>
                @if ($lastSync)
                    <p class="mt-3 text-lg font-bold text-slate-900">{{ \Carbon\Carbon::parse($lastSync['ran_at'])->format('d M Y H:i') }}</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $lastSync['status'] === 'success' ? 'Berhasil' : 'Gagal' }}
                        · {{ number_format($lastSync['duration_ms'] / 1000, 1) }} detik
                    </p>
                @else
                    <p class="mt-3 text-sm text-slate-500">Belum ada sinkronisasi.</p>
                @endif
            </article>

{{-- SiPintu Students --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <p class="text-sm font-semibold text-slate-500">Total Siswa SiPintu</p>
                <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($sipintuStudentCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Data siswa di gateway SiPintu</p>
            </article>

            {{-- SIPKL Students --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <p class="text-sm font-semibold text-slate-500">Total Siswa SIPKL</p>
                <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($localStudentCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Data siswa lokal</p>
            </article>

            {{-- SiPintu Teachers --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <p class="text-sm font-semibold text-slate-500">Total Guru SiPintu</p>
                <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($sipintuTeacherCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Data guru di gateway SiPintu</p>
            </article>

            {{-- SIPKL Teachers --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <p class="text-sm font-semibold text-slate-500">Total Guru SIPKL</p>
                <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($localTeacherCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Data guru lokal</p>
            </article>
        </div>

        @if ($connectionStatus === 'connected' && $sipintuStudentCount === 0 && $sipintuTeacherCount === 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                Belum ada data yang tersedia untuk disinkronkan dari SiPintu. Tombol sinkronisasi tetap dapat digunakan; hasil 0 data akan dicatat sebagai berhasil.
            </div>
        @endif

        @if ($classroomMappingCount === 0)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
                Belum ada classroom mapping SiPintu. Data siswa yang memerlukan kelas akan dilewati dengan aman sampai pemetaan dibuat di
                <a href="{{ route('admin.sipintu-classroom-mapping.index') }}" class="font-semibold underline">Pemetaan Kelas SiPintu</a>.
            </div>
        @endif

 {{-- Info Banner --}}
        <div class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold">Data <span class="font-bold">Siswa</span> dan <span class="font-bold">Guru</span> disinkronkan dari SiPintu.</p>
                <p class="mt-1 text-blue-700">NIS digunakan sebagai identitas unik siswa, NIP untuk guru. Data yang sudah ada diperbarui, data baru ditambahkan, dan data dummy yang tidak ada di SiPintu dinonaktifkan (soft delete). DUDI, Admin, dan modul PKL tidak pernah diubah.</p>
            </div>
        </div>

        {{-- Preview Result --}}
        @if ($preview)
            <div class="rounded-2xl border {{ $preview['success'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-red-200 bg-red-50/50' }} shadow-card-sm">
                <div class="flex flex-col sm:flex-row gap-4 sm: sm: border-b border-slate-200/60 px-6 py-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Hasil Preview (Dry Run)</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            @if ($preview['success'])
                                Data TIDAK diubah. Berikut klasifikasi perbandingan data SiPintu vs data lokal.
                            @else
                                {{ $preview['message'] }}
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ number_format($preview['duration_ms'] / 1000, 2) }} detik</span>
                </div>

                @if ($preview['success'])
                    <div class="grid gap-6 p-6 lg:grid-cols-1 sm:grid-cols-2">
                        {{-- Student Preview --}}
                        <div>
                            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">Siswa</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @php
                                    $studentItems = [
                                        'Baru (Akan Ditambah)' => $preview['students']['baru'] ?? 0,
                                        'Akan Diperbarui' => $preview['students']['diperbarui'] ?? 0,
                                        'Tidak Berubah' => $preview['students']['tidak_berubah'] ?? 0,
                                        'Konflik' => $preview['students']['konflik'] ?? 0,
                                        'Perlu Pemetaan Kelas' => $preview['students']['perlu_pemetaan'] ?? 0,
                                        'Tidak Ditemukan' => $preview['students']['tidak_ditemukan'] ?? 0,
                                        'Error' => $preview['students']['error'] ?? 0,
                                        'Total Remote' => $preview['students']['total_remote'] ?? 0,
                                    ];
                                @endphp
                                @foreach ($studentItems as $label => $value)
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-2xl font-extrabold text-slate-900">{{ number_format((int) $value) }}</p>
                                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $label }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Teacher Preview --}}
                        <div>
                            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">Guru</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @php
                                    $teacherItems = [
                                        'Baru (Akan Ditambah)' => $preview['teachers']['baru'] ?? 0,
                                        'Akan Diperbarui' => $preview['teachers']['diperbarui'] ?? 0,
                                        'Tidak Berubah' => $preview['teachers']['tidak_berubah'] ?? 0,
                                        'Konflik' => $preview['teachers']['konflik'] ?? 0,
                                        'Perlu Pemetaan Kelas' => $preview['teachers']['perlu_pemetaan'] ?? 0,
                                        'Tidak Ditemukan' => $preview['teachers']['tidak_ditemukan'] ?? 0,
                                        'Error' => $preview['teachers']['error'] ?? 0,
                                        'Total Remote' => $preview['teachers']['total_remote'] ?? 0,
                                    ];
                                @endphp
                                @foreach ($teacherItems as $label => $value)
                                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                                        <p class="text-2xl font-extrabold text-slate-900">{{ number_format((int) $value) }}</p>
                                        <p class="mt-1 text-xs font-medium text-slate-500">{{ $label }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- History Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="flex flex-col sm:flex-row gap-4 sm: sm: border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Sinkronisasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Log aktivitas sinkronisasi data siswa</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Admin</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
<th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Siswa Ditambah</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Siswa Diperbarui</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Guru Ditambah</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Guru Diperbarui</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($history as $log)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-800">{{ $log->admin_name ?? 'Sistem (terjadwal)' }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($log->status === 'success')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Berhasil</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800" title="{{ $log->message }}">Gagal</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($log->added) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($log->updated) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($log->teacher_added) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($log->teacher_updated) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">{{ number_format($log->duration_ms / 1000, 1) }} s</td>
                            </tr>
@if ($log->status !== 'success' && $log->message)
                                <tr class="bg-red-50/40">
                                    <td colspan="8" class="px-6 py-3 text-sm text-red-700">{{ $log->message }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada riwayat sinkronisasi.</p>
                                        <p class="text-sm text-slate-500">Klik "Mulai Sinkronisasi" untuk menjalankan sinkronisasi pertama.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($history->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
