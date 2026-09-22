@extends('layouts.app')

@section('title', 'Detail Penilaian Siswa')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Data Penilaian</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Penilaian: {{ $penilaian->penempatanPKL?->siswa?->nama }}</h1>
                <p class="mt-2 text-sm text-slate-500">Status: {{ ucfirst($penilaian->status) }}</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('dudi.penilaian.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
                
                @if($penilaian->status !== 'final')
                    <a href="{{ route('dudi.penilaian.edit', $penilaian->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Edit Penilaian
                    </a>
                    <form action="{{ route('dudi.penilaian.finalize', $penilaian->id) }}" method="POST" class="inline-block"
                          data-confirm="Apakah Anda yakin ingin memfinalisasi penilaian ini? Setelah difinalisasi, penilaian tidak dapat diubah lagi."
                          data-confirm-title="Finalisasi Penilaian"
                          data-confirm-type="warning"
                          data-confirm-btn="Ya, Finalisasi">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Finalisasi Penilaian
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                {{-- Tabel Aspek Penilaian --}}
                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Rincian Nilai Per Aspek</h3>
                        <p class="mt-1 text-sm text-slate-500">Detail nilai, predikat, dan deskripsi khusus per aspek</p>
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

                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Catatan DUDI</h3>
                    </div>
                    <div class="px-6 py-5">
                        <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                            <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $penilaian->catatan ?: 'Belum ada catatan dari DUDI.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-blue-200 bg-blue-50 shadow-card-sm overflow-hidden">
                    <div class="border-b border-blue-100 px-6 py-5">
                        <h3 class="text-base font-bold text-blue-900">Hasil Akhir</h3>
                    </div>
                    <div class="p-6 text-center">
                        <div class="mb-4">
                            <p class="text-sm font-medium text-blue-600">Nilai Akhir</p>
                            <p class="text-5xl font-black text-blue-900 mt-2">{{ $penilaian->nilai_akhir ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-2">Predikat Nilai Akhir</p>
                            @if($penilaian->predikat)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-4 py-2 text-sm font-bold text-emerald-800">{{ $penilaian->predikat }}</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-800">Belum ada predikat</span>
                            @endif
                        </div>
                        <div class="mt-4 text-left border-t border-blue-100 pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Deskripsi Nilai Akhir</p>
                            <p class="mt-1 text-xs text-blue-900 leading-relaxed">{{ \App\Services\PenilaianService::getDeskripsiPredikat($penilaian->predikat) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Informasi Penilai</h3>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Guru Pembimbing</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Tanggal Penilaian</dt>
                                <dd class="mt-1 text-sm text-slate-900">
                                    {{ $penilaian->tanggal_penilaian ? \Carbon\Carbon::parse($penilaian->tanggal_penilaian)->translatedFormat('d F Y') : '-' }}
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
