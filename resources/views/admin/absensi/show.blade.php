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
    <div class="mx-auto max-w-5xl">
        {{-- Page header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Monitoring</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Absensi</h1>
                <p class="mt-2 text-sm text-slate-500">Informasi lengkap absensi PKL</p>
            </div>
            <a href="{{ route('admin.absensi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="space-y-6">
            {{-- Informasi Absensi --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Informasi Absensi</h3>
                    <p class="mt-1 text-sm text-slate-500">Data absensi PKL</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Siswa</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                        @if($absensi->penempatanPKL?->siswa)
                            <p class="mt-0.5 text-xs text-slate-500">NIS: {{ $absensi->penempatanPKL->siswa->nis }}</p>
                        @endif
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        @php
                            $statusEnum = AbsensiStatus::tryFrom($absensi->status);
                        @endphp
                        <p class="mt-1 text-sm font-semibold">
                            @if($statusEnum)
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $statusEnum->color() === 'emerald' ? 'bg-emerald-100 text-emerald-800' : ($statusEnum->color() === 'amber' ? 'bg-amber-100 text-amber-800' : ($statusEnum->color() === 'blue' ? 'bg-blue-100 text-blue-800' : ($statusEnum->color() === 'orange' ? 'bg-orange-100 text-orange-800' : ($statusEnum->color() === 'red' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-800')))) }}">
                                    {{ $statusEnum->label() }}
                                </span>
                            @else
                                <span class="text-slate-400">{{ $absensi->status }}</span>
                            @endif
                            @if($absensi->trashed())
                                <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guru Pembimbing</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->penempatanPKL?->guru?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jam Masuk</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->jam_masuk ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jam Pulang</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->jam_keluar ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Perusahaan (DUDI)</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode PKL</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->penempatanPKL?->periodePKL?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lokasi Masuk</p>
                        <p class="mt-1 text-sm font-mono text-slate-900">{{ $absensi->lokasi_masuk ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Lokasi Pulang</p>
                        <p class="mt-1 text-sm font-mono text-slate-900">{{ $absensi->lokasi_pulang ?? '-' }}</p>
                    </div>
                    @if($absensi->foto_masuk)
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Foto Masuk</p>
                        <a href="{{ asset('storage/' . $absensi->foto_masuk) }}" target="_blank" class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Lihat Foto
                        </a>
                    </div>
                    @endif
                    @if($absensi->foto_pulang)
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Foto Pulang</p>
                        <a href="{{ asset('storage/' . $absensi->foto_pulang) }}" target="_blank" class="mt-1 inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Lihat Foto
                        </a>
                    </div>
                    @endif
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Keterangan</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $absensi->keterangan ?: '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.absensi.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Kembali
                </a>
                @unless($absensi->trashed())
                    @can('update', $absensi)
                        <a href="{{ route('admin.absensi.edit', $absensi->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 113.182 3.182L7.5 20.25 3 21.75l1.5-4.5L16.862 4.487z" />
                            </svg>
                            Edit Absensi
                        </a>
                    @endcan
                    @can('delete', $absensi)
                        <form method="POST" action="{{ route('admin.absensi.destroy', $absensi->id) }}" onsubmit="return confirm('Hapus absensi ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Hapus Absensi
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $absensi)
                        <form method="POST" action="{{ route('admin.absensi.restore', $absensi->id) }}" onsubmit="return confirm('Pulihkan absensi ini?')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3-3m-3 3l-3-3m3 3l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pulihkan Absensi
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $absensi)
                        <form method="POST" action="{{ route('admin.absensi.force-delete', $absensi->id) }}" onsubmit="return confirm('Hapus permanen absensi ini? Tindakan ini tidak dapat dibatalkan!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-800 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-red-900">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Hapus Permanen
                            </button>
                        </form>
                    @endcan
                @endunless
            </div>
        </div>
    </div>
</div>
@endsection

