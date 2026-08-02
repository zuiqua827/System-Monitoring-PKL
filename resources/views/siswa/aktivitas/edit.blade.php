@php
    /**
     * @var \App\Models\Aktivitas $aktivitas
     */
@endphp

@extends('layouts.app')

@section('title', 'Edit Aktivitas')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl">
        <div class="mb-8">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Aktivitas</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Edit Aktivitas Harian</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui aktivitas harian PKL Anda</p>
        </div>

        <form method="POST" action="{{ route('siswa.aktivitas.update', $aktivitas->id) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="text-base font-bold text-slate-900">Data Aktivitas</h3>
                    <p class="mt-1 text-sm text-slate-500">Perbarui detail aktivitas yang dilakukan</p>
                </div>
                <div class="grid gap-px overflow-hidden rounded-b-2xl bg-slate-100 sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <label for="tanggal" class="block text-sm font-semibold text-slate-700">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal" name="tanggal" value="{{ old('tanggal', $aktivitas->tanggal?->format('Y-m-d')) }}" required
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('tanggal')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5">
                        <label for="judul" class="block text-sm font-semibold text-slate-700">Judul Aktivitas <span class="text-red-500">*</span></label>
                        <input type="text" id="judul" name="judul" value="{{ old('judul', $aktivitas->judul) }}" required maxlength="255"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('judul')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5">
                        <label for="jam_mulai" class="block text-sm font-semibold text-slate-700">Jam Mulai</label>
                        <input type="time" id="jam_mulai" name="jam_mulai" value="{{ old('jam_mulai', $aktivitas->jam_mulai) }}"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('jam_mulai')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5">
                        <label for="jam_selesai" class="block text-sm font-semibold text-slate-700">Jam Selesai</label>
                        <input type="time" id="jam_selesai" name="jam_selesai" value="{{ old('jam_selesai', $aktivitas->jam_selesai) }}"
                               class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        @error('jam_selesai')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="deskripsi" class="block text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('deskripsi', $aktivitas->deskripsi) }}</textarea>
                        @error('deskripsi')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="hasil" class="block text-sm font-semibold text-slate-700">Hasil</label>
                        <textarea id="hasil" name="hasil" rows="2"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('hasil', $aktivitas->hasil) }}</textarea>
                        @error('hasil')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="kendala" class="block text-sm font-semibold text-slate-700">Kendala</label>
                        <textarea id="kendala" name="kendala" rows="2"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('kendala', $aktivitas->kendala) }}</textarea>
                        @error('kendala')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="solusi" class="block text-sm font-semibold text-slate-700">Solusi</label>
                        <textarea id="solusi" name="solusi" rows="2"
                                  class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">{{ old('solusi', $aktivitas->solusi) }}</textarea>
                        @error('solusi')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Foto Saat Ini --}}
                    <div class="bg-white p-5 sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700">Foto Kegiatan Saat Ini</label>
                        @if($aktivitas->foto_kegiatan)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $aktivitas->foto_kegiatan) }}" alt="Foto Kegiatan" class="h-32 w-auto rounded-xl border border-slate-200 shadow-sm">
                            </div>
                        @else
                            <p class="mt-2 text-sm text-slate-500">Belum ada foto.</p>
                        @endif
                    </div>

                    <div class="bg-white p-5 sm:col-span-2">
                        <label for="foto_kegiatan" class="block text-sm font-semibold text-slate-700">Ganti Foto Kegiatan</label>
                        <div class="mt-1.5 flex items-center gap-3">
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                Pilih Foto
                                <input type="file" id="foto_kegiatan" name="foto_kegiatan" accept="image/jpeg,image/jpg,image/png" class="hidden">
                            </label>
                            <span id="file-name" class="text-sm text-slate-500">Biarkan kosong jika tidak ingin mengubah foto</span>
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Format: JPEG, JPG, PNG. Maksimal 2 MB.</p>
                        @error('foto_kegiatan')
                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Tombol --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('siswa.aktivitas.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm transition hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-card-sm transition hover:bg-blue-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('foto_kegiatan')?.addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'Biarkan kosong jika tidak ingin mengubah foto';
    document.getElementById('file-name').textContent = fileName;
});
</script>
@endpush
@endsection
