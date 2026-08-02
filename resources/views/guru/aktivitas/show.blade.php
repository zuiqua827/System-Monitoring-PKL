@php
    use App\Enums\AktivitasStatus;
@endphp

@extends('layouts.app')

@section('title', 'Detail Aktivitas')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-4xl space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">Aktivitas</p>
                <h1 class="page-title mt-1">Detail Aktivitas</h1>
            </div>
            <a href="{{ route('guru.aktivitas.index') }}" class="btn-secondary self-start sm:self-auto">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Kembali
            </a>
        </div>

        {{-- Informasi Aktivitas --}}
        <div class="card p-6">
            <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                <h3 class="section-heading">Informasi Aktivitas</h3>
                @php
                    $statusClass = match($aktivitas->status) {
                        'draft' => 'badge-slate',
                        'menunggu_validasi' => 'badge-amber',
                        'disetujui' => 'badge-green',
                        'ditolak' => 'badge-red',
                        default => 'badge-slate',
                    };
                @endphp
                <span class="{{ $statusClass }}">
                    {{ AktivitasStatus::tryFrom($aktivitas->status)?->label() ?? $aktivitas->status }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <p class="input-label">Siswa</p>
                    <p class="text-sm font-semibold text-slate-800">{{ $aktivitas->penempatanPKL?->siswa?->nama ?? '-' }}</p>
                    @if($aktivitas->penempatanPKL?->siswa)
                        <p class="text-xs text-slate-500">NIS: {{ $aktivitas->penempatanPKL->siswa->nis }}</p>
                    @endif
                </div>
                <div>
                    <p class="input-label">Guru Pembimbing</p>
                    <p class="text-sm font-medium text-slate-800">{{ $aktivitas->penempatanPKL?->guru?->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Perusahaan (DUDI)</p>
                    <p class="text-sm font-medium text-slate-800">{{ $aktivitas->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Periode PKL</p>
                    <p class="text-sm font-medium text-slate-800">{{ $aktivitas->penempatanPKL?->periodePKL?->nama ?? '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Tanggal</p>
                    <p class="text-sm font-medium text-slate-800">{{ $aktivitas->tanggal ? $aktivitas->tanggal->format('d/m/Y') : '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Jam</p>
                    <p class="text-sm font-medium text-slate-800">{{ $aktivitas->jam_mulai ?? '-' }} - {{ $aktivitas->jam_selesai ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                <div>
                    <p class="input-label">Judul Aktivitas</p>
                    <p class="text-sm font-medium text-slate-800">{{ $aktivitas->judul }}</p>
                </div>
                <div>
                    <p class="input-label">Deskripsi</p>
                    <p class="text-sm text-slate-700">{{ $aktivitas->deskripsi ?: '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Hasil</p>
                    <p class="text-sm text-slate-700">{{ $aktivitas->hasil ?: '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Kendala</p>
                    <p class="text-sm text-slate-700">{{ $aktivitas->kendala ?: '-' }}</p>
                </div>
                <div>
                    <p class="input-label">Solusi</p>
                    <p class="text-sm text-slate-700">{{ $aktivitas->solusi ?: '-' }}</p>
                </div>

                @if($aktivitas->foto_kegiatan)
                    <div>
                        <p class="input-label mb-2">Foto Kegiatan</p>
                        <img src="{{ asset('storage/' . $aktivitas->foto_kegiatan) }}" alt="Foto Kegiatan" class="max-w-xs rounded-xl shadow-sm ring-1 ring-slate-200">
                    </div>
                @endif

                @if($aktivitas->catatan_guru)
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                            <div>
                                <p class="text-sm font-semibold text-blue-800">Catatan Guru:</p>
                                <p class="mt-1 text-sm text-blue-700">{{ $aktivitas->catatan_guru }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if($aktivitas->validatedBy)
                    <div class="text-xs text-slate-400">
                        @if($aktivitas->validated_at)
                            <p>Divalidasi oleh: <span class="font-medium text-slate-600">{{ $aktivitas->validatedBy->name }}</span> pada {{ $aktivitas->validated_at->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Form Validasi (hanya jika status menunggu_validasi) --}}
        @if($aktivitas->status === 'menunggu_validasi')
            <div id="validasi" class="card p-6">
                <h3 class="section-heading mb-4">Validasi Aktivitas</h3>

                <form method="POST" action="{{ route('guru.aktivitas.validate', $aktivitas->id) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="input-label">Status Validasi <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 transition hover:bg-slate-50 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                                <input type="radio" name="status" value="disetujui" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500" {{ old('status') === 'disetujui' ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-700">Disetujui</span>
                            </label>
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 transition hover:bg-slate-50 has-[:checked]:border-red-400 has-[:checked]:bg-red-50">
                                <input type="radio" name="status" value="ditolak" class="h-4 w-4 text-red-600 focus:ring-red-500" {{ old('status') === 'ditolak' ? 'checked' : '' }}>
                                <span class="text-sm font-medium text-slate-700">Ditolak</span>
                            </label>
                        </div>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="catatan_guru" class="input-label">Catatan Guru</label>
                        <textarea id="catatan_guru" name="catatan_guru" rows="3" class="input" placeholder="Tambahkan catatan untuk siswa...">{{ old('catatan_guru') }}</textarea>
                        @error('catatan_guru')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-200 pt-4">
                        <button type="submit" class="btn-primary">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Simpan Validasi
                        </button>
                        <a href="{{ route('guru.aktivitas.index') }}" class="btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
