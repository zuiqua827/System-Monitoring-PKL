@extends('layouts.app')

@section('title', 'Detail Aktivitas Siswa')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">{{ session('error') }}</div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Data Aktivitas</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Detail Jurnal: {{ $aktivitas->judul }}</h1>
                <p class="mt-2 text-sm text-slate-500">Oleh {{ $aktivitas->penempatanPKL?->siswa?->nama }} - {{ \Carbon\Carbon::parse($aktivitas->tanggal)->translatedFormat('l, d F Y') }}</p>
            </div>
            <a href="{{ route('dudi.aktivitas.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50">
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
                        <h3 class="text-base font-bold text-slate-900">Detail Kegiatan</h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Judul Kegiatan</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $aktivitas->judul }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Waktu Mulai</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($aktivitas->jam_mulai)->format('H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500">Waktu Selesai</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($aktivitas->jam_selesai)->format('H:i') }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Deskripsi Kegiatan</dt>
                                <dd class="mt-2 text-sm text-slate-700 whitespace-pre-wrap">{{ $aktivitas->deskripsi }}</dd>
                            </div>
@if($aktivitas->foto_kegiatan)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-slate-500">Lampiran Foto</dt>
                                <dd class="mt-2">
                                    <img src="{{ Storage::url($aktivitas->foto_kegiatan) }}" alt="Foto Kegiatan" class="rounded-xl border border-slate-200 w-full max-w-lg object-cover">
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm overflow-hidden">
                    <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5">
                        <h3 class="text-base font-bold text-slate-900">Validasi Aktivitas</h3>
                    </div>
                    <div class="p-6">
                        <div class="mb-6">
                            <p class="text-sm font-medium text-slate-500">Status Saat Ini</p>
                            <div class="mt-2">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-800',
                                        'menunggu_validasi' => 'bg-amber-100 text-amber-800',
                                        'disetujui' => 'bg-emerald-100 text-emerald-800',
                                        'ditolak' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $statusColors[$aktivitas->status] ?? 'bg-slate-100 text-slate-800';
                                    $statusLabel = str_replace('_', ' ', $aktivitas->status);
                                @endphp
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold capitalize {{ $color }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        @if($aktivitas->status === 'menunggu_validasi')
                            <form action="{{ route('dudi.aktivitas.update', $aktivitas->id) }}" method="POST" class="space-y-4">
                                @csrf
                                @method('PUT')
                                
                                <div>
                                    <label for="status" class="block text-sm font-medium text-slate-700">Tindakan</label>
                                    <select name="status" id="status" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                                        <option value="">Pilih tindakan...</option>
                                        <option value="disetujui">Setujui</option>
                                        <option value="ditolak">Tolak</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="catatan_guru" class="block text-sm font-medium text-slate-700">Catatan / Komentar (Opsional)</label>
                                    <textarea name="catatan_guru" id="catatan_guru" rows="3" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Berikan komentar untuk aktivitas ini..."></textarea>
                                </div>
                                
                                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Simpan Validasi
                                </button>
                            </form>
                        @else
                            <div class="mt-4">
                                <p class="text-sm font-medium text-slate-500">Komentar / Catatan Evaluasi:</p>
                                <div class="mt-2 rounded-xl bg-slate-50 p-4 border border-slate-200">
                                    <p class="text-sm text-slate-700">{{ $aktivitas->catatan_guru ?? 'Tidak ada catatan.' }}</p>
                                </div>
                            </div>
                            @if($aktivitas->validatedBy)
                                <div class="mt-4">
                                    <p class="text-xs text-slate-500">Divalidasi oleh: {{ $aktivitas->validatedBy->name }} ({{ \Carbon\Carbon::parse($aktivitas->validated_at)->format('d M Y H:i') }})</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
