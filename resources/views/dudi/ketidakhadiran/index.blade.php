@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $pengajuans
     */
@endphp

@extends('layouts.app')

@section('title', 'Daftar Pengajuan Ketidakhadiran')

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
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">DUDI</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengajuan Ketidakhadiran</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola pengajuan izin dan sakit dari siswa PKL Anda</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm">
            <form method="GET" action="{{ route('dudi.ketidakhadiran.index') }}" class="flex flex-wrap items-center gap-3">
                <div>
                    <select name="status" class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-4 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">Semua Status</option>
                        <option value="menunggu" {{ request('status') === 'menunggu' ? 'selected' : '' }}>Menunggu</option>
                        <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Filter</button>
                @if(request('status'))
                    <a href="{{ route('dudi.ketidakhadiran.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Reset</a>
                @endif
            </form>
        </div>

        {{-- Tabel Pengajuan --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Siswa</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jenis</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pengajuans as $index => $p)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $pengajuans->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-slate-900">{{ $p->penempatanPKL->siswa->nama }}</div>
                                    <div class="text-xs text-slate-500">{{ $p->penempatanPKL->siswa->nis }}</div>
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-800">{{ $p->tanggal->format('d/m/Y') }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600 capitalize">
                                    @if($p->jenis === 'izin')
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Izin</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10">Sakit</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($p->status === 'menunggu')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Menunggu</span>
                                    @elseif($p->status === 'disetujui')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Disetujui</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Ditolak</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <a href="{{ route('dudi.ketidakhadiran.show', $p->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                        {{ $p->status === 'menunggu' ? 'Proses' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4M5.5 9.5h13M7 20h10a2.5 2.5 0 0 0 2.5-2.5v-10A2.5 2.5 0 0 0 17 5H7a2.5 2.5 0 0 0-2.5 2.5v10A2.5 2.5 0 0 0 7 20Z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada pengajuan ketidakhadiran.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pengajuans->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $pengajuans->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
