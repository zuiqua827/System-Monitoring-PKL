@extends('layouts.app')

@section('title', 'Laporan Siswa & Penempatan PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Laporan</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Siswa & Penempatan PKL</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.laporan.siswa.export.excel', request()->query()) }}" class="flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('admin.laporan.siswa.export.pdf', request()->query()) }}" class="flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5M8 16h8M8 12h3" />
                    </svg>
                    Export PDF
                </a>
                <a href="{{ route('admin.laporan.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kembali</a>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
            <form action="{{ route('admin.laporan.siswa') }}" method="GET">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div>
                        <label for="periode_id" class="block text-xs font-semibold text-slate-700">Periode</label>
                        <select name="periode_id" id="periode_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Periode</option>
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="jurusan_id" class="block text-xs font-semibold text-slate-700">Jurusan</label>
                        <select name="jurusan_id" id="jurusan_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Jurusan</option>
                            @foreach($jurusans as $j)
                                <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="kelas_id" class="block text-xs font-semibold text-slate-700">Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Kelas</option>
                            @foreach($kelass as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="guru_id" class="block text-xs font-semibold text-slate-700">Guru</label>
                        <select name="guru_id" id="guru_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Guru</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dudi_id" class="block text-xs font-semibold text-slate-700">DUDI</label>
                        <select name="dudi_id" id="dudi_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua DUDI</option>
                            @foreach($dudis as $d)
                                <option value="{{ $d->id }}" {{ request('dudi_id') == $d->id ? 'selected' : '' }}>{{ $d->nama_perusahaan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-slate-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                            <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <a href="{{ route('admin.laporan.siswa') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">Tampilkan</button>
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">DUDI</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Guru</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($penempatanPkls as $index => $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $penempatanPkls->firstItem() + $index }}
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">{{ $item->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">NIS: {{ $item->siswa->nis ?? '-' }}</p>
                                    <p class="mt-1 text-xs font-medium text-blue-600">{{ $item->siswa->kelas->nama ?? '-' }} - {{ $item->siswa->kelas->jurusan->singkatan ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    <p class="font-semibold">{{ $item->dudi->nama_perusahaan ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    <p class="font-semibold">{{ $item->guru->nama ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <p>{{ $item->periodePkl->nama ?? '-' }}</p>
                                    <p class="text-xs">{{ $item->tanggal_mulai?->format('d M Y') ?? '-' }} - {{ $item->tanggal_selesai?->format('d M Y') ?? '-' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @php
                                        $statusClass = match($item->status) {
                                            'aktif' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                            'selesai' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                            'dibatalkan' => 'bg-red-50 text-red-700 ring-red-200',
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">Data laporan siswa tidak ditemukan.</h3>
                                    <p class="mt-1 text-sm text-slate-500">Silakan sesuaikan filter pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($penempatanPkls->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $penempatanPkls->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
