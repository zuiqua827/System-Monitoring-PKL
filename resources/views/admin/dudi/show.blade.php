@extends('layouts.app')

@section('title', 'Detail DUDI')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl">
        {{-- Page header --}}
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Administrasi</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $dudi->nama_perusahaan }}</h1>
                <p class="mt-2 text-sm text-slate-500">Detail lengkap data DUDI (Dunia Usaha / Dunia Industri)</p>
            </div>
            <a href="{{ route('admin.dudi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="space-y-6">
            {{-- Profile Summary --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm sm:p-8">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-orange-700 text-2xl font-extrabold text-white shadow-lg shadow-orange-500/20">
                        {{ strtoupper(substr($dudi->nama_perusahaan, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-bold text-slate-900">{{ $dudi->nama_perusahaan }}</h2>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $dudi->trashed() ? 'bg-red-100 text-red-800' : ($dudi->status_aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800') }}">
                                {{ $dudi->trashed() ? 'Dihapus' : ($dudi->status_aktif ? 'Aktif' : 'Nonaktif') }}
                            </span>
                        </div>
                        <div class="mt-2 grid gap-x-8 gap-y-1 text-sm text-slate-500 sm:grid-cols-2">
                            <p><span class="font-semibold text-slate-700">PIC:</span> {{ $dudi->penanggung_jawab ?? '-' }}</p>
                            <p><span class="font-semibold text-slate-700">Telepon:</span> {{ $dudi->no_telepon ?? '-' }}</p>
                            <p><span class="font-semibold text-slate-700">Email:</span> {{ $dudi->user?->email ?? '-' }}</p>
                            <p><span class="font-semibold text-slate-700">Penempatan:</span> {{ $dudi->penempatan_count ?? $dudi->penempatan()->count() }} siswa</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Informasi Akun --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Informasi Akun</h3>
                    <p class="mt-1 text-sm text-slate-500">Status dan aktivitas akun login</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Email Login</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->user?->email ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status Akun</p>
                        <p class="mt-1 text-sm font-semibold">
                            @if($dudi->user?->email_verified_at)
                                <span class="text-emerald-600">Terverifikasi</span>
                            @else
                                <span class="text-amber-600">Belum Verifikasi</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Terakhir Login</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->user?->last_login_at ? $dudi->user->last_login_at->format('d/m/Y H:i') : 'Belum pernah' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal Dibuat</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->created_at ? $dudi->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Data Perusahaan --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Data Perusahaan</h3>
                    <p class="mt-1 text-sm text-slate-500">Informasi lengkap data perusahaan</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Nama Instansi/Perusahaan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->nama_perusahaan }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">PIC/Pembimbing Industri</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->penanggung_jawab ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">No. Telepon</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->no_telepon ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Alamat</p>
                        <p class="mt-1 text-sm text-slate-700">{{ $dudi->alamat ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kecamatan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->kecamatan ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kabupaten</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->kabupaten ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Provinsi</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->provinsi ?? '-' }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <p class="mt-1 text-sm font-semibold">
                            @if($dudi->trashed())
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Dihapus</span>
                            @elseif($dudi->status_aktif)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Aktif</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Nonaktif</span>
                            @endif
                        </p>
                    </div>
                    @if($dudi->latitude && $dudi->longitude)
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Latitude</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->latitude }}</p>
                    </div>
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Longitude</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->longitude }}</p>
                    </div>
                    @endif
                    <div class="bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Penempatan PKL</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $dudi->penempatan_count ?? $dudi->penempatan()->count() }}</p>
                    </div>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('admin.dudi.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Kembali
                </a>
                @unless($dudi->trashed())
                    @can('update', $dudi)
                        <a href="{{ route('admin.dudi.edit', $dudi->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-amber-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487a2.25 2.25 0 113.182 3.182L7.5 20.25 3 21.75l1.5-4.5L16.862 4.487z" />
                            </svg>
                            Edit DUDI
                        </a>
                    @endcan
                    @can('delete', $dudi)
                        <form method="POST" action="{{ route('admin.dudi.destroy', $dudi->id) }}" onsubmit="return confirm('Hapus DUDI {{ $dudi->nama_perusahaan }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0115.916 21.75H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Hapus DUDI
                            </button>
                        </form>
                    @endcan
                @else
                    @can('restore', $dudi)
                        <form method="POST" action="{{ route('admin.dudi.restore', $dudi->id) }}" onsubmit="return confirm('Pulihkan DUDI {{ $dudi->nama_perusahaan }}?')">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15l3-3m0 0l3-3m-3 3l-3-3m3 3l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pulihkan DUDI
                            </button>
                        </form>
                    @endcan
                    @can('forceDelete', $dudi)
                        <form method="POST" action="{{ route('admin.dudi.force-delete', $dudi->id) }}" onsubmit="return confirm('Hapus permanen {{ $dudi->nama_perusahaan }}? Tindakan ini tidak dapat dibatalkan!')">
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
