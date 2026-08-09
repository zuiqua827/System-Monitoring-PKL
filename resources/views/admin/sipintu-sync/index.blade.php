@extends('layouts.app')

@section('title', 'Sinkronisasi SiPintu')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8" x-data="{ syncing: false }">
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

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            {{-- Connection Status --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <div class="flex items-start justify-between">
                    <p class="text-sm font-semibold text-slate-500">Status Koneksi</p>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $connBadge['class'] }}">{{ $connBadge['label'] }}</span>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ $connectionMessage }}</p>
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
                <div class="flex items-center justify-between border-b border-slate-200/60 px-6 py-5">
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
                    <div class="grid gap-6 p-6 lg:grid-cols-2">
                        {{-- Student Preview --}}
                        <div>
                            <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-slate-500">Siswa</h3>
                            <div class="grid grid-cols-2 gap-3">
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
                            <div class="grid grid-cols-2 gap-3">
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
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Sinkronisasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Log aktivitas sinkronisasi data siswa</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
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
