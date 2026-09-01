@extends('layouts.app', ['__pageTitle' => 'Pengaturan Operasional'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pengaturan Operasional</h1>
            <p class="mt-1 text-sm text-slate-500">Atur jadwal operasional perusahaan sebagai acuan sistem presensi siswa PKL.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <div class="flex">
                <div class="shrink-0">
                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form action="{{ route('dudi.operasional.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Jam Operasional</h2>
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    
                    {{-- Jam Masuk --}}
                    <div>
                        <label for="jam_masuk" class="block text-sm font-semibold text-slate-700">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="jam_masuk" value="{{ old('jam_masuk', $jamMasuk) }}" required class="mt-2 block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-blue-500 @error('jam_masuk') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                        @error('jam_masuk')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Batas Terlambat --}}
                    <div>
                        <label for="batas_terlambat" class="block text-sm font-semibold text-slate-700">Batas Terlambat</label>
                        <input type="time" name="batas_terlambat" id="batas_terlambat" value="{{ old('batas_terlambat', $batasTerlambat) }}" required class="mt-2 block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-blue-500 @error('batas_terlambat') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                        @error('batas_terlambat')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Batas Sangat Terlambat --}}
                    <div>
                        <label for="batas_sangat_terlambat" class="block text-sm font-semibold text-slate-700">Batas Sangat Terlambat</label>
                        <input type="time" name="batas_sangat_terlambat" id="batas_sangat_terlambat" value="{{ old('batas_sangat_terlambat', $batasSangatTerlambat) }}" required class="mt-2 block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-blue-500 @error('batas_sangat_terlambat') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                        @error('batas_sangat_terlambat')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Jam Pulang --}}
                    <div>
                        <label for="jam_pulang" class="block text-sm font-semibold text-slate-700">Jam Pulang</label>
                        <input type="time" name="jam_pulang" id="jam_pulang" value="{{ old('jam_pulang', $jamPulang) }}" required class="mt-2 block w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm transition focus:border-blue-500 focus:ring-blue-500 @error('jam_pulang') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror">
                        @error('jam_pulang')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <hr class="my-8 border-slate-200">

                <h2 class="text-lg font-bold text-slate-900">Hari Operasional</h2>
                <div class="mt-4">
                    @php
                        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
                        $oldHari = old('hari_operasional', $hariOperasional);
                    @endphp
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        @foreach ($days as $day)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 cursor-pointer transition hover:bg-slate-100">
                                <input type="checkbox" name="hari_operasional[]" value="{{ $day }}" class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-600" @checked(in_array($day, $oldHari))>
                                <span class="text-sm font-semibold text-slate-700 capitalize">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('hari_operasional')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div class="flex justify-end gap-3 rounded-b-2xl bg-slate-50 px-6 py-4">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
