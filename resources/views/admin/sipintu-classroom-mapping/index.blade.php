@extends('layouts.app')

@section('title', 'Pemetaan Kelas SiPintu')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Integrasi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pemetaan Kelas SiPintu</h1>
                <p class="mt-2 text-sm text-slate-500">Petakan classroom_id SiPintu ke kelas lokal secara 1-ke-1. Pemetaan dipakai langsung oleh Preview dan Sinkronisasi.</p>
            </div>

            @if ($connected)
                <div class="flex flex-wrap items-center gap-2.5">
                    @if (($summary['auto_matchable'] ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.sipintu-classroom-mapping.auto-map') }}"
                              data-confirm="Petakan otomatis {{ $summary['auto_matchable'] }} kelas yang nama, tingkat, dan jurusannya cocok persis tanpa konflik?"
                              data-confirm-title="Auto-Map Kelas Cocok Eksak"
                              data-confirm-type="info"
                              data-confirm-btn="Ya, Petakan Otomatis">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                                </svg>
                                Auto-Map Cocok Eksak ({{ $summary['auto_matchable'] }})
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.sipintu-classroom-mapping.apply') }}"
                          data-confirm="Terapkan semua pemetaan valid ke data siswa lokal? Hanya kelas (class_id) yang akan disesuaikan."
                          data-confirm-title="Terapkan Pemetaan Kelas"
                          data-confirm-type="info"
                          data-confirm-btn="Ya, Terapkan">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 11.5 12 5l7.5 6.5M6.5 10.5V20h11v-9.5M10 20v-5h4v5" />
                            </svg>
                            Terapkan Pemetaan
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Summary cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-semibold text-slate-500">Classroom Digunakan Siswa</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($summary['total_remote'] ?? count($classrooms)) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Total {{ number_format($summary['total_students'] ?? 0) }} siswa memiliki classroom</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-card-sm">
                <p class="text-xs font-semibold text-emerald-700">Sudah Dipetakan Valid</p>
                <p class="mt-2 text-2xl font-extrabold text-emerald-900">{{ number_format($summary['mapped'] ?? 0) }}</p>
                <p class="mt-0.5 text-[11px] text-emerald-700">Siap digunakan Preview &amp; Sinkronisasi</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-card-sm">
                <p class="text-xs font-semibold text-amber-700">Belum Dipetakan</p>
                <p class="mt-2 text-2xl font-extrabold text-amber-900">{{ number_format($summary['unmapped'] ?? 0) }}</p>
                <p class="mt-0.5 text-[11px] text-amber-700">Perlu dipilih atau auto-map</p>
            </div>
            <div class="rounded-2xl border {{ ($summary['invalid'] ?? 0) > 0 ? 'border-red-200 bg-red-50/50' : 'border-slate-200 bg-white' }} p-5 shadow-card-sm">
                <p class="text-xs font-semibold {{ ($summary['invalid'] ?? 0) > 0 ? 'text-red-700' : 'text-slate-500' }}">Mapping Tidak Valid / Konflik</p>
                <p class="mt-2 text-2xl font-extrabold {{ ($summary['invalid'] ?? 0) > 0 ? 'text-red-900' : 'text-slate-900' }}">{{ number_format($summary['invalid'] ?? 0) }}</p>
                <p class="mt-0.5 text-[11px] {{ ($summary['invalid'] ?? 0) > 0 ? 'text-red-700' : 'text-slate-500' }}">Kelas nonaktif atau konflik 1-ke-banyak</p>
            </div>
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
            <div class="flex flex-col sm:flex-row gap-4 border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Pemetaan Tersimpan ({{ $mappings->count() }})</h2>
                    <p class="mt-1 text-sm text-slate-500">Daftar classroom_id yang sudah dipetakan ke kelas lokal beserta status validitasnya</p>
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
                    <table class="w-full min-w-[800px] divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Classroom ID</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Kelas Lokal</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jurusan</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Validitas</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($mappings as $mapping)
                                @php
                                    $health = $mappingHealth[$mapping->id] ?? ['is_valid' => $mapping->kelas !== null && !$mapping->kelas->trashed(), 'reason' => null, 'in_remote' => true];
                                @endphp
                                <tr class="transition hover:bg-slate-50 {{ !$health['is_valid'] ? 'bg-red-50/40' : '' }}">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-800">{{ $mapping->classroom_id }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-800">
                                        {{ $mapping->kelas?->nama ?? 'Kelas Terhapus (#'.$mapping->kelas_id.')' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ $mapping->kelas?->jurusan?->nama ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($health['is_valid'])
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Valid</span>
                                            @if (!$health['in_remote'] && $connected)
                                                <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">Tidak dipakai di payload saat ini</span>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800" title="{{ $health['reason'] }}">Tidak Valid</span>
                                            @if (!empty($health['reason']))
                                                <p class="mt-1 text-xs text-red-700">{{ $health['reason'] }}</p>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('admin.sipintu-classroom-mapping.destroy', $mapping->id) }}"
                                              data-confirm="Hapus pemetaan untuk Classroom ID {{ $mapping->classroom_id }}?"
                                              data-confirm-title="Hapus Pemetaan"
                                              data-confirm-type="warning"
                                              data-confirm-btn="Ya, Hapus"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
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
                    <h2 class="text-base font-bold text-slate-900">Classroom SiPintu Digunakan Siswa ({{ count($classrooms) }})</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih kelas lokal untuk setiap classroom_id. Satu kelas lokal hanya dapat dipetakan ke satu classroom_id SiPintu.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Classroom SiPintu</th>
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
                                    $existingHealth = $existing ? ($mappingHealth[$existing->id] ?? ['is_valid' => true, 'reason' => null]) : null;
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-slate-800">
                                        ID: {{ $classroom['classroom_id'] }}
                                        @if (!empty($classroom['classroom_name']))
                                            <span class="ml-1.5 inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $classroom['classroom_name'] }}</span>
                                        @else
                                            <span class="ml-1.5 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Nama tidak tersedia di API</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-slate-600">{{ number_format($classroom['student_count']) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if ($existing && ($existingHealth['is_valid'] ?? false))
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Terpetakan</span>
                                        @elseif ($existing)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800" title="{{ $existingHealth['reason'] ?? '' }}">Tidak Valid</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Belum</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                                        @if ($existing && $existing->kelas)
                                            {{ $existing->kelas->nama }}
                                        @elseif (!empty($classroom['suggested_kelas_nama']))
                                            <span class="text-xs text-emerald-700">Saran eksak: <strong>{{ $classroom['suggested_kelas_nama'] }}</strong></span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        <form method="POST" action="{{ route('admin.sipintu-classroom-mapping.store') }}" class="inline-flex items-center flex-wrap gap-2">
                                            @csrf
                                            <input type="hidden" name="classroom_id" value="{{ $classroom['classroom_id'] }}">
                                            <select name="kelas_id" class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">— Pilih Kelas —</option>
                                                @foreach ($kelasOptions as $kelas)
                                                    <option value="{{ $kelas->id }}" @selected(($existing && $existing->kelas_id === $kelas->id) || (!$existing && ($classroom['suggested_kelas_id'] ?? null) === $kelas->id))>
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
