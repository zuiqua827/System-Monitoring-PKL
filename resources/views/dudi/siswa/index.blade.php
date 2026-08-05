@extends('layouts.app')

@section('title', 'Data Siswa PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">DUDI</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Data Siswa PKL</h1>
                <p class="mt-2 text-sm text-slate-500">Daftar siswa yang melaksanakan PKL di perusahaan Anda</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm">
            <form method="GET" action="{{ route('dudi.siswa.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center ps-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" name="search" placeholder="Cari nama siswa atau NIS..." value="{{ request('search') }}"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-10 pe-4 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('dudi.siswa.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nama Siswa</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">NIS</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Kelas / Jurusan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Guru Pembimbing</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Periode PKL</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($siswaList as $index => $penempatan)
                            <tr class="transition hover:bg-slate-50">
<td class="px-4 py-3.5 text-sm text-slate-500">{{ ($siswaList->firstItem() ?? 0) + $index }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-900">
                                    {{ $penempatan->siswa?->nama ?? '-' }}
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $penempatan->siswa?->nis ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">
                                    {{ $penempatan->siswa?->kelas?->nama ?? '-' }}<br>
                                    <span class="text-xs text-slate-400">{{ $penempatan->siswa?->kelas?->jurusan?->nama ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $penempatan->guru?->nama ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $penempatan->periodePKL?->nama ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    @if($penempatan->status === 'aktif')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Aktif</span>
                                    @elseif($penempatan->status === 'selesai')
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">Selesai</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-800">{{ ucfirst($penempatan->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('dudi.siswa.show', $penempatan->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada siswa PKL yang ditempatkan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($siswaList->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $siswaList->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

