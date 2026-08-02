@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $penilaianList
     * @var \App\Models\PenempatanPKL|null $penempatanAktif
     */
@endphp

@extends('layouts.app')

@section('title', 'Penilaian PKL Saya')

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
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Siswa</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Penilaian PKL Saya</h1>
                <p class="mt-2 text-sm text-slate-500">Lihat hasil penilaian PKL Anda</p>
            </div>
        </div>

        {{-- Status Penempatan --}}
        @if ($penempatanAktif)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 shadow-card-sm">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-4.55 6-10a6 6 0 0 0-12 0c0 5.45 6 10 6 10Z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-blue-900">Penempatan Aktif: {{ $penempatanAktif->dudi?->nama_perusahaan ?? '-' }}</p>
                        <p class="text-xs text-blue-700">{{ $penempatanAktif->periodePKL?->nama ?? '-' }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-card-sm">
                <p class="text-sm font-medium text-amber-800">Anda tidak memiliki penempatan PKL yang aktif.</p>
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Guru Pembimbing</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Perusahaan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nilai Akhir</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Predikat</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($penilaianList as $index => $penilaian)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $penilaianList->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-900">{{ $penilaian->penempatanPKL?->guru?->nama ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $penilaian->penempatanPKL?->dudi?->nama_perusahaan ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm font-bold text-slate-900">{{ $penilaian->nilai_akhir ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    @if($penilaian->predikat)
                                        @php
                                            $predikatColors = [
                                                'A' => 'bg-emerald-100 text-emerald-800',
                                                'B' => 'bg-blue-100 text-blue-800',
                                                'C' => 'bg-amber-100 text-amber-800',
                                                'D' => 'bg-orange-100 text-orange-800',
                                                'E' => 'bg-red-100 text-red-800',
                                            ];
                                            $colorClass = $predikatColors[$penilaian->predikat] ?? 'bg-slate-100 text-slate-800';
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $colorClass }}">
                                            {{ $penilaian->predikat }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($penilaian->status === 'final')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Final</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Draft</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('siswa.penilaian.show', $penilaian->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 4.5h10A2.5 2.5 0 0 1 19.5 7v13l-3.75-2-3.75 2-3.75-2-3.75 2V7A2.5 2.5 0 0 1 7 4.5Z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada data penilaian.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($penilaianList->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $penilaianList->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
