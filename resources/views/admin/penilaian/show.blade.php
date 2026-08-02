@php
    /**
     * @var \App\Models\Penilaian $penilaian
     */
@endphp

@extends('layouts.app')

@section('title', 'Detail Penilaian PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        {{-- Page header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penilaian</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Penilaian PKL</h1>
                <p class="mt-2 text-sm text-slate-500">Informasi lengkap penilaian PKL siswa</p>
            </div>
            <a href="{{ route('admin.penilaian.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="space-y-6">
            {{-- Informasi Penilaian --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Informasi Penilaian</h3>
                    <p class="mt-1 text-sm text-slate-500">Data penilaian PKL</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Siswa</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                        @if($penilaian->penempatanPKL?->siswa)
                            <p class="mt-0.5 text-xs text-slate-500">NIS: {{ $penilaian->penempatanPKL->siswa->nis }}</p>
                        @endif
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <p class="mt-1 text-sm font-semibold">
                            @if($penilaian->status === 'final')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Final</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Draft</span>
                            @endif
                            @if($penilaian->trashed())
                                <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guru Pembimbing</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Perusahaan (DUDI)</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode PKL</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->periodePKL?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dinilai Oleh</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->dinilaiOleh?->name ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Penilaian</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->tanggal_penilaian ? $penilaian->tanggal_penilaian->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Dibuat</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->created_at ? $penilaian->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Detail Nilai --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Detail Nilai</h3>
                    <p class="mt-1 text-sm text-slate-500">Rincian nilai per komponen penilaian</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="flex items-center justify-between bg-white p-4">
                        <span class="text-sm font-medium text-slate-700">Disiplin</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_disiplin ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-white p-4">
                        <span class="text-sm font-medium text-slate-700">Kehadiran</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_kehadiran ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-white p-4">
                        <span class="text-sm font-medium text-slate-700">Tanggung Jawab</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_tanggung_jawab ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-white p-4">
                        <span class="text-sm font-medium text-slate-700">Komunikasi</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_komunikasi ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-white p-4">
                        <span class="text-sm font-medium text-slate-700">Kerjasama</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_kerjasama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-white p-4">
                        <span class="text-sm font-medium text-slate-700">Inisiatif</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_inisiatif ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between bg-white p-4 sm:col-span-2">
                        <span class="text-sm font-medium text-slate-700">Teknis</span>
                        <span class="text-sm font-bold text-slate-900">{{ $penilaian->nilai_teknis ?? '-' }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-6 border-t border-slate-100 bg-slate-50 px-6 py-5">
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nilai Akhir</p>
                        <p class="mt-1 text-3xl font-bold text-blue-600">{{ $penilaian->nilai_akhir ?? '-' }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Predikat</p>
                        <p class="mt-1">
                            @if($penilaian->predikat)
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold
                                    @if($penilaian->predikat === 'A') bg-emerald-100 text-emerald-800
                                    @elseif($penilaian->predikat === 'B') bg-blue-100 text-blue-800
                                    @elseif($penilaian->predikat === 'C') bg-amber-100 text-amber-800
                                    @elseif($penilaian->predikat === 'D') bg-orange-100 text-orange-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ $penilaian->predikat }}
                                </span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <p class="mt-1">
                            @if($penilaian->status === 'final')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Final</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Draft</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Catatan --}}
            @if($penilaian->catatan || $penilaian->catatan_guru)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Catatan</h3>
                    <p class="mt-1 text-sm text-slate-500">Catatan dari guru pembimbing</p>
                </div>
                <div class="divide-y divide-slate-100 px-6 py-5">
                    @if($penilaian->catatan)
                        <div class="pb-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $penilaian->catatan }}</p>
                        </div>
                    @endif
                    @if($penilaian->catatan_guru)
                        <div class="pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan Guru</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $penilaian->catatan_guru }}</p>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Tombol Aksi --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.penilaian.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Kembali
                </a>
                @unless($penilaian->trashed())
                    @can('update', $penilaian)
                        <a href="{{ route('admin.penilaian.edit', $penilaian->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 113.182 3.182L7.5 20.25 3 21.75l1.5-4.5L16.862 4.487z" />
                            </svg>
                            Edit Penilaian
                        </a>
                    @endcan
                    @can('delete', $penilaian)
                        <form method="POST" action="{{ route('admin.penilaian.destroy', $penilaian->id) }}" onsubmit="return confirm('Hapus penilaian ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Hapus Penilaian
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $penilaian)
                        <form method="POST" action="{{ route('admin.penilaian.restore', $penilaian->id) }}" onsubmit="return confirm('Pulihkan penilaian ini?')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3-3m-3 3l-3-3m3 3l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pulihkan Penilaian
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $penilaian)
                        <form method="POST" action="{{ route('admin.penilaian.force-delete', $penilaian->id) }}" onsubmit="return confirm('Hapus permanen penilaian ini? Tindakan ini tidak dapat dibatalkan!')">
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
