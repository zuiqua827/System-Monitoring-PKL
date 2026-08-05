@extends('layouts.app')

@section('title', 'Detail Penilaian')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penilaian</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Penilaian</h1>
            <p class="mt-2 text-sm text-slate-500">Informasi lengkap penilaian PKL siswa</p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Data Penilaian</h2>
                        <p class="mt-1 text-sm text-slate-500">Detail nilai aspek PKL</p>
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
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <div class="flex items-center gap-4 rounded-xl bg-slate-50 p-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                            <p class="text-xs text-slate-500">{{ $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">Guru: {{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</p>
                        </div>
                        <div class="ml-auto text-right">
                            <p class="text-2xl font-bold text-slate-900">{{ $penilaian->nilai_akhir ?? '-' }}</p>
                            <p class="text-xs font-semibold text-slate-500">Nilai Akhir</p>
                        </div>
                    </div>
                </div>

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

                <div class="flex items-center justify-between bg-white px-6 py-4">
                    <span class="text-sm font-medium text-slate-600">Predikat</span>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold
                        @if($penilaian->predikat === 'A') bg-emerald-100 text-emerald-700
                        @elseif($penilaian->predikat === 'B') bg-blue-100 text-blue-700
                        @elseif($penilaian->predikat === 'C') bg-amber-100 text-amber-700
                        @elseif($penilaian->predikat === 'D') bg-orange-100 text-orange-700
                        @else bg-red-100 text-red-700 @endif">
                        {{ $penilaian->predikat ?? '-' }}
                    </span>
                </div>

                @if($penilaian->catatan_guru)
                <div class="bg-white px-6 py-4 sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Catatan Guru</p>
                    <p class="mt-1.5 text-sm text-slate-700">{{ $penilaian->catatan_guru }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('guru.penilaian.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Kembali
            </a>
        </div>
    </div>
</div>
@endsection
