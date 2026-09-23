@extends('layouts.app')

@section('title', 'Riwayat Log WhatsApp')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="whatsappLogs()">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('admin.whatsapp.index') }}" class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Kembali ke WhatsApp Gateway
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Riwayat Log WhatsApp</h1>
                <p class="mt-1 text-sm text-slate-500">Audit seluruh notifikasi WhatsApp yang diproses oleh sistem.</p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300">
                <div class="text-2xl font-bold text-slate-900">{{ number_format($summary['total']) }}</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-wider text-slate-500">Total Log</div>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 shadow-sm transition hover:bg-emerald-50">
                <div class="text-2xl font-bold text-emerald-700">{{ number_format($summary['sent']) }}</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-wider text-emerald-600">Terkirim</div>
            </div>
            <div class="rounded-2xl border border-red-100 bg-red-50/50 p-4 shadow-sm transition hover:bg-red-50">
                <div class="text-2xl font-bold text-red-700">{{ number_format($summary['failed']) }}</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-wider text-red-600">Gagal</div>
            </div>
            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 shadow-sm transition hover:bg-blue-50">
                <div class="text-2xl font-bold text-blue-700">{{ number_format($summary['pending']) }}</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-wider text-blue-600">Pending</div>
            </div>
        </div>

        {{-- Filters Toolbar --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('admin.whatsapp.logs') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label for="search" class="sr-only">Cari</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" id="search" name="search" value="{{ $filters['search'] }}" placeholder="Cari Siswa / NIS / No HP..."
                            class="block w-full rounded-xl border border-slate-200 py-2 pl-9 pr-3 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Status --}}
                <div class="w-full sm:w-40">
                    <label for="status" class="sr-only">Status</label>
                    <select id="status" name="status" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Status (Semua)</option>
                        <option value="sent" {{ $filters['status'] === 'sent' ? 'selected' : '' }}>Terkirim</option>
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="failed" {{ $filters['status'] === 'failed' ? 'selected' : '' }}>Gagal</option>
                        <option value="skipped" {{ $filters['status'] === 'skipped' ? 'selected' : '' }}>Dilewati</option>
                    </select>
                </div>

                {{-- Type --}}
                <div class="w-full sm:w-44">
                    <label for="type" class="sr-only">Jenis</label>
                    <select id="type" name="type" class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Jenis (Semua)</option>
                        <option value="attendance_warning" {{ $filters['type'] === 'attendance_warning' ? 'selected' : '' }}>Reminder H-5</option>
                        <option value="attendance_late" {{ $filters['type'] === 'attendance_late' ? 'selected' : '' }}>Terlambat</option>
                        <option value="manual_test" {{ $filters['type'] === 'manual_test' ? 'selected' : '' }}>Manual Test</option>
                    </select>
                </div>

                {{-- Date --}}
                <div class="w-full sm:w-36">
                    <label for="date" class="sr-only">Tanggal</label>
                    <input type="date" id="date" name="date" value="{{ $filters['date'] }}" title="Tanggal Target"
                        class="block w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                {{-- Buttons --}}
                <div class="flex w-full items-center gap-2 sm:w-auto shrink-0">
                    <button type="submit" class="flex-1 sm:flex-none justify-center inline-flex rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        Terapkan
                    </button>
                    <a href="{{ route('admin.whatsapp.logs') }}" class="flex-1 sm:flex-none justify-center inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Table Container --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="border-b border-slate-200 bg-slate-50 font-semibold text-slate-700">
                        <tr>
                            <th class="py-3 px-4 w-[110px]">Tanggal</th>
                            <th class="py-3 px-4 min-w-[150px]">Siswa</th>
                            <th class="py-3 px-4 min-w-[140px]">DUDI</th>
                            <th class="py-3 px-4 w-[120px]">Jenis</th>
                            <th class="py-3 px-4 w-[130px]">Nomor HP</th>
                            <th class="py-3 px-4 min-w-[200px]">Preview Pesan</th>
                            <th class="py-3 px-4 w-[120px]">Status</th>
                            <th class="py-3 px-4 w-[80px] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-900">{{ $log->tanggal->format('d/m/Y') }}</span>
                                    <span class="block text-[10px] text-slate-500 mt-0.5">{{ $log->created_at->format('H:i') }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-900 line-clamp-1">{{ $log->siswa->nama ?? '-' }}</div>
                                    @if($log->siswa && $log->siswa->nis)
                                    <div class="text-[10px] text-slate-500 mt-0.5">NIS: {{ $log->siswa->nis }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-700 line-clamp-2">{{ $log->penempatan->dudi->nama_perusahaan ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($log->message_type === 'attendance_warning')
                                        <span class="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700 border border-blue-100">Reminder H-5</span>
                                    @elseif($log->message_type === 'attendance_late')
                                        <span class="rounded-md bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700 border border-rose-100">Terlambat</span>
                                    @elseif($log->message_type === 'manual_test')
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 border border-slate-200">Manual Test</span>
                                    @else
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 border border-slate-200">{{ $log->message_type }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                    @php
                                        // Simple formatting for ID numbers
                                        $phone = $log->recipient_phone;
                                        if(str_starts_with($phone, '62') && strlen($phone) > 6) {
                                            $formatted = '+62 ' . substr($phone, 2, 3) . '-' . substr($phone, 5, 4) . '-' . substr($phone, 9);
                                        } else {
                                            $formatted = $phone;
                                        }
                                    @endphp
                                    <span title="{{ $phone }}" data-raw-phone="{{ $phone }}">
                                        {{ $formatted }}
                                        <span class="sr-only">{{ $phone }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="text-[11px] text-slate-600 line-clamp-2 leading-relaxed" title="Klik Detail untuk melihat lengkap">
                                        {{ $log->message_content }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    @if($log->status === 'sent')
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-600">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                Terkirim
                                            </span>
                                        </div>
                                    @elseif($log->status === 'pending' || $log->status === 'sending')
                                        <span class="inline-flex items-center gap-1.5 font-semibold text-blue-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                            Pending
                                        </span>
                                    @elseif($log->status === 'skipped')
                                        <span class="inline-flex items-center gap-1.5 font-semibold text-amber-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                            Dilewati
                                        </span>
                                    @else
                                        <div class="flex flex-col gap-1 max-w-[120px]">
                                            <span class="inline-flex items-center gap-1.5 font-semibold text-red-600">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>
                                                Gagal
                                            </span>
                                            @if($log->error_reason)
                                                <span class="text-[9px] text-red-500 leading-tight line-clamp-1" title="{{ $log->error_reason }}">
                                                    {{ Str::limit($log->error_reason, 20) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button type="button"
                                        @click="openDetail({{ json_encode([
                                            'id' => $log->id,
                                            'tanggal' => $log->tanggal->format('d/m/Y'),
                                            'nama_siswa' => $log->siswa->nama ?? '-',
                                            'nis' => $log->siswa->nis ?? '-',
                                            'dudi' => $log->penempatan->dudi->nama_perusahaan ?? '-',
                                            'phone' => $formatted,
                                            'type' => $log->message_type,
                                            'status' => $log->status,
                                            'message' => $log->message_content,
                                            'error' => $log->error_reason,
                                            'created_at' => $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-',
                                            'sent_at' => $log->sent_at ? $log->sent_at->format('d/m/Y H:i:s') : '-',
                                            'failed_at' => $log->failed_at ? $log->failed_at->format('d/m/Y H:i:s') : '-',
                                        ]) }})"
                                        class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <div class="rounded-full bg-slate-100 p-3 mb-3">
                                            <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-sm font-semibold text-slate-900">Tidak ada log WhatsApp</h3>
                                        <p class="mt-1 text-xs text-slate-500 max-w-sm">Tidak ditemukan riwayat log yang sesuai dengan filter pencarian Anda.</p>
                                        @if(array_filter($filters))
                                        <a href="{{ route('admin.whatsapp.logs') }}" class="mt-4 inline-flex rounded-xl bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                                            Tampilkan Semua Log
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="border-t border-slate-200 bg-slate-50/50 p-4">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- Modal Detail --}}
    <div x-show="modalOpen" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        {{-- Backdrop --}}
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

        {{-- Panel --}}
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="modalOpen"
                     @click.away="modalOpen = false"
                     @keydown.escape.window="modalOpen = false"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all sm:my-8 sm:max-w-2xl border border-slate-200">

                    {{-- Modal Header --}}
                    <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2" id="modal-title">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Detail Log WhatsApp
                        </h3>
                        <button type="button" @click="modalOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-colors">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-6 py-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">

                            {{-- Info Grid --}}
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Siswa</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="selected.nama_siswa"></p>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">NIS</p>
                                <p class="mt-1 text-sm font-medium text-slate-900" x-text="selected.nis"></p>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">DUDI</p>
                                <p class="mt-1 text-sm font-medium text-slate-900" x-text="selected.dudi"></p>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nomor WhatsApp</p>
                                <p class="mt-1 text-sm font-mono text-slate-900" x-text="selected.phone"></p>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Jenis</p>
                                <div class="mt-1">
                                    <template x-if="selected.type === 'attendance_warning'">
                                        <span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 border border-blue-100">Reminder H-5</span>
                                    </template>
                                    <template x-if="selected.type === 'attendance_late'">
                                        <span class="rounded-md bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 border border-rose-100">Terlambat</span>
                                    </template>
                                    <template x-if="selected.type === 'manual_test'">
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 border border-slate-200">Manual Test</span>
                                    </template>
                                    <template x-if="!['attendance_warning', 'attendance_late', 'manual_test'].includes(selected.type)">
                                        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 border border-slate-200" x-text="selected.type"></span>
                                    </template>
                                </div>
                            </div>

                            <div class="sm:col-span-2 border-t border-slate-100 pt-4 mt-2">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Status & Keterangan</p>

                                <div class="flex items-center gap-2 mb-3">
                                    <template x-if="selected.status === 'sent'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Terkirim
                                        </span>
                                    </template>
                                    <template x-if="selected.status === 'pending' || selected.status === 'sending'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-ping"></span>
                                            Pending
                                        </span>
                                    </template>
                                    <template x-if="selected.status === 'skipped'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Dilewati
                                        </span>
                                    </template>
                                    <template x-if="selected.status === 'failed'">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                            Gagal
                                        </span>
                                    </template>
                                </div>

                                <template x-if="selected.error && selected.error !== '-'">
                                    <div class="rounded-xl bg-red-50 border border-red-100 p-3 text-xs text-red-800 font-mono break-words">
                                        <span class="font-bold block mb-1">Error Trace:</span>
                                        <span x-text="selected.error"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="sm:col-span-2 border-t border-slate-100 pt-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Isi Pesan</p>
                                <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-xs text-slate-700 font-mono whitespace-pre-wrap leading-relaxed shadow-inner overflow-x-auto" x-text="selected.message"></div>
                            </div>

                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-100 pt-4 mt-2">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Waktu Dibuat</p>
                                    <p class="mt-1 text-xs font-medium text-slate-700" x-text="selected.created_at"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Waktu Dikirim</p>
                                    <p class="mt-1 text-xs font-medium text-slate-700" x-text="selected.sent_at"></p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Waktu Gagal</p>
                                    <p class="mt-1 text-xs font-medium text-slate-700" x-text="selected.failed_at"></p>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex justify-end">
                        <button type="button" @click="modalOpen = false" class="inline-flex justify-center rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('whatsappLogs', () => ({
            modalOpen: false,
            selected: {
                id: '',
                tanggal: '',
                nama_siswa: '',
                nis: '',
                dudi: '',
                phone: '',
                type: '',
                status: '',
                message: '',
                error: '',
                created_at: '',
                sent_at: '',
                failed_at: ''
            },
            openDetail(data) {
                this.selected = data;
                this.modalOpen = true;
            }
        }))
    });
</script>
@endsection
