@php
    /**
     * @var \App\Models\Aktivitas $aktivitas
     */
@endphp

@extends('layouts.app')

@section('title', 'Detail Aktivitas Harian')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        {{-- Page header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Monitoring</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Aktivitas Harian</h1>
                <p class="mt-2 text-sm text-slate-500">Informasi lengkap aktivitas PKL siswa</p>
            </div>
            <a href="{{ route('admin.aktivitas.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="space-y-6">
            {{-- Informasi Aktivitas --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Informasi Aktivitas</h3>
                    <p class="mt-1 text-sm text-slate-500">Data aktivitas harian PKL</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Siswa</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                        @if($aktivitas->penempatanPKL?->siswa)
                            <p class="mt-0.5 text-xs text-slate-500">NIS: {{ $aktivitas->penempatanPKL->siswa->nis }}</p>
                        @endif
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <p class="mt-1 text-sm font-semibold">
                            @php
                                $statusClasses = [
                                    'draft' => 'bg-slate-100 text-slate-800',
                                    'menunggu_validasi' => 'bg-amber-100 text-amber-800',
                                    'disetujui' => 'bg-emerald-100 text-emerald-800',
                                    'ditolak' => 'bg-red-100 text-red-800',
                                ];
                                $statusClass = $statusClasses[$aktivitas->status] ?? 'bg-slate-100 text-slate-800';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                {{ App\Enums\AktivitasStatus::tryFrom($aktivitas->status)?->label() ?? $aktivitas->status }}
                            </span>
                            @if($aktivitas->trashed())
                                <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->tanggal ? $aktivitas->tanggal->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Jam</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->jam_mulai ?? '-' }} - {{ $aktivitas->jam_selesai ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guru Pembimbing</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->penempatanPKL?->guru?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Perusahaan (DUDI)</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode PKL</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->penempatanPKL?->periodePKL?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Dibuat</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->created_at ? $aktivitas->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Judul</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->judul }}</p>
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Deskripsi</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->deskripsi ?: '-' }}</p>
                    </div>
                    @if($aktivitas->hasil)
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Hasil</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->hasil }}</p>
                    </div>
                    @endif
                    @if($aktivitas->kendala)
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kendala</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->kendala }}</p>
                    </div>
                    @endif
                    @if($aktivitas->solusi)
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Solusi</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->solusi }}</p>
                    </div>
                    @endif
                    @if($aktivitas->foto_kegiatan)
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Foto Kegiatan</p>
                        <img src="{{ asset('storage/' . $aktivitas->foto_kegiatan) }}" alt="Foto Kegiatan" class="mt-2 h-48 w-auto rounded-xl border border-slate-200 object-cover shadow-card-sm">
                    </div>
                    @endif
                </div>
            </div>

            {{-- Informasi Validasi Guru --}}
            @if($aktivitas->catatan_guru || $aktivitas->validatedBy)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Validasi Guru</h3>
                    <p class="mt-1 text-sm text-slate-500">Informasi validasi dari guru pembimbing</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    @if($aktivitas->validatedBy)
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Divalidasi Oleh</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->validatedBy->name }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Validasi</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->validated_at ? $aktivitas->validated_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    @endif
                    @if($aktivitas->catatan_guru)
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan Guru</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->catatan_guru }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Tombol Aksi --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.aktivitas.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Kembali
                </a>
                @unless($aktivitas->trashed())
                    @can('update', $aktivitas)
                        <a href="{{ route('admin.aktivitas.edit', $aktivitas->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 113.182 3.182L7.5 20.25 3 21.75l1.5-4.5L16.862 4.487z" />
                            </svg>
                            Edit Aktivitas
                        </a>
                    @endcan
                    @can('delete', $aktivitas)
                        <form method="POST" action="{{ route('admin.aktivitas.destroy', $aktivitas->id) }}" onsubmit="return confirm('Hapus aktivitas ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Hapus Aktivitas
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $aktivitas)
                        <form method="POST" action="{{ route('admin.aktivitas.restore', $aktivitas->id) }}" onsubmit="return confirm('Pulihkan aktivitas ini?')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3-3m-3 3l-3-3m3 3l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pulihkan Aktivitas
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $aktivitas)
                        <form method="POST" action="{{ route('admin.aktivitas.force-delete', $aktivitas->id) }}" onsubmit="return confirm('Hapus permanen aktivitas ini? Tindakan ini tidak dapat dibatalkan!')">
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

