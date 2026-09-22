@extends('layouts.app')

@section('title', 'WhatsApp Gateway & Notifikasi')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="whatsappDashboard()">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Integrasi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">WhatsApp Gateway & Notifikasi</h1>
                <p class="mt-2 text-sm text-slate-500">Kelola gateway Baileys, koneksi sesi WhatsApp, dan jadwal pengingat absensi siswa PKL.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form method="POST" action="{{ route('admin.whatsapp.scan-now') }}"
                      data-confirm="Jalankan pemindaian absensi dan kirim pengingat WhatsApp sekarang?"
                      data-confirm-title="Pindai Absensi Sekarang"
                      data-confirm-type="info"
                      data-confirm-btn="Ya, Jalankan">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Pindai Manual Sekarang
                    </button>
                </form>

                <a href="{{ route('admin.whatsapp.logs') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    <svg class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                    Lihat Riwayat Log
                </a>
            </div>
        </div>

        {{-- Statistics Row --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Terkirim Hari Ini</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-emerald-600">{{ number_format($stats['sent_today']) }}</span>
                    <span class="text-xs text-slate-400">pesan</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dalam Antrean</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-blue-600">{{ number_format($stats['pending_today']) }}</span>
                    <span class="text-xs text-slate-400">pesan</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Gagal Hari Ini</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-red-600">{{ number_format($stats['failed_today']) }}</span>
                    <span class="text-xs text-slate-400">pesan</span>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Dilewati (No WA Kosong)</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-amber-600">{{ number_format($stats['skipped_today']) }}</span>
                    <span class="text-xs text-slate-400">siswa</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            {{-- Left Column: Gateway Connection Card & Test Message (5 cols) --}}
            <div class="space-y-6 lg:col-span-5">
                {{-- Gateway Connection Box --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900">Status WhatsApp Gateway</h2>
                        <button @click="fetchStatus()" type="button" class="text-xs font-medium text-blue-600 hover:underline flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh
                        </button>
                    </div>

                    {{-- Connection Status Banner --}}
                    <div class="mt-4">
                        <template x-if="status === 'connected'">
                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                            <p class="font-bold text-emerald-900">TERHUBUNG</p>
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-emerald-700">
                                            Nomor: <span class="font-semibold" x-text="phone || '-'"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <form method="POST" action="{{ route('admin.whatsapp.disconnect') }}"
                                          data-confirm="Putuskan sesi WhatsApp pada gateway? Anda harus memindai ulang kode QR untuk menghubungkan kembali."
                                          data-confirm-title="Putuskan Sesi WhatsApp"
                                          data-confirm-type="danger"
                                          data-confirm-btn="Ya, Putuskan">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700 hover:underline">
                                            Putuskan Sesi (Logout)
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </template>

                        <template x-if="status === 'qr_ready'">
                            <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-4 text-center">
                                <div class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                                    <span class="h-2 w-2 rounded-full bg-blue-600 animate-pulse"></span>
                                    Pindai QR Code WhatsApp
                                </div>
                                <p class="mt-2 text-xs text-slate-600">Buka WhatsApp di ponsel $\rightarrow$ Menu $\rightarrow$ Perangkat Tertaut $\rightarrow$ Tautkan Perangkat.</p>
                                <div class="mt-4 flex justify-center">
                                    <template x-if="qr">
                                        <img :src="qr" alt="WhatsApp QR Code" class="h-56 w-56 rounded-xl border border-slate-300 bg-white p-2 shadow-sm">
                                    </template>
                                </div>
                                <p class="mt-3 text-[11px] text-slate-400">QR Code akan diperbarui secara otomatis.</p>
                            </div>
                        </template>

                        <template x-if="status === 'connecting'">
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-5 w-5 animate-spin rounded-full border-2 border-amber-600 border-t-transparent"></div>
                                    <p class="text-xs font-semibold text-amber-800">Sedang menghubungkan ke server WhatsApp...</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="status === 'disconnected' || status === 'error'">
                            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="h-5 w-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <p class="font-bold text-xs text-red-900">GATEWAY TERPUTUS</p>
                                        <p class="mt-1 text-xs text-red-700" x-text="error || 'Pastikan microservice Node.js di whatsapp-gateway/ sedang berjalan pada port 3000.'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Microservice Info --}}
                    <div class="mt-5 border-t border-slate-100 pt-4 text-xs text-slate-500 space-y-1">
                        <div class="flex justify-between">
                            <span>Gateway URL:</span>
                            <span class="font-mono text-slate-700">{{ $settings['gateway_url'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Engine:</span>
                            <span class="text-slate-700 font-semibold">Baileys Multi-Device (Node.js)</span>
                        </div>
                    </div>
                </div>

                {{-- Test Send Card --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900">Uji Coba Kirim Pesan</h2>
                    <p class="mt-1 text-xs text-slate-500">Kirim pesan uji coba ke nomor WhatsApp Anda untuk memverifikasi koneksi gateway.</p>

                    <form method="POST" action="{{ route('admin.whatsapp.test-send') }}" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="test_phone" class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Tujuan</label>
                            <input type="text" id="test_phone" name="phone" placeholder="Contoh: 081234567890" required
                                class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="test_message" class="block text-xs font-semibold text-slate-700">Isi Pesan Uji Coba</label>
                            <textarea id="test_message" name="message" rows="3" required
                                class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">Halo! Ini adalah pesan uji coba dari Sistem Monitoring PKL (SIMONGAN).</textarea>
                        </div>

                        <button type="submit" class="w-full rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Kirim Pesan Uji Coba
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right Column: WhatsApp Configuration Form (7 cols) --}}
            <div class="space-y-6 lg:col-span-7">
                <form method="POST" action="{{ route('admin.whatsapp.settings.update') }}" class="space-y-6">
                    @csrf

                    {{-- Settings Container --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                        <div class="border-b border-slate-100 pb-4">
                            <h2 class="text-base font-bold text-slate-900">Konfigurasi Pengingat & Notifikasi</h2>
                            <p class="mt-1 text-xs text-slate-500">Atur switch fitur, toleransi waktu reminder, serta template pesan otomatis.</p>
                        </div>

                        {{-- Toggles --}}
                        <div class="space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Kontrol Fitur</h3>

                            {{-- Master Switch --}}
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Master WhatsApp Notification</p>
                                    <p class="text-xs text-slate-500">Saklar utama untuk mengaktifkan atau menonaktifkan seluruh pengiriman WhatsApp.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="master_enabled" value="1" class="peer sr-only" {{ $settings['master_enabled'] ? 'checked' : '' }}>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                </label>
                            </div>

                            {{-- Reminder Switch --}}
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Pengingat Absensi Masuk (H-5 Menit)</p>
                                    <p class="text-xs text-slate-500">Mengirim peringatan otomatis kepada siswa yang belum absen sebelum jam masuk DUDI.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="reminder_enabled" value="1" class="peer sr-only" {{ $settings['reminder_enabled'] ? 'checked' : '' }}>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                </label>
                            </div>

                            {{-- Late Switch --}}
                            <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Notifikasi Keterlambatan</p>
                                    <p class="text-xs text-slate-500">Mengirim pemberitahuan kepada siswa yang belum melakukan presensi setelah melewati jam masuk DUDI.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input type="checkbox" name="late_enabled" value="1" class="peer sr-only" {{ $settings['late_enabled'] ? 'checked' : '' }}>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-300 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Timing Configuration --}}
                        <div class="border-t border-slate-100 pt-4">
                            <label for="offset_minutes" class="block text-xs font-semibold text-slate-700">Waktu Pengingat Sebelum Jam Masuk (Menit)</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input type="number" id="offset_minutes" name="offset_minutes" min="1" max="60" value="{{ $settings['offset_minutes'] }}" required
                                    class="w-28 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <span class="text-xs text-slate-500">menit sebelum jam masuk DUDI (Default: 5 menit).</span>
                            </div>
                        </div>

                        {{-- Template Warning --}}
                        <div class="border-t border-slate-100 pt-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <label for="reminder_template" class="block text-xs font-semibold text-slate-700">Template Pesan Peringatan (Warning)</label>
                                <span class="text-[11px] text-slate-400">Placeholder: {nama}, {dudi}, {jam_masuk}, {tanggal}</span>
                            </div>
                            <textarea id="reminder_template" name="reminder_template" rows="6" required
                                class="block w-full rounded-xl border border-slate-200 p-3 font-mono text-xs text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ $settings['reminder_template'] }}</textarea>
                        </div>

                        {{-- Template Late --}}
                        <div class="border-t border-slate-100 pt-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <label for="late_template" class="block text-xs font-semibold text-slate-700">Template Pesan Terlambat (Late)</label>
                                <span class="text-[11px] text-slate-400">Placeholder: {nama}, {dudi}, {jam_masuk}, {status}, {tanggal}</span>
                            </div>
                            <textarea id="late_template" name="late_template" rows="6" required
                                class="block w-full rounded-xl border border-slate-200 p-3 font-mono text-xs text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ $settings['late_template'] }}</textarea>
                        </div>

                        {{-- Server & Gateway Endpoint Settings --}}
                        <div class="border-t border-slate-100 pt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="gateway_url" class="block text-xs font-semibold text-slate-700">Gateway URL</label>
                                <input type="url" id="gateway_url" name="gateway_url" value="{{ $settings['gateway_url'] }}" required
                                    class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="api_key" class="block text-xs font-semibold text-slate-700">API Token Gateway (Opsional)</label>
                                <input type="password" id="api_key" name="api_key" value="{{ $settings['api_key'] }}" placeholder="Biarkan kosong jika tanpa token"
                                    class="mt-1 block w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-xs text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                Simpan Konfigurasi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Recent Logs Preview Table --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">10 Notifikasi WhatsApp Terakhir</h2>
                    <p class="text-xs text-slate-500">Daftar riwayat notifikasi terbaru yang diproses oleh sistem.</p>
                </div>
                <a href="{{ route('admin.whatsapp.logs') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                    Lihat Semua Riwayat $\rightarrow$
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="border-b border-slate-200 bg-slate-50/50 font-semibold text-slate-700">
                        <tr>
                            <th class="py-3 px-4">Tanggal & Waktu</th>
                            <th class="py-3 px-4">Siswa</th>
                            <th class="py-3 px-4">DUDI</th>
                            <th class="py-3 px-4">Tipe</th>
                            <th class="py-3 px-4">Nomor HP</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-medium text-slate-900">{{ $log->tanggal->format('d/m/Y') }}</span>
                                    <span class="block text-[11px] text-slate-400">{{ $log->created_at->format('H:i:s') }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="font-semibold text-slate-900">{{ $log->siswa->nama ?? '-' }}</span>
                                    <span class="block text-[11px] text-slate-400">NIS: {{ $log->siswa->nis ?? '-' }}</span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    {{ $log->penempatan->dudi->nama_perusahaan ?? '-' }}
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
                                    Belum ada catatan log pengiriman WhatsApp.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function whatsappDashboard() {
    return {
        status: @json($gatewayStatus['status']),
        phone: @json($gatewayStatus['phone']),
        name: @json($gatewayStatus['name']),
        qr: @json($gatewayStatus['qr']),
        error: @json($gatewayStatus['error']),
        loading: false,
        pollTimer: null,

        init() {
            // Poll gateway status every 5 seconds
            this.pollTimer = setInterval(() => {
                this.fetchStatus();
            }, 5000);
        },

        async fetchStatus() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('admin.whatsapp.status-ajax') }}');
                const data = await res.json();
                this.status = data.status;
                this.phone = data.phone;
                this.name = data.name;
                this.qr = data.qr;
                this.error = data.error;
            } catch (e) {
                this.status = 'disconnected';
                this.error = 'Gagal memuat status dari server.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
