@extends('layouts.app')

@section('title', 'Laporan Penilaian PKL')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Laporan</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Penilaian Siswa DUDI</h1>
            </div>
            <a href="{{ route('dudi.laporan.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kembali</a>
        </div>

        {{-- Summary Cards --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-9">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm text-center xl:col-span-2">
                <p class="text-xs font-semibold text-slate-500 uppercase">Siswa / Penilaian</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($stats['total_siswa'] ?? 0) }} / {{ number_format($stats['total_penilaian'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-emerald-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Final</p>
                <p class="mt-1 text-xl font-bold text-emerald-700">{{ number_format($stats['final'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-amber-50 p-4 shadow-card-sm text-center">
                <p class="text-xs font-semibold text-amber-600 uppercase">Draft</p>
                <p class="mt-1 text-xl font-bold text-amber-700">{{ number_format($stats['draft'] ?? 0) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-blue-50 p-4 shadow-card-sm text-center xl:col-span-2">
                <p class="text-xs font-semibold text-blue-600 uppercase">Rata-rata Nilai</p>
                <p class="mt-1 text-xl font-bold text-blue-700">{{ number_format($stats['rata_rata_nilai'] ?? 0, 1) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card-sm text-center flex items-center justify-around xl:col-span-3">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Predikat A</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($stats['predikat_a'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Predikat B</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($stats['predikat_b'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Predikat C</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($stats['predikat_c'] ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase">Predikat D</p>
                    <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($stats['predikat_d'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Form --}}
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
            <form action="{{ route('dudi.laporan.penilaian') }}" method="GET">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div>
                        <label for="periode_id" class="block text-xs font-semibold text-slate-700">Periode</label>
                        <select name="periode_id" id="periode_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Semua Periode</option>
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" {{ request('periode_id') == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="jurusan_id" class="block text-xs font-semibold text-slate-700">Jurusan</label>
                        <select name="jurusan_id" id="jurusan_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Semua Jurusan</option>
                            @foreach($jurusans as $j)
                                <option value="{{ $j->id }}" {{ request('jurusan_id') == $j->id ? 'selected' : '' }}>{{ $j->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="kelas_id" class="block text-xs font-semibold text-slate-700">Kelas</label>
                        <select name="kelas_id" id="kelas_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Semua Kelas</option>
                            @foreach($kelass as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Perusahaan / Mitra</label>
                        <input type="text" readonly value="{{ $dudi->nama_perusahaan }}" class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-slate-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                            <option value="">Semua Status</option>
                            <option value="final" {{ request('status') == 'final' ? 'selected' : '' }}>Final</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div>
                        <label for="tanggal_mulai" class="block text-xs font-semibold text-slate-700">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <label for="tanggal_akhir" class="block text-xs font-semibold text-slate-700">Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="{{ request('tanggal_akhir') }}" class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <a href="{{ route('dudi.laporan.penilaian') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Reset</a>
                    <button type="submit" class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-700">Tampilkan</button>
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Guru Pembimbing</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Rincian Aspek</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Nilai Akhir</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($penilaian as $index => $item)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                    {{ $penilaian->firstItem() + $index }}
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">{{ $item->penempatanPKL->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">NIS: {{ $item->penempatanPKL->siswa->nis ?? '-' }}</p>
                                    <p class="mt-1 text-xs font-medium text-orange-600">{{ $item->penempatanPKL->siswa->kelas->nama ?? '-' }} - {{ $item->penempatanPKL->siswa->kelas->jurusan->singkatan ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    <p class="font-semibold">{{ $item->penempatanPKL->guru->nama ?? '-' }}</p>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                                        <div class="flex justify-between"><span>Kehadiran:</span> <span class="font-bold">{{ $item->nilai_kehadiran ?? '-' }}</span></div>
                                        <div class="flex justify-between"><span>Kerja Sama:</span> <span class="font-bold">{{ $item->nilai_kerjasama ?? '-' }}</span></div>
                                        <div class="flex justify-between"><span>Komunikasi:</span> <span class="font-bold">{{ $item->nilai_komunikasi ?? '-' }}</span></div>
                                        <div class="flex justify-between"><span>Problem Solv:</span> <span class="font-bold">{{ $item->nilai_problem_solving ?? '-' }}</span></div>
                                        <div class="flex justify-between"><span>Teknis:</span> <span class="font-bold">{{ $item->nilai_teknis ?? '-' }}</span></div>
                                        <div class="flex justify-between"><span>Inisiatif:</span> <span class="font-bold">{{ $item->nilai_inisiatif ?? '-' }}</span></div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="text-2xl font-black text-slate-900">{{ $item->nilai_akhir ? number_format($item->nilai_akhir, 1) : '-' }}</p>
                                    @if($item->predikat)
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset {{ $item->predikat == 'A' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : ($item->predikat == 'B' ? 'bg-blue-50 text-blue-700 ring-blue-200' : ($item->predikat == 'C' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-red-50 text-red-700 ring-red-200')) }}">
                                            Predikat {{ $item->predikat }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    @php
                                        $statusClass = match(strtolower($item->status)) {
                                            'final' => 'bg-blue-50 text-blue-700 ring-blue-200',
                                            'draft' => 'bg-slate-50 text-slate-600 ring-slate-200',
                                            default => 'bg-slate-50 text-slate-700 ring-slate-200'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                    @if($item->tanggal_penilaian)
                                        <p class="mt-2 text-xs text-slate-500">{{ \Carbon\Carbon::parse($item->tanggal_penilaian)->format('d M Y') }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-900">Data laporan penilaian tidak ditemukan.</h3>
                                    <p class="mt-1 text-sm text-slate-500">Silakan sesuaikan filter pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($penilaian->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $penilaian->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
