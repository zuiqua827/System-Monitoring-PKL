@php
    /**
     * @var \App\Models\Absensi $absensi
     */
    use App\Enums\AbsensiStatus;
@endphp

@extends('layouts.app')

@section('title', 'Detail Absensi')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Absensi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Absensi</h1>
            </div>
            <a href="{{ route('siswa.absensi.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Detail Absensi</h3>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                    <div class="mt-1">
                        @php
                            $statusEnum = AbsensiStatus::tryFrom($absensi->status);
                        @endphp
                        @if($statusEnum)
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold
                                @switch($statusEnum->value)
                                    @case('hadir') bg-emerald-100 text-emerald-700 @break
                                    @case('terlambat') bg-amber-100 text-amber-700 @break
                                    @case('izin') bg-blue-100 text-blue-700 @break
                                    @case('sakit') bg-orange-100 text-orange-700 @break
                                    @case('alpha') bg-red-100 text-red-700 @break
                                    @default bg-slate-100 text-slate-700
                                @endswitch">
                                <span class="h-1.5 w-1.5 rounded-full
                                    @switch($statusEnum->value)
                                        @case('hadir') bg-emerald-500 @break
                                        @case('terlambat') bg-amber-500 @break
                                        @case('izin') bg-blue-500 @break
                                        @case('sakit') bg-orange-500 @break
                                        @case('alpha') bg-red-500 @break
                                        @default bg-slate-500
                                    @endswitch"></span>
                                {{ $statusEnum->label() }}
                            </span>
                        @else
                            <span class="text-slate-400">{{ $absensi->status }}</span>
                        @endif
                    </div>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jam Masuk</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->jam_masuk ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jam Pulang</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->jam_keluar ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi Masuk</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->lokasi_masuk ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lokasi Pulang</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->lokasi_pulang ?? '-' }}</p>
                </div>
                @if($absensi->foto_masuk)
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Foto Masuk</p>
                    <img src="{{ asset('storage/' . $absensi->foto_masuk) }}" alt="Foto Masuk" class="mt-1 max-w-xs rounded-xl shadow-sm ring-1 ring-slate-200">
                </div>
                @endif
                @if($absensi->foto_pulang)
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Foto Pulang</p>
                    <img src="{{ asset('storage/' . $absensi->foto_pulang) }}" alt="Foto Pulang" class="mt-1 max-w-xs rounded-xl shadow-sm ring-1 ring-slate-200">
                </div>
                @endif
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->keterangan ?: '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Guru Pembimbing</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->penempatanPKL?->guru?->nama ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perusahaan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
