@extends('layouts.app')

@section('title', 'Lokasi Absensi Siswa Bimbingan')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Guru Pembimbing</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengaturan Lokasi Absensi WFO / WFH</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola mode kerja dan validasi radius lokasi siswa bimbingan Anda</p>
            </div>
            <a href="{{ route('guru.absensi.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-card-sm hover:bg-slate-50">
                Kembali ke Absensi Siswa
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Siswa</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Perusahaan (DUDI)</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status PKL</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Mode Kerja</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Validasi Lokasi</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($penempatanPkls as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3.5 text-sm font-semibold text-slate-900">
                                    {{ $item->siswa?->nama ?? '-' }}
                                    <div class="text-xs font-normal text-slate-500">{{ $item->siswa?->kelas?->nama ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-sm text-slate-700">{{ $item->dudi?->nama_perusahaan ?? '-' }}</td>
                                <td class="px-5 py-3.5 text-xs font-bold uppercase text-slate-700">{{ $item->status }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $item->getEffectiveModeKerja() === 'WFH' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $item->getEffectiveModeKerja() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ ($item->validasi_lokasi ?? true) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ ($item->validasi_lokasi ?? true) ? 'Geofence Aktif' : 'Geofence Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <a href="{{ route('guru.penempatan-pkl.location.edit', $item) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                        Atur Lokasi WFO/WFH
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">
                                    Belum ada penempatan siswa bimbingan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($penempatanPkls->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $penempatanPkls->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
