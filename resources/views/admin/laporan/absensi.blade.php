@extends('layouts.app')

@section('title', 'Laporan Absensi PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-600">Laporan</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Absensi Siswa PKL</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.laporan.absensi.export.excel', request()->query()) }}" class="flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('admin.laporan.absensi.export.pdf', request()->query()) }}" class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700" target="_blank">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export PDF
                </a>
                <a href="{{ route('admin.laporan.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kembali</a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-slate-500 uppercase">Siswa</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['total_siswa'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-slate-500 uppercase">Total Absensi</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($stats['total_absensi'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-emerald-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Hadir</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ number_format($stats['hadir'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-amber-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-amber-600 uppercase">Terlambat</p>
                <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($stats['terlambat'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-blue-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-blue-600 uppercase">Izin</p>
                <p class="mt-1 text-2xl font-bold text-blue-700">{{ number_format($stats['izin'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-orange-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-orange-600 uppercase">Sakit</p>
                <p class="mt-1 text-2xl font-bold text-orange-700">{{ number_format($stats['sakit'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-red-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-red-600 uppercase">Alpha</p>
                <p class="mt-1 text-2xl font-bold text-red-700">{{ number_format($stats['alpha'] ?? 0) }}</p>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
            <form action="{{ route('admin.laporan.absensi') }}" method="GET">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div>
                        <label for="periode_id" class="block text-xs font-semibold text-slate-700">Periode</label>
                        <select name="periode_id" id="periode_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Periode</option>
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="jurusan_id" class="block text-xs font-semibold text-slate-700">Jurusan</label>
                        <select name="jurusan_id" id="jurusan_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Jurusan</option>
                            @foreach($jurusans as $j)
                                <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="kelas_id" class="block text-xs font-semibold text-slate-700">Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Kelas</option>
                            @foreach($kelass as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="guru_id" class="block text-xs font-semibold text-slate-700">Guru</label>
                        <select name="guru_id" id="guru_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Guru</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dudi_id" class="block text-xs font-semibold text-slate-700">DUDI</label>
                        <select name="dudi_id" id="dudi_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua DUDI</option>
                            @foreach($dudis as $d)
                                <option value="{{ $d->id }}" {{ request('dudi_id') == $d->id ? 'selected' : '' }}>{{ $d->nama_perusahaan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-slate-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Status</option>
                            <option value="hadir" {{ request('status') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="terlambat" {{ request('status') == 'terlambat' ? 'selected' : '' }}>Terlambat</option>
                            <option value="izin" {{ request('status') == 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="sakit" {{ request('status') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="alpha" {{ request('status') == 'alpha' ? 'selected' : '' }}>Alpha</option>
                        </select>
                    </div>
                    <div>
                        <label for="tanggal_mulai" class="block text-xs font-semibold text-slate-700">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-xs font-semibold text-slate-700">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="{{ request('tanggal_akhir') }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <a href="{{ route('admin.laporan.absensi') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Tampilkan</button>
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($absensis as $index => $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $absensis->firstItem() + $index }}
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">{{ $item->penempatanPKL->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">NIS: {{ $item->penempatanPKL->siswa->nis ?? '-' }}</p>
                                    <p class="mt-1 text-xs font-medium text-emerald-600">{{ $item->penempatanPKL->siswa->kelas->nama ?? '-' }} - {{ $item->penempatanPKL->siswa->kelas->jurusan->singkatan ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    <p class="font-semibold">{{ $item->penempatanPKL->dudi->nama_perusahaan ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $item->penempatanPKL->guru->nama ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <p class="font-bold text-slate-700">{{ $item->tanggal?->format('d M Y') ?? '-' }}</p>
                                    <p class="text-xs">Masuk: {{ $item->jam_masuk?->format('H:i') ?? '-' }}</p>
                                    <p class="text-xs">Keluar: {{ $item->jam_keluar?->format('H:i') ?? '-' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @php
                                        $statusClass = match(strtolower($item->status)) {
                                            'hadir' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'terlambat' => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            'izin' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                            'sakit' => 'bg-orange-50 text-orange-700 ring-orange-200',
                                            'alpha' => 'bg-red-50 text-red-700 ring-red-200',
                                            default => 'bg-slate-50 text-slate-700 ring-slate-200'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $item->keterangan ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">Data laporan absensi tidak ditemukan.</h3>
                                    <p class="mt-1 text-sm text-slate-500">Silakan sesuaikan filter pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($absensis->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $absensis->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
