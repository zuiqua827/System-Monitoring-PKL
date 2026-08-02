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
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Absensi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Absensi</h1>
            </div>
            <a href="{{ route('guru.absensi.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
        </div>

        {{-- Informasi Absensi --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Detail Absensi</h3>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Siswa</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $absensi->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                    @if($absensi->penempatanPKL?->siswa)
                        <p class="text-xs text-slate-500">NIS: {{ $absensi->penempatanPKL->siswa->nis }}</p>
                    @endif
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
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perusahaan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
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
                    <a href="{{ asset('storage/' . $absensi->foto_masuk) }}" target="_blank" class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        Lihat Foto
                    </a>
                </div>
                @endif
                @if($absensi->foto_pulang)
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Foto Pulang</p>
                    <a href="{{ asset('storage/' . $absensi->foto_pulang) }}" target="_blank" class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.41a2.25 2.25 0 013.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>
                        Lihat Foto
                    </a>
                </div>
                @endif
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $absensi->keterangan ?: '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Form Validasi --}}
        @can('verify', $absensi)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Validasi Absensi</h3>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('guru.absensi.verify', $absensi->id) }}" class="space-y-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="status" class="block text-sm font-semibold text-slate-700">Ubah Status <span class="text-red-500">*</span></label>
                            <select id="status" name="status" required
                                    class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                                @foreach(AbsensiStatus::cases() as $status)
                                    <option value="{{ $status->value }}" {{ $absensi->status == $status->value ? 'selected' : '' }}>
                                        {{ $status->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="keterangan" class="block text-sm font-semibold text-slate-700">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" rows="3"
                                      class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('keterangan', $absensi->keterangan ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Simpan Validasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection
