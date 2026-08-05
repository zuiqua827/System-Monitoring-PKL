@extends('layouts.app')

@section('title', 'Aktivitas Siswa PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">DUDI</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Jurnal Aktivitas Siswa</h1>
                <p class="mt-2 text-sm text-slate-500">Evaluasi dan validasi jurnal harian siswa PKL</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm">
            <form method="GET" action="{{ route('dudi.aktivitas.index') }}" class="grid gap-4 sm:grid-cols-4 sm:items-center">
                <div class="sm:col-span-2">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center ps-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" name="search" placeholder="Cari nama siswa, NIS, atau judul..." value="{{ request('search') }}"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 ps-10 pe-4 text-sm text-slate-900 shadow-sm placeholder-slate-400 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
                <div>
                    <select name="status" class="block w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="menunggu_validasi" {{ request('status') === 'menunggu_validasi' ? 'selected' : '' }}>Menunggu Validasi</option>
                        <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex flex-1 justify-center items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Filter</button>
                    @if(request()->anyFilled(['search', 'tanggal', 'status']))
                        <a href="{{ route('dudi.aktivitas.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Reset</a>
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
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nama Siswa</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Judul Kegiatan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($aktivitasList as $index => $aktivitas)
                            <tr class="transition hover:bg-slate-50">
<td class="px-4 py-3.5 text-sm text-slate-500">{{ ($aktivitasList->firstItem() ?? 0) + $index }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-900">{{ \Carbon\Carbon::parse($aktivitas->tanggal)->translatedFormat('d M Y') }}</td>
                                <td class="px-4 py-3.5 text-sm font-medium text-slate-900">{{ $aktivitas->penempatanPKL?->siswa?->nama ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-900">{{ Str::limit($aktivitas->judul, 40) }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">
                                    {{ \Carbon\Carbon::parse($aktivitas->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($aktivitas->jam_selesai)->format('H:i') }}
                                </td>
                                <td class="px-4 py-3.5">
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
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold capitalize {{ $color }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('dudi.aktivitas.show', $aktivitas->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada data aktivitas.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($aktivitasList->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $aktivitasList->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

