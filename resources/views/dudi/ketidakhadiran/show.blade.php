@php
    /**
     * @var \App\Models\PengajuanKetidakhadiran $pengajuan
     */
@endphp

@extends('layouts.app')

@section('title', 'Detail Pengajuan Ketidakhadiran')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-3xl space-y-6">
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

        <div class="flex items-center gap-3">
            <a href="{{ route('dudi.ketidakhadiran.index') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Detail Pengajuan</h1>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 bg-slate-50 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">{{ $pengajuan->penempatanPKL->siswa->nama }}</h3>
                        <p class="text-sm text-slate-500">NIS: {{ $pengajuan->penempatanPKL->siswa->nis }}</p>
                    </div>
                    <div>
                        @if($pengajuan->status === 'menunggu')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800">Menunggu</span>
                        @elseif($pengajuan->status === 'disetujui')
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-bold text-emerald-800">Disetujui</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1.5 text-sm font-bold text-red-800">Ditolak</span>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Tanggal Pengajuan</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $pengajuan->tanggal->format('d F Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-slate-500">Jenis Ketidakhadiran</dt>
                        <dd class="mt-1 text-sm text-slate-900 capitalize font-medium">{{ $pengajuan->jenis }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Alasan</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $pengajuan->alasan }}</dd>
                    </div>
                    @if($pengajuan->lampiran)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Lampiran</dt>
                        <dd class="mt-2 text-sm text-slate-900">
                            <a href="{{ Storage::url($pengajuan->lampiran) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 hover:bg-slate-50">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm3.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75z" />
                                </svg>
                                Lihat Lampiran (Berkas)
                            </a>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
            
            @if($pengajuan->status === 'menunggu')
            <div class="border-t border-slate-100 bg-slate-50 px-6 py-6">
                <form action="{{ route('dudi.ketidakhadiran.process', $pengajuan->id) }}" method="POST" id="process-form">
                    @csrf
                    <input type="hidden" name="status" id="status-input" value="">
                    
                    <div class="mb-4">
                        <label for="catatan" class="block text-sm font-medium text-slate-700">Catatan Validasi <span class="text-xs text-slate-400">(Wajib jika menolak)</span></label>
                        <textarea name="catatan" id="catatan" rows="2" class="mt-1 block w-full rounded-xl border border-slate-300 py-2.5 px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" onclick="submitForm('disetujui')" class="inline-flex flex-1 justify-center items-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            Setujui Pengajuan
                        </button>
                        <button type="button" onclick="submitForm('ditolak')" class="inline-flex flex-1 justify-center items-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                            Tolak Pengajuan
                        </button>
                    </div>
                </form>
            </div>
            @else
                @if($pengajuan->catatan_validasi)
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-6">
                    <h4 class="text-sm font-medium text-slate-500">Catatan Validasi:</h4>
                    <p class="mt-1 text-sm text-slate-900">{{ $pengajuan->catatan_validasi }}</p>
                    <p class="mt-2 text-xs text-slate-500">Divallidasi pada: {{ $pengajuan->validated_at?->format('d M Y, H:i') }}</p>
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@if($pengajuan->status === 'menunggu')
@push('scripts')
<script>
    function submitForm(status) {
        document.getElementById('status-input').value = status;
        
        const catatan = document.getElementById('catatan').value;
        if (status === 'ditolak' && !catatan.trim()) {
            alert('Catatan wajib diisi jika Anda menolak pengajuan.');
            return;
        }
        
        if (confirm(`Apakah Anda yakin ingin ${status === 'disetujui' ? 'menyetujui' : 'menolak'} pengajuan ini?`)) {
            document.getElementById('process-form').submit();
        }
    }
</script>
@endpush
@endif
