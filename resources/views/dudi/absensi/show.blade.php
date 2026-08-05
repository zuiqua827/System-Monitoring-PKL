@extends('layouts.app')

@section('title', 'Detail Absensi Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Data Absensi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Absensi: {{ $absensi->penempatanPKL?->siswa?->nama }}</h1>
                <p class="mt-2 text-sm text-slate-500">Tanggal: {{ \Carbon\Carbon::parse($absensi->tanggal)->translatedFormat('l, d F Y') }}</p>
            </div>
            <a href="{{ route('dudi.absensi.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Check In Card --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Data Check In</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Jam Masuk</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->jam_masuk ? \Carbon\Carbon::parse($absensi->jam_masuk)->format('H:i') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Lokasi Check In</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $absensi->lokasi_masuk ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-slate-500">Foto Check In</dt>
                            <dd class="mt-2">
                                @if($absensi->foto_masuk)
                                    <img src="{{ Storage::url($absensi->foto_masuk) }}" alt="Foto Check In" class="rounded-xl border border-slate-200 w-full max-w-sm object-cover">
                                @else
                                    <div class="flex h-32 w-full max-w-sm items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50">
                                        <p class="text-sm text-slate-500">Tidak ada foto</p>
                                    </div>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Check Out Card --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Data Check Out</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Jam Keluar</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->jam_keluar ? \Carbon\Carbon::parse($absensi->jam_keluar)->format('H:i') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Lokasi Check Out</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $absensi->lokasi_pulang ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-slate-500">Foto Check Out</dt>
                            <dd class="mt-2">
                                @if($absensi->foto_pulang)
                                    <img src="{{ Storage::url($absensi->foto_pulang) }}" alt="Foto Check Out" class="rounded-xl border border-slate-200 w-full max-w-sm object-cover">
                                @else
                                    <div class="flex h-32 w-full max-w-sm items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50">
                                        <p class="text-sm text-slate-500">Tidak ada foto</p>
                                    </div>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
