@extends('layouts.app')

@section('title', 'Riwayat Log Notifikasi WhatsApp')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('admin.whatsapp.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:underline">
                    $\leftarrow$ Kembali ke Dashboard WhatsApp
                </a>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Riwayat Log WhatsApp</h1>
                <p class="mt-1 text-sm text-slate-500">Audit seluruh notifikasi peringatan absensi dan pesan WhatsApp yang diproses oleh sistem.</p>
            </div>
        </div>

        {{-- Filters Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('admin.whatsapp.logs') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                {{-- Search --}}
                <div>
                    <label for="search" class="block text-xs font-semibold text-slate-700">Cari Siswa / No HP</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama, NIS, atau no HP..."
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-xs font-semibold text-slate-700">Status Pengiriman</label>
                    <select id="status" name="status" class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="sent" {{ $filters['status'] === 'sent' ? 'selected' : '' }}>Terkirim (Sent)</option>
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Dalam Antrean (Pending)</option>
                        <option value="sending" {{ $filters['status'] === 'sending' ? 'selected' : '' }}>Sedang Dikirim (Sending)</option>
                        <option value="failed" {{ $filters['status'] === 'failed' ? 'selected' : '' }}>Gagal (Failed)</option>
                        <option value="skipped" {{ $filters['status'] === 'skipped' ? 'selected' : '' }}>Dilewati (Skipped)</option>
                    </select>
                </div>

                {{-- Type --}}
                <div>
                    <label for="type" class="block text-xs font-semibold text-slate-700">Jenis Notifikasi</label>
                    <select id="type" name="type" class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Semua Jenis</option>
                        <option value="attendance_warning" {{ $filters['type'] === 'attendance_warning' ? 'selected' : '' }}>Reminder H-5 (Warning)</option>
                        <option value="attendance_late" {{ $filters['type'] === 'attendance_late' ? 'selected' : '' }}>Terlambat (Late)</option>
                        <option value="manual_test" {{ $filters['type'] === 'manual_test' ? 'selected' : '' }}>Pesan Uji Coba</option>
                    </select>
                </div>

                {{-- Date --}}
                <div>
                    <label for="date" class="block text-xs font-semibold text-slate-700">Tanggal Target</label>
                    <input type="date" id="date" name="date" value="{{ $filters['date'] }}"
                        class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                {{-- Buttons --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.whatsapp.logs') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Table Container --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="border-b border-slate-200 bg-slate-50/60 font-semibold text-slate-700">
                        <tr>
                            <th class="py-3 px-4">Tanggal & Waktu</th>
                            <th class="py-3 px-4">Siswa & DUDI</th>
                            <th class="py-3 px-4">Tipe Pesan</th>
                            <th class="py-3 px-4">Nomor HP</th>
                            <th class="py-3 px-4">Isi Pesan</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Catatan / Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-900">{{ $log->tanggal->format('d/m/Y') }}</span>
                                    <span class="block text-[11px] text-slate-400">{{ $log->created_at->format('H:i:s') }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-semibold text-slate-900">{{ $log->siswa->nama ?? '-' }}</span>
                                    <span class="block text-[11px] text-slate-400">{{ $log->penempatan->dudi->nama_perusahaan ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($log->message_type === 'attendance_warning')
                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">Reminder H-5</span>
                                    @elseif($log->message_type === 'attendance_late')
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700">Terlambat</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">{{ $log->message_type }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700">
                                    {{ $log->recipient_phone }}
                                </td>
                                <td class="py-3 px-4 max-w-xs">
                                    <p class="truncate text-[11px] text-slate-600" title="{{ $log->message_content }}">
                                        {{ $log->message_content }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Terkirim
                                        </span>
                                    @elseif($log->status === 'pending' || $log->status === 'sending')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-ping"></span>
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    @elseif($log->status === 'skipped')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Dilewati
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Gagal
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 max-w-xs truncate text-[11px] text-slate-500" title="{{ $log->error_reason }}">
                                    {{ $log->error_reason ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Tidak ada data log yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
