@php
    /**
     * @var \App\Models\Aktivitas $aktivitas
     */
@endphp

@extends('layouts.app')

@section('title', 'Detail Aktivitas')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Aktivitas</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Aktivitas Harian</h1>
            </div>
            <a href="{{ route('siswa.aktivitas.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
        </div>

        {{-- Informasi Aktivitas --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Detail Aktivitas Harian</h3>
                        <p class="mt-1 text-sm text-slate-500">Informasi lengkap aktivitas PKL</p>
                    </div>
                    @php
                        $statusClass = match($aktivitas->status) {
                            'draft' => 'bg-slate-100 text-slate-700',
                            'menunggu_validasi' => 'bg-amber-100 text-amber-700',
                            'disetujui' => 'bg-emerald-100 text-emerald-700',
                            'ditolak' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $statusDot = match($aktivitas->status) {
                            'draft' => 'bg-slate-500',
                            'menunggu_validasi' => 'bg-amber-500',
                            'disetujui' => 'bg-emerald-500',
                            'ditolak' => 'bg-red-500',
                            default => 'bg-slate-500',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold {{ $statusClass }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ \App\Enums\AktivitasStatus::tryFrom($aktivitas->status)?->label() ?? $aktivitas->status }}
                    </span>
                </div>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->tanggal ? $aktivitas->tanggal->format('d/m/Y') : '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                    <p class="mt-1">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $statusClass }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                            {{ \App\Enums\AktivitasStatus::tryFrom($aktivitas->status)?->label() ?? $aktivitas->status }}
                        </span>
                    </p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jam Mulai</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $aktivitas->jam_mulai ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jam Selesai</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $aktivitas->jam_selesai ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Judul</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $aktivitas->judul }}</p>
                </div>
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Deskripsi</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->deskripsi ?: '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hasil</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->hasil ?: '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kendala</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->kendala ?: '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Solusi</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $aktivitas->solusi ?: '-' }}</p>
                </div>

                @if($aktivitas->foto_kegiatan)
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Foto Kegiatan</p>
                    <img src="{{ asset('storage/' . $aktivitas->foto_kegiatan) }}" alt="Foto Kegiatan" class="mt-2 h-48 w-auto rounded-xl border border-slate-200 shadow-sm">
                </div>
                @endif

                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dibuat Pada</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $aktivitas->created_at ? $aktivitas->created_at->format('d/m/Y H:i') : '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Diperbarui Pada</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $aktivitas->updated_at ? $aktivitas->updated_at->format('d/m/Y H:i') : '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Catatan Validasi --}}
        @if(in_array($aktivitas->status, ['disetujui', 'ditolak']))
            <div class="rounded-2xl border p-6 {{ $aktivitas->status === 'disetujui' ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $aktivitas->status === 'disetujui' ? 'text-emerald-500' : 'text-red-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        @if($aktivitas->status === 'disetujui')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        @endif
                    </svg>
                    <div>
                        <p class="text-sm font-semibold {{ $aktivitas->status === 'disetujui' ? 'text-emerald-800' : 'text-red-800' }}">
                            {{ $aktivitas->status === 'disetujui' ? 'Aktivitas Disetujui' : 'Aktivitas Ditolak' }}
                        </p>
                        <p class="mt-1 text-sm {{ $aktivitas->status === 'disetujui' ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $aktivitas->catatan_guru ?: 'Tidak ada catatan.' }}
                        </p>
                        @if($aktivitas->validatedBy)
                            <p class="mt-1 text-xs {{ $aktivitas->status === 'disetujui' ? 'text-emerald-600' : 'text-red-600' }}">
                                Oleh: {{ $aktivitas->validatedBy->name }} pada {{ $aktivitas->validated_at ? $aktivitas->validated_at->format('d/m/Y H:i') : '-' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
