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

{{-- Sync Button --}}
            <form method="POST" action="{{ route('admin.sipintu-sync.sync') }}" @submit.prevent="
                if (!confirm('Mulai sinkronisasi data siswa dari SiPintu?')) { return; }
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
                <p class="mt-1 text-xs text-slate-500">Data di gateway SiPintu</p>
            </article>

            {{-- SIPKL Students --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <p class="text-sm font-semibold text-slate-500">Total Siswa SIPKL</p>
                <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($localStudentCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Data siswa lokal</p>
            </article>
        </div>

        {{-- Info Banner --}}
        <div class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold">Hanya data <span class="font-bold">Siswa</span> yang disinkronkan.</p>
                <p class="mt-1 text-blue-700">NIS digunakan sebagai identitas unik. Data yang sudah ada diperbarui, data baru ditambahkan, dan data dummy yang tidak ada di SiPintu dinonaktifkan (soft delete). Data Guru, DUDI, Admin, dan modul PKL tidak pernah diubah.</p>
            </div>
        </div>

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
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Ditambah</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Diperbarui</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Dinonaktifkan</th>
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
                                <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($log->deleted) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-600">{{ number_format($log->duration_ms / 1000, 1) }} s</td>
                            </tr>
                            @if ($log->status !== 'success' && $log->message)
                                <tr class="bg-red-50/40">
                                    <td colspan="7" class="px-6 py-3 text-sm text-red-700">{{ $log->message }}</td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7">
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
