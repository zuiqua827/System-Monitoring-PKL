@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $pengajuans
     * @var \App\Models\PenempatanPKL|null $penempatanAktif
     */
@endphp

@extends('layouts.app')

@section('title', 'Pengajuan Ketidakhadiran')

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
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Siswa</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pengajuan Ketidakhadiran</h1>
                <p class="mt-2 text-sm text-slate-500">Ajukan Izin atau Sakit ke pembimbing DUDI</p>
            </div>
        </div>

        @if($penempatanAktif)
            {{-- Form Pengajuan --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="border-b border-slate-100 px-5 py-4 bg-slate-50">
                    <h3 class="text-base font-bold text-slate-900">Buat Pengajuan Baru</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('siswa.ketidakhadiran.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="tanggal" class="block text-sm font-medium text-slate-700">Tanggal</label>
                                <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                                       class="mt-1 block w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="jenis" class="block text-sm font-medium text-slate-700">Jenis</label>
                                <select name="jenis" id="jenis" required
                                        class="mt-1 block w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <option value="">Pilih Jenis</option>
                                    <option value="izin" {{ old('jenis') == 'izin' ? 'selected' : '' }}>Izin</option>
                                    <option value="sakit" {{ old('jenis') == 'sakit' ? 'selected' : '' }}>Sakit</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="alasan" class="block text-sm font-medium text-slate-700">Alasan</label>
                            <textarea name="alasan" id="alasan" rows="3" required placeholder="Jelaskan alasan ketidakhadiran Anda secara singkat..."
                                      class="mt-1 block w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('alasan') }}</textarea>
                        </div>

                        <div>
                            <label for="lampiran" class="block text-sm font-medium text-slate-700">Lampiran (Surat Dokter/Bukti)</label>
                            <input type="file" name="lampiran" id="lampiran" accept=".jpg,.jpeg,.png,.pdf"
                                   class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-xl file:border-0 file:bg-blue-50 file:py-2.5 file:px-4 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-xs text-slate-500">Maks. 2MB (JPG, PNG, PDF)</p>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                Kirim Pengajuan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-card-sm">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-medium text-amber-800">Anda belum memiliki penempatan PKL yang aktif.</p>
                </div>
            </div>
        @endif

        {{-- Riwayat Pengajuan --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900">Riwayat Pengajuan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jenis</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Alasan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Catatan</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Lampiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pengajuans as $index => $p)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $pengajuans->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-800">{{ $p->tanggal->format('d/m/Y') }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600 capitalize">{{ $p->jenis }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ Str::limit($p->alasan, 50) }}</td>
                                <td class="px-4 py-3.5">
                                    @if($p->status === 'menunggu')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Menunggu</span>
                                    @elseif($p->status === 'disetujui')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Disetujui</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800">Ditolak</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $p->catatan_validasi ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm">
                                    @if($p->lampiran)
                                        <a href="{{ Storage::url($p->lampiran) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4M5.5 9.5h13M7 20h10a2.5 2.5 0 0 0 2.5-2.5v-10A2.5 2.5 0 0 0 17 5H7a2.5 2.5 0 0 0-2.5 2.5v10A2.5 2.5 0 0 0 7 20Z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada riwayat pengajuan.</p>
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
