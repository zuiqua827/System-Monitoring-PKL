@extends('layouts.app')

@section('title', 'Laporan Aktivitas PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-violet-600">Laporan</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Aktivitas Siswa Bimbingan</h1>
            </div>
            <a href="{{ route('guru.laporan.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kembali</a>
        </div>

        {{-- Summary Cards --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-slate-500 uppercase">Total Siswa</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['total_siswa'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-slate-500 uppercase">Total Aktivitas</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['total_aktivitas'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-amber-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-amber-600 uppercase">Menunggu Validasi</p>
                <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($stats['pending'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-emerald-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Disetujui</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($stats['approved'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-red-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-red-600 uppercase">Ditolak</p>
                <p class="mt-1 text-2xl font-bold text-red-700">{{ number_format($stats['rejected'] ?? 0) }}</p>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
            <form action="{{ route('guru.laporan.aktivitas') }}" method="GET">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div>
                        <label for="periode_id" class="block text-xs font-semibold text-slate-700">Periode</label>
                        <select name="periode_id" id="periode_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Semua Periode</option>
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="jurusan_id" class="block text-xs font-semibold text-slate-700">Jurusan</label>
                        <select name="jurusan_id" id="jurusan_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Semua Jurusan</option>
                            @foreach($jurusans as $j)
                                <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="kelas_id" class="block text-xs font-semibold text-slate-700">Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Semua Kelas</option>
                            @foreach($kelass as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Guru Pembimbing</label>
                        <input type="text" readonly value="{{ $guru->nama }}" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-slate-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending / Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved / Disetujui</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected / Ditolak</option>
                        </select>
                    </div>
                    <div>
                        <label for="tanggal_mulai" class="block text-xs font-semibold text-slate-700">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-xs font-semibold text-slate-700">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="{{ request('tanggal_akhir') }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <a href="{{ route('guru.laporan.aktivitas') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-700">Tampilkan</button>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Siswa</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Penempatan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Aktivitas</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($aktivitas as $index => $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $aktivitas->firstItem() + $index }}
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">{{ $item->penempatanPKL->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">NIS: {{ $item->penempatanPKL->siswa->nis ?? '-' }}</p>
                                    <p class="mt-1 text-xs font-medium text-violet-600">{{ $item->penempatanPKL->siswa->kelas->nama ?? '-' }} - {{ $item->penempatanPKL->siswa->kelas->jurusan->singkatan ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    <p class="font-semibold">{{ $item->penempatanPKL->dudi->nama_perusahaan ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <p class="font-bold text-slate-700">{{ $item->tanggal?->format('d M Y') ?? '-' }}</p>
                                    @if($item->jam_mulai && $item->jam_selesai)
                                        <p class="text-xs">{{ \Carbon\Carbon::parse($item->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->jam_selesai)->format('H:i') }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700 min-w-[300px]">
                                    <p class="font-bold">{{ $item->judul ?? '-' }}</p>
                                    <p class="mt-1 text-xs text-slate-500 break-words">{{ Str::limit($item->deskripsi, 100) }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @php
                                        $statusClass = match(strtolower($item->status)) {
                                            'pending' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'rejected' => 'bg-red-50 text-red-700 ring-red-200',
                                            default => 'bg-slate-50 text-slate-700 ring-slate-200'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">Data laporan aktivitas tidak ditemukan.</h3>
                                    <p class="mt-1 text-sm text-slate-500">Silakan sesuaikan filter pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($aktivitas->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $aktivitas->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
