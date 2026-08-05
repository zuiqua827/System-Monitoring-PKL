@extends('layouts.app')

@section('title', 'Detail Penilaian Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
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
                    <form action="{{ route('dudi.penilaian.finalize', $penilaian->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi penilaian ini? Setelah difinalisasi, penilaian tidak dapat diubah lagi.');" class="inline-block">
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
                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Rincian Nilai</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <dt class="text-sm font-medium text-slate-500">Disiplin Waktu</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_disiplin ?? '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <dt class="text-sm font-medium text-slate-500">Kehadiran</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_kehadiran ?? '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <dt class="text-sm font-medium text-slate-500">Tanggung Jawab</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_tanggung_jawab ?? '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <dt class="text-sm font-medium text-slate-500">Komunikasi</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_komunikasi ?? '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <dt class="text-sm font-medium text-slate-500">Kerjasama Tim</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_kerjasama ?? '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                <dt class="text-sm font-medium text-slate-500">Inisiatif</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_inisiatif ?? '-' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4 border border-slate-100 sm:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Kemampuan Teknis</dt>
                                <dd class="mt-2 text-2xl font-bold text-slate-900">{{ $penilaian->nilai_teknis ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                @if($penilaian->catatan || $penilaian->catatan_guru)
                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Catatan Evaluasi</h3>
                    </div>
                    <div class="px-6 py-5 space-y-6">
                        @if($penilaian->catatan)
                            <div>
                                <h4 class="text-sm font-medium text-slate-500 mb-2">Catatan Umum</h4>
                                <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $penilaian->catatan }}</p>
                                </div>
                            </div>
                        @endif
                        @if($penilaian->catatan_guru)
                            <div>
                                <h4 class="text-sm font-medium text-slate-500 mb-2">Catatan Guru Pembimbing</h4>
                                <div class="rounded-xl bg-blue-50 p-4 border border-blue-100">
                                    <p class="text-sm text-blue-900 whitespace-pre-wrap">{{ $penilaian->catatan_guru }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-blue-200 bg-blue-50 shadow-card-sm overflow-hidden">
                    <div class="border-b border-blue-100 px-6 py-5">
                        <h3 class="text-base font-bold text-blue-900">Hasil Akhir</h3>
                    </div>
                    <div class="p-6 text-center">
                        <div class="mb-4">
                            <p class="text-sm font-medium text-blue-600">Nilai Rata-rata</p>
                            <p class="text-5xl font-black text-blue-900 mt-2">{{ $penilaian->nilai_akhir ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-blue-600 mb-2">Predikat</p>
                            @if($penilaian->predikat === 'A')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-4 py-2 text-sm font-bold text-emerald-800">A (Sangat Baik)</span>
                            @elseif($penilaian->predikat === 'B')
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-800">B (Baik)</span>
                            @elseif($penilaian->predikat === 'C')
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-4 py-2 text-sm font-bold text-amber-800">C (Cukup)</span>
                            @elseif($penilaian->predikat === 'D' || $penilaian->predikat === 'E')
                                <span class="inline-flex items-center rounded-full bg-red-100 px-4 py-2 text-sm font-bold text-red-800">{{ $penilaian->predikat }} (Kurang)</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-bold text-slate-800">Belum ada predikat</span>
                            @endif
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
                                <dt class="text-sm font-medium text-slate-500">Dinilai Oleh (Guru Pembimbing)</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $penilaian->penempatanPKL?->guru?->nama }}</dd>
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
