@extends('layouts.app')

@section('title', 'Detail Penilaian')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Penilaian</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Penilaian</h1>
            <p class="mt-2 text-sm text-slate-500">Informasi lengkap penilaian PKL siswa</p>
        </div>

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Data Siswa</h2>
                        <p class="mt-1 text-sm text-slate-500">Informasi peserta didik dan penempatan</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold
                        @if($penilaian->status === 'final') bg-emerald-100 text-emerald-700
                        @else bg-amber-100 text-amber-700 @endif">
                        <span class="h-1.5 w-1.5 rounded-full {{ $penilaian->status === 'final' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        {{ $penilaian->status === 'final' ? 'Final' : 'Draft' }}
                    </span>
                </div>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 rounded-xl bg-slate-50 p-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 shrink-0">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="grow">
                        <p class="text-base font-bold text-slate-900">{{ $penilaian->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                        <p class="text-xs text-slate-500">{{ $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">Guru: {{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-600">{{ $penilaian->nilai_akhir ?? '-' }}</p>
                        <p class="text-xs font-semibold text-slate-500">Nilai Akhir ({{ $penilaian->predikat ?? '-' }})</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Aspek Penilaian --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-base font-bold text-slate-900">Penilaian Per Aspek</h3>
                <p class="mt-1 text-sm text-slate-500">Rincian nilai, predikat, dan deskripsi khusus per aspek</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-3 text-center w-12">No</th>
                            <th class="px-4 py-3">Aspek Penilaian</th>
                            <th class="px-4 py-3 text-center w-20">Nilai</th>
                            <th class="px-4 py-3 text-center w-24">Predikat</th>
                            <th class="px-4 py-3">Deskripsi Aspek</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @php
                            $aspekList = [
                                ['no' => 1, 'key' => 'kehadiran', 'nama' => 'Kehadiran', 'nilai' => $penilaian->nilai_kehadiran],
                                ['no' => 2, 'key' => 'kerjasama', 'nama' => 'Kerja Sama', 'nilai' => $penilaian->nilai_kerjasama],
                                ['no' => 3, 'key' => 'komunikasi', 'nama' => 'Komunikasi', 'nilai' => $penilaian->nilai_komunikasi],
                                ['no' => 4, 'key' => 'problem_solving', 'nama' => 'Problem Solving', 'nilai' => $penilaian->nilai_problem_solving],
                                ['no' => 5, 'key' => 'teknis', 'nama' => 'Teknis', 'nilai' => $penilaian->nilai_teknis],
                                ['no' => 6, 'key' => 'inisiatif', 'nama' => 'Inisiatif', 'nilai' => $penilaian->nilai_inisiatif],
                            ];
                        @endphp
                        @foreach($aspekList as $asp)
                            @php
                                $pred = \App\Services\PenilaianService::calculatePredikatStatic($asp['nilai']) ?? '-';
                                $desk = \App\Services\PenilaianService::getDeskripsiAspek($asp['key'], $asp['nilai']);
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3.5 text-center text-slate-500 font-medium">{{ $asp['no'] }}</td>
                                <td class="px-4 py-3.5 font-bold text-slate-900">{{ $asp['nama'] }}</td>
                                <td class="px-4 py-3.5 text-center font-bold text-slate-900">{{ $asp['nilai'] ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold
                                        @if($pred === 'A+') bg-emerald-100 text-emerald-800
                                        @elseif($pred === 'A') bg-emerald-50 text-emerald-700 border border-emerald-200
                                        @elseif($pred === 'B') bg-blue-100 text-blue-800
                                        @elseif($pred === 'C') bg-amber-100 text-amber-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ $pred }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600 text-xs leading-relaxed">{{ $desk }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ringkasan Nilai Akhir & Catatan DUDI --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Deskripsi Nilai Akhir</p>
                <p class="mt-1 text-sm text-slate-700 leading-relaxed">{{ \App\Services\PenilaianService::getDeskripsiPredikat($penilaian->predikat) }}</p>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Catatan DUDI</p>
                <p class="mt-1 text-sm text-slate-700">{{ $penilaian->catatan ?: 'Belum ada catatan dari DUDI.' }}</p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('guru.penilaian.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Kembali
            </a>
            @if($penilaian->status === 'final')
                <a href="{{ route('guru.penilaian.print', $penilaian->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak PDF
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
