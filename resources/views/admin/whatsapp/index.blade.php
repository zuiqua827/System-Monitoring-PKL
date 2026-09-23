@extends('layouts.app')

@section('title', 'WhatsApp Gateway & Notifikasi')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="whatsappDashboard()">
    <div class="mx-auto max-w-7xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Monitoring & Operasional</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">WhatsApp Gateway & Notifikasi</h1>
                <p class="mt-2 text-sm text-slate-500">Pantau status gateway Baileys, statistik notifikasi, dan operasional pengingat absensi harian.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if(session('success'))
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-2.5 flex items-center gap-2 text-emerald-800 text-sm font-semibold shadow-sm animate-pulse-once">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-2.5 flex items-center gap-2 text-red-800 text-sm font-semibold shadow-sm animate-pulse-once">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.whatsapp.scan-now') }}"
                      data-confirm="Jalankan pemindaian absensi dan kirim pengingat WhatsApp sekarang?"
                      data-confirm-title="Pindai Absensi Sekarang"
                      data-confirm-type="info"
                      data-confirm-btn="Ya, Jalankan">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Pindai Manual Sekarang
                    </button>
                </form>

                <a href="{{ route('admin.whatsapp.logs') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                    Semua Riwayat Log
                </a>
            </div>
        </div>

        {{-- Top Info Bar & Stats --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-2 bg-emerald-500"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Terkirim Hari Ini</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-slate-800">{{ number_format($stats['sent_today']) }}</span>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $stats['success_rate'] }}% Success</span>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">
                    Terakhir: {{ $stats['last_sent'] ? \Carbon\Carbon::parse($stats['last_sent'])->translatedFormat('H:i') : '-' }}
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-2 bg-blue-500"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dalam Antrean</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-slate-800">{{ number_format($stats['pending_today']) }}</span>
                    <span class="text-xs text-slate-500">Pesan pending</span>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">Diproses oleh background worker</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-2 bg-red-500"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Gagal Hari Ini</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-slate-800">{{ number_format($stats['failed_today']) }}</span>
                    <span class="text-xs text-slate-500">Gagal / Invalid</span>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">
                    Terakhir: {{ $stats['last_failed'] ? \Carbon\Carbon::parse($stats['last_failed'])->translatedFormat('H:i') : '-' }}
                </p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 h-full w-2 bg-amber-500"></div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dilewati (No WA Kosong)</p>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-slate-800">{{ number_format($stats['skipped_today']) }}</span>
                    <span class="text-xs text-slate-500">Siswa diabaikan</span>
                </div>
                <p class="mt-2 text-[10px] text-slate-400">Tidak ada nomor valid untuk dikirim</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Left Column: Gateway Status & Test Message (1 col out of 3) --}}
            <div class="space-y-6 lg:col-span-1">

                {{-- Gateway Connection Box --}}
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <h2 class="text-sm font-bold text-slate-900">Status Gateway</h2>
                        </div>
                        <button @click="fetchStatus()" type="button" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1 transition-colors">
                            <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Refresh
                        </button>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-center min-h-[160px]">
                        {{-- Connection Status Banner --}}
                        <template x-if="status === 'connected'">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="relative">
                                    <div class="absolute inset-0 rounded-full bg-emerald-400 animate-ping opacity-25"></div>
                                    <div class="relative flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">TERHUBUNG</h3>
                                    <p class="text-xs font-mono font-medium text-slate-500 mt-0.5" x-text="phone"></p>
                                </div>
                                <form method="POST" action="{{ route('admin.whatsapp.disconnect') }}" class="w-full mt-2"
                                      data-confirm="Putuskan sesi WhatsApp pada gateway? Anda harus memindai ulang kode QR untuk menghubungkan kembali."
                                      data-confirm-title="Putuskan Sesi WhatsApp"
                                      data-confirm-type="danger"
                                      data-confirm-btn="Ya, Putuskan">
                                    @csrf
                                    <button type="submit" class="w-full rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 hover:text-red-600 transition-colors">
                                        Putuskan Sesi
                                    </button>
                                </form>
                            </div>
                        </template>

                        <template x-if="status === 'qr_ready'">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-[10px] font-bold tracking-wider text-amber-800 uppercase">
                                    <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    QR Diperlukan
                                </div>
                                <template x-if="qr">
                                    <img :src="qr" alt="WhatsApp QR Code" class="h-40 w-40 rounded-xl border border-slate-200 bg-white p-1.5 shadow-sm">
                                </template>
                                <p class="text-[11px] text-slate-500 px-2 leading-tight">Pindai QR ini melalui aplikasi WhatsApp (Perangkat Tertaut).</p>
                            </div>
                        </template>

                        <template x-if="status === 'connecting'">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="h-10 w-10 animate-spin rounded-full border-2 border-slate-300 border-t-blue-600"></div>
                                <p class="text-xs font-semibold text-slate-600">Menghubungkan ke server...</p>
                            </div>
                        </template>

                        <template x-if="status === 'disconnected' || status === 'error'">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-red-600">TERPUTUS</h3>
                                    <p class="text-[11px] text-slate-500 mt-1 px-2" x-text="error || 'Pastikan microservice Node.js berjalan.'"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="bg-slate-50 border-t border-slate-200 px-5 py-3 text-[10px] text-slate-500 flex justify-between">
                        <span>Engine: <span class="font-semibold text-slate-700">Baileys MD</span></span>
                        <span>Update: <span class="font-mono text-slate-700" x-text="lastChecked"></span></span>
                    </div>
                </div>

                {{-- Test Send Card --}}
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-4">
                        <h2 class="text-sm font-bold text-slate-900">Uji Coba Pesan</h2>
                    </div>

                    <div class="p-5">
                        <form method="POST" action="{{ route('admin.whatsapp.test-send') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="test_phone" class="block text-xs font-semibold text-slate-700 mb-1">Nomor Tujuan</label>
                                <input type="text" id="test_phone" name="phone" placeholder="081234567890" required
                                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-shadow">
                            </div>

                            <div>
                                <label for="test_message" class="block text-xs font-semibold text-slate-700 mb-1">Pesan</label>
                                <textarea id="test_message" name="message" rows="2" required
                                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-shadow resize-none">Pesan uji coba dari SIMONGAN.</textarea>
                            </div>

                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                Kirim Uji Coba
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Right Column: Notification Controls & Logs (2 cols) --}}
            <div class="space-y-6 lg:col-span-2 flex flex-col">

                {{-- Notification Controls --}}
                <form method="POST" action="{{ route('admin.whatsapp.settings.update') }}" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    @csrf
                    {{-- Hidden technical fields so validation doesn't fail --}}
                    <input type="hidden" name="gateway_url" value="{{ $settings['gateway_url'] }}">
                    <input type="hidden" name="api_key" value="{{ $settings['api_key'] }}">

                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Kontrol Notifikasi & Pengingat</h2>
                        </div>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>

                    <div class="p-5 grid grid-cols-1 gap-6 md:grid-cols-2">

                        {{-- Switches Column --}}
                        <div class="space-y-4">
                            {{-- Master Switch --}}
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:bg-slate-50">
                                <div class="pr-4">
                                    <p class="text-xs font-bold text-slate-900">Master WhatsApp Notification</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5 leading-tight">Matikan saklar ini untuk menghentikan seluruh pengiriman WhatsApp seketika (Kill switch).</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center shrink-0">
                                    <input type="checkbox" name="master_enabled" value="1" class="peer sr-only" {{ $settings['master_enabled'] ? 'checked' : '' }}>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                                </label>
                            </div>

                            {{-- Reminder Switch --}}
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:bg-slate-50">
                                <div class="pr-4">
                                    <p class="text-xs font-bold text-slate-900">Pengingat Absensi Masuk</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5 leading-tight">Kirim peringatan ke siswa yang belum absen sebelum jam masuk.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center shrink-0">
                                    <input type="checkbox" name="reminder_enabled" value="1" class="peer sr-only" {{ $settings['reminder_enabled'] ? 'checked' : '' }}>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                                </label>
                            </div>

                            {{-- Late Switch --}}
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/50 p-4 transition-colors hover:bg-slate-50">
                                <div class="pr-4">
                                    <p class="text-xs font-bold text-slate-900">Notifikasi Keterlambatan</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5 leading-tight">Pemberitahuan kepada siswa yang melewati jam masuk DUDI tanpa absensi.</p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center shrink-0">
                                    <input type="checkbox" name="late_enabled" value="1" class="peer sr-only" {{ $settings['late_enabled'] ? 'checked' : '' }}>
                                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-full peer-focus:outline-none"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Templates Column --}}
                        <div class="space-y-4">
                            <div>
                                <label for="offset_minutes" class="block text-xs font-bold text-slate-900 mb-1">Durasi Pengingat (Menit)</label>
                                <div class="relative">
                                    <input type="number" id="offset_minutes" name="offset_minutes" min="1" max="60" value="{{ $settings['offset_minutes'] }}" required
                                        class="block w-full rounded-lg border border-slate-200 pl-3 pr-16 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-xs text-slate-400">menit sblm</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="reminder_template" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide mb-1">Template Pengingat (H-5)</label>
                                <textarea id="reminder_template" name="reminder_template" rows="3" required
                                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 font-mono text-[10px] text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ $settings['reminder_template'] }}</textarea>
                            </div>

                            <div>
                                <label for="late_template" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wide mb-1">Template Terlambat</label>
                                <textarea id="late_template" name="late_template" rows="3" required
                                    class="block w-full rounded-lg border border-slate-200 px-3 py-2 font-mono text-[10px] text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">{{ $settings['late_template'] }}</textarea>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Recent Logs Preview Table --}}
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm flex-1 flex flex-col overflow-hidden">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-900">Riwayat Terkini</h2>
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-600">10 Terakhir</span>
                        </div>

                        <div class="flex gap-2">
                            <button @click="filter = 'all'" type="button" :class="filter === 'all' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="px-2 py-1 text-[10px] font-semibold rounded-md transition-colors">Semua</button>
                            <button @click="filter = 'failed'" type="button" :class="filter === 'failed' ? 'bg-red-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'" class="px-2 py-1 text-[10px] font-semibold rounded-md transition-colors">Gagal</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-left text-[11px] text-slate-600">
                            <thead class="border-b border-slate-100 bg-white text-slate-400 font-semibold uppercase tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-4 font-medium">Siswa & Waktu</th>
                                    <th class="py-2.5 px-4 font-medium">DUDI</th>
                                    <th class="py-2.5 px-4 font-medium">Nomor Tujuan</th>
                                    <th class="py-2.5 px-4 font-medium">Status & Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @forelse($recentLogs as $log)
                                    <tr class="hover:bg-slate-50/50 transition-colors" x-show="filter === 'all' || (filter === 'failed' && '{{ $log->status }}' === 'failed')">
                                        <td class="py-3 px-4">
                                            <p class="font-bold text-slate-800">{{ $log->siswa->nama ?? '-' }}</p>
                                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $log->tanggal->format('d/m/y') }} • {{ $log->created_at->format('H:i') }}</p>
                                        </td>
                                        <td class="py-3 px-4 text-slate-700">
                                            {{ Str::limit($log->penempatan->dudi->nama_perusahaan ?? '-', 20) }}
                                        </td>
                                        <td class="py-3 px-4 font-mono text-slate-500">
                                            {{ $log->recipient_phone }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex flex-col gap-1 items-start">
                                                @if($log->status === 'sent')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Terkirim</span>
                                                @elseif($log->status === 'pending' || $log->status === 'sending')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 border border-blue-200 px-2 py-0.5 text-[10px] font-bold text-blue-700">Antrean</span>
                                                @elseif($log->status === 'skipped')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-700">Dilewati</span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-[10px] font-bold text-red-700">Gagal</span>
                                                @endif

                                                @if($log->error_reason)
                                                    <span class="text-[9px] text-slate-400 max-w-[150px] truncate" title="{{ $log->error_reason }}">{{ $log->error_reason }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-slate-400">
                                            Belum ada log notifikasi WhatsApp.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

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
        lastChecked: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
        filter: 'all',

        init() {
            this.pollTimer = setInterval(() => {
                this.fetchStatus();
            }, 5000);

            // Show any session flash messages using standard toast if implemented in app layout
            // (Assumes a toast library or Alpine global store handles this based on session)
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
                this.lastChecked = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            } catch (e) {
                this.status = 'error';
                this.error = 'Gagal terhubung ke service gateway lokal.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
