@extends('layouts.app')

@section('title', 'Detail Penempatan PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        {{-- Page header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Akademik</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Penempatan PKL</h1>
                <p class="mt-2 text-sm text-slate-500">Informasi lengkap penempatan siswa PKL</p>
            </div>
            <a href="{{ route('admin.penempatan-pkl.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="space-y-6">
            {{-- Informasi Penempatan --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Informasi Penempatan PKL</h3>
                    <p class="mt-1 text-sm text-slate-500">Data lengkap penempatan PKL</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Siswa</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->siswa?->nama ?? '-' }}</p>
                        @if($penempatanPkl->siswa)
                            <p class="mt-0.5 text-xs text-slate-500">NIS: {{ $penempatanPkl->siswa->nis }}</p>
                        @endif
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <p class="mt-1 text-sm font-semibold">
                            @if($penempatanPkl->status === 'aktif')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Aktif</span>
                            @elseif($penempatanPkl->status === 'pending')
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Pending</span>
                            @elseif($penempatanPkl->status === 'selesai')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">Selesai</span>
                            @elseif($penempatanPkl->status === 'dibatalkan')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dibatalkan</span>
                            @else
                                <span class="text-slate-400">{{ $penempatanPkl->status }}</span>
                            @endif
                            @if($penempatanPkl->trashed())
                                <span class="ml-1 inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guru Pembimbing</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->guru?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Perusahaan (DUDI)</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->dudi?->nama_perusahaan ?? $penempatanPkl->dudi?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Periode PKL</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->periodePKL?->nama ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nomor Surat</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->nomor_surat ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Mulai</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->tanggal_mulai ? $penempatanPkl->tanggal_mulai->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Selesai</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->tanggal_selesai ? $penempatanPkl->tanggal_selesai->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Dibuat Oleh</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->dibuatOleh?->name ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Dibuat</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->created_at ? $penempatanPkl->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    @if($penempatanPkl->approvedBy)
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Disetujui Oleh</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->approvedBy->name }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Disetujui</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->approved_at ? $penempatanPkl->approved_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    @endif
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $penempatanPkl->catatan ?: '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Absensi</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->absensi_count ?? $penempatanPkl->absensi()->count() }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Aktivitas</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatanPkl->aktivitas_count ?? $penempatanPkl->aktivitas()->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.penempatan-pkl.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Kembali
                </a>
                @unless($penempatanPkl->trashed())
                    @can('update', $penempatanPkl)
                        <a href="{{ route('admin.penempatan-pkl.edit', $penempatanPkl->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 113.182 3.182L7.5 20.25 3 21.75l1.5-4.5L16.862 4.487z" />
                            </svg>
                            Edit Penempatan
                        </a>
                    @endcan
                    @can('delete', $penempatanPkl)
                        <form method="POST" action="{{ route('admin.penempatan-pkl.destroy', $penempatanPkl->id) }}" onsubmit="return confirm('Hapus penempatan PKL ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Hapus Penempatan
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $penempatanPkl)
                        <form method="POST" action="{{ route('admin.penempatan-pkl.restore', $penempatanPkl->id) }}" onsubmit="return confirm('Pulihkan penempatan PKL ini?')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3-3m-3 3l-3-3m3 3l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pulihkan Penempatan
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $penempatanPkl)
                        <form method="POST" action="{{ route('admin.penempatan-pkl.force-delete', $penempatanPkl->id) }}" onsubmit="return confirm('Hapus permanen penempatan PKL ini? Tindakan ini tidak dapat dibatalkan!')">
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
