@extends('layouts.app')

@section('title', 'Detail Siswa PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Data Siswa PKL</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Siswa: {{ $penempatan->siswa?->nama }}</h1>
            </div>
            <a href="{{ route('dudi.siswa.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Informasi Siswa</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Nama Lengkap</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatan->siswa?->nama }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">NIS / NISN</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->siswa?->nis }} / {{ $penempatan->siswa?->nisn }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Kelas</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->siswa?->kelas?->nama }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Jurusan</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->siswa?->kelas?->jurusan?->nama }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Alamat Siswa</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->siswa?->alamat ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Telepon Siswa</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->siswa?->telepon ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Informasi Pembimbing Sekolah</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Nama Guru</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatan->guru?->nama }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">NIP</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->guru?->nip ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Telepon Guru</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $penempatan->guru?->telepon ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Penempatan PKL</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="space-y-6">
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Periode</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $penempatan->periodePKL?->nama }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Tanggal Pelaksanaan</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($penempatan->tanggal_mulai)->format('d M Y') }} - 
                                    {{ \Carbon\Carbon::parse($penempatan->tanggal_selesai)->format('d M Y') }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Status</dt>
                                <dd class="mt-1">
                                    @if($penempatan->status === 'aktif')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Aktif</span>
                                    @elseif($penempatan->status === 'selesai')
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">Selesai</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800">{{ ucfirst($penempatan->status) }}</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
