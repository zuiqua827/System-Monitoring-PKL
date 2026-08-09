@extends('layouts.app')

@section('title', 'Pemetaan Kelas SiPintu')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">{{ session('error') }}</div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Integrasi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pemetaan Kelas SiPintu</h1>
                <p class="mt-2 text-sm text-slate-500">Petakan classroom_id SiPintu ke kelas lokal. Pemetaan dipakai otomatis oleh sinkronisasi berikutnya.</p>
            </div>

            @if ($connected)
                <form method="POST" action="{{ route('admin.sipintu-classroom-mapping.apply') }}" onsubmit="return confirm('Terapkan semua pemetaan ke siswa lokal? Hanya class_id yang diubah.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 11.5 12 5l7.5 6.5M6.5 10.5V20h11v-9.5M10 20v-5h4v5" />
                        </svg>
                        Terapkan Pemetaan
                    </button>
                </form>
            @endif
        </div>

        {{-- Connection warning --}}
        @if (!$connected)
            <div class="flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50/60 p-5">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div class="text-sm text-red-800">
                    <p class="font-semibold">Tidak dapat mengambil data classroom dari SiPintu.</p>
                    <p class="mt-1 text-red-700">Periksa koneksi dan kredensial SiPintu, lalu coba muat ulang halaman. Pemetaan yang sudah tersimpan tetap dapat diterapkan.</p>
                </div>
            </div>
        @endif

        {{-- Existing mappings --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Pemetaan Tersimpan ({{ $mappings->count() }})</h2>
                    <p class="mt-1 text-sm text-slate-500">Daftar classroom_id yang sudah dipetakan ke kelas lokal</p>
                </div>
            </div>

            @if ($mappings->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 6h14M7 6c0 2.5-1 4-3 4.5M17 6c0 2.5 1 4 3 4.5M5 18h14M7 18c0-2.5-1-4-3-4.5M17 18c0-2.5 1-4 3-4.5" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">Belum ada pemetaan.</p>
                    <p class="text-sm text-slate-500">Gunakan tabel di bawah untuk memetakan classroom_id ke kelas lokal.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Classroom ID</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Kelas Lokal</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jurusan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($mappings as $mapping)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-800">{{ $mapping->classroom_id }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-800">{{ $mapping->kelas->nama }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ $mapping->kelas->jurusan?->nama }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Mapping table --}}
        @if ($connected)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="text-base font-bold text-slate-900">Classroom SiPintu ({{ count($classrooms) }})</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih kelas lokal untuk setiap classroom_id. Pemetaan bersifat sekali-simpan dan dapat diubah.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Classroom ID</th>
                                <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah Siswa</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Kelas Lokal</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($classrooms as $classroom)
                                @php
                                    $existing = $mappings->firstWhere('classroom_id', $classroom['classroom_id']);
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-800">{{ $classroom['classroom_id'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($classroom['student_count']) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if ($existing)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Terpetakan</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Belum</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        {{ $existing ? $existing->kelas->nama : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('admin.sipintu-classroom-mapping.store') }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="classroom_id" value="{{ $classroom['classroom_id'] }}">
                                            <select name="kelas_id" class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">— Pilih Kelas —</option>
                                                @foreach ($kelasOptions as $kelas)
                                                    <option value="{{ $kelas->id }}" @selected($existing && $existing->kelas_id === $kelas->id)>
                                                        {{ $kelas->nama }} ({{ $kelas->jurusan?->nama ?? '—' }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                                                Simpan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                            <p class="text-sm font-semibold text-slate-700">Tidak ada classroom yang ditemukan.</p>
                                            <p class="text-sm text-slate-500">Pastikan data siswa SiPintu memiliki classroom_id.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
