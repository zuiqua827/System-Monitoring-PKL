@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $aktivitasList
     * @var \App\Models\PenempatanPKL|null $penempatanAktif
     * @var \App\Models\Siswa $siswa
     */
@endphp

@extends('layouts.app')

@section('title', 'Aktivitas Harian PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">{{ session('error') }}</div>
        @endif

        {{-- Status Penempatan Aktif --}}
        @if ($penempatanAktif)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 shadow-card-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-4.55 6-10a6 6 0 0 0-12 0c0 5.45 6 10 6 10Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">Penempatan Aktif: {{ $penempatanAktif->dudi?->nama_perusahaan ?? '-' }}</p>
                        <p class="text-xs text-blue-700">{{ $penempatanAktif->periodePKL?->nama ?? '-' }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-card-sm">
                <p class="text-sm font-medium text-amber-800">Anda tidak memiliki penempatan PKL yang aktif.</p>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Siswa</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Aktivitas Harian PKL</h1>
                <p class="mt-2 text-sm text-slate-500">Catat aktivitas harian PKL Anda</p>
            </div>
            @if ($penempatanAktif)
                <a href="{{ route('siswa.aktivitas.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Aktivitas
                </a>
            @endif
        </div>

        {{-- Filters --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm">
            <form method="GET" action="{{ route('siswa.aktivitas.index') }}" class="flex flex-wrap items-center gap-3">
                <div>
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}"
                           class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <select name="status" class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">Semua Status</option>
                        @foreach(\App\Enums\AktivitasStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ request('status') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Filter</button>
                @if(request('tanggal') || request('status'))
                    <a href="{{ route('siswa.aktivitas.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Reset</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Judul</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jam</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($aktivitasList as $index => $aktivitas)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $aktivitasList->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-800">{{ $aktivitas->tanggal ? $aktivitas->tanggal->format('d/m/Y') : '-' }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-900">
                                    <span class="block max-w-[220px] truncate" title="{{ $aktivitas->judul }}">{{ $aktivitas->judul }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">
                                    {{ $aktivitas->jam_mulai ?? '-' }} - {{ $aktivitas->jam_selesai ?? '-' }}
                                </td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $statusClass = match($aktivitas->status) {
                                            'draft' => 'bg-slate-100 text-slate-700',
                                            'menunggu_validasi' => 'bg-amber-100 text-amber-800',
                                            'disetujui' => 'bg-emerald-100 text-emerald-800',
                                            'ditolak' => 'bg-red-100 text-red-800',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                        {{ \App\Enums\AktivitasStatus::tryFrom($aktivitas->status)?->label() ?? $aktivitas->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('siswa.aktivitas.show', $aktivitas->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>

                                        @if($aktivitas->status === 'draft')
                                            <a href="{{ route('siswa.aktivitas.edit', $aktivitas->id) }}" class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">Edit</a>

                                            <form method="POST" action="{{ route('siswa.aktivitas.destroy', $aktivitas->id) }}" class="inline" onsubmit="return confirm('Hapus aktivitas ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100">Hapus</button>
                                            </form>

                                            <form method="POST" action="{{ route('siswa.aktivitas.submit', $aktivitas->id) }}" class="inline" onsubmit="return confirm('Kirim aktivitas untuk divalidasi?')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">Kirim</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h9M8 12h9M8 18h5M5 6h.01M5 12h.01M5 18h.01" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">
                                            @if(request('tanggal') || request('status'))
                                                Tidak ada hasil untuk filter yang dipilih.
                                            @else
                                                Belum ada aktivitas harian.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($aktivitasList->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $aktivitasList->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
