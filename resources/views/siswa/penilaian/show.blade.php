@extends('layouts.app')

@section('title', 'Detail Penilaian')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penilaian</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Hasil Penilaian PKL</h1>
            </div>
            <a href="{{ route('siswa.penilaian.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
        </div>

        {{-- Informasi Penilaian --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Informasi Penilaian PKL</h3>
                        <p class="mt-1 text-sm text-slate-500">Detail hasil penilaian PKL Anda</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold
                        @if($penilaian->status === 'final') bg-emerald-100 text-emerald-700
                        @else bg-amber-100 text-amber-700 @endif">
                        <span class="h-1.5 w-1.5 rounded-full {{ $penilaian->status === 'final' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        {{ $penilaian->status === 'final' ? 'Final' : 'Draft' }}
                    </span>
                </div>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Periode PKL</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->periodePKL?->nama ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                    <p class="mt-1">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold
                            @if($penilaian->status === 'final') bg-emerald-100 text-emerald-700
                            @else bg-amber-100 text-amber-700 @endif">
                            {{ $penilaian->status === 'final' ? 'Final' : 'Draft' }}
                        </span>
                    </p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Guru Pembimbing</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Perusahaan (DUDI)</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dinilai Oleh</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $penilaian->dinilaiOleh?->name ?? '-' }}</p>
                </div>
                <div class="bg-white px-6 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Penilaian</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ $penilaian->tanggal_penilaian ? $penilaian->tanggal_penilaian->format('d/m/Y') : '-' }}</p>
                </div>
            </div>
        </div>

        {{-- Detail Nilai --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Detail Nilai</h3>
            </div>
            <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                @php
                    $nilaiFields = [
                        'nilai_disiplin' => 'Disiplin',
                        'nilai_kehadiran' => 'Kehadiran',
                        'nilai_tanggung_jawab' => 'Tanggung Jawab',
                        'nilai_komunikasi' => 'Komunikasi',
                        'nilai_kerjasama' => 'Kerjasama',
                        'nilai_inisiatif' => 'Inisiatif',
                        'nilai_teknis' => 'Teknis',
                    ];
                @endphp

                @foreach($nilaiFields as $field => $label)
                <div class="flex items-center justify-between bg-white px-6 py-4">
                    <span class="text-sm font-medium text-slate-600">{{ $label }}</span>
                    <span class="text-sm font-bold text-slate-900">{{ $penilaian->$field ?? '-' }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Ringkasan Nilai --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Nilai Akhir</p>
                <p class="mt-2 text-3xl font-bold text-blue-700">{{ $penilaian->nilai_akhir ?? '-' }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Predikat</p>
                <p class="mt-2 text-3xl font-bold
                    @if($penilaian->predikat === 'A') text-emerald-700
                    @elseif($penilaian->predikat === 'B') text-blue-700
                    @elseif($penilaian->predikat === 'C') text-amber-700
                    @elseif($penilaian->predikat === 'D') text-orange-700
                    @else text-red-700
                    @endif">
                    {{ $penilaian->predikat ?? '-' }}
                </p>
            </div>
        </div>

        {{-- Catatan Guru --}}
        @if($penilaian->catatan_guru)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm p-6">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Catatan Guru</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $penilaian->catatan_guru }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
