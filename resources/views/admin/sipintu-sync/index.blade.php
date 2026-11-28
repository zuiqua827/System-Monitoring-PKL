@extends('layouts.app')

@section('title', 'Sinkronisasi SiPintu')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="{
    syncing: false,
    syncStep: 1,
    syncEntity: 'all',
    createMissingDudi: false,
    startSync(entity = 'all') {
        this.syncEntity = entity;
        const scopeLabel = entity === 'siswa'
            ? 'Siswa'
            : (entity === 'guru' ? 'Guru' : (entity === 'dudi' ? 'DUDI' : 'seluruh entitas aktif (Siswa, Guru, DUDI)'));
        let msg = 'Sistem akan mengunduh data ' + scopeLabel + ' dari gateway SiPintu dan memperbarui database lokal SIMONGAN. Password akun dan data operasional PKL tidak pernah dihapus atau ditimpa.';
        if (entity === 'dudi' && !this.createMissingDudi) {
            msg += ' Mode Link-only aktif: hanya menautkan & memperbarui data identitas untuk DUDI yang sudah dibuat Admin.';
        } else if (entity === 'dudi' && this.createMissingDudi) {
            msg += ' Konfirmasi aktif: data DUDI baru dari SiPintu yang belum ada di SIMONGAN juga akan dibuat.';
        }
        window.showConfirm({
            type: 'info',
            title: 'Mulai Sinkronisasi ' + scopeLabel + '?',
            message: msg,
            confirmText: 'Mulai Sinkronisasi',
            cancelText: 'Batal',
            onConfirm: () => {
                this.syncing = true;
                this.syncStep = 1;
                setInterval(() => {
                    if (this.syncStep < 3) this.syncStep++;
                }, 1800);
                this.$nextTick(() => this.$refs.syncForm.submit());
            }
        });
    }
}">
    {{-- SiPintu Sync Progress Overlay Modal --}}
    <div x-show="syncing" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-md"></div>
        <div class="relative w-full max-w-lg rounded-3xl bg-white p-7 shadow-2xl border border-slate-100 z-10 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-8 ring-blue-50/50">
                <svg class="h-8 w-8 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
            </div>
            <h3 class="mt-4 text-xl font-bold text-slate-900">Sinkronisasi SiPintu Berlangsung</h3>
            <p class="mt-1 text-sm text-slate-500">Menyinkronkan data siswa dan guru dari gateway SiPintu...</p>

            {{-- Interactive Step Tracker --}}
            <div class="mt-6 space-y-3 text-left">
                <div class="flex items-center gap-3 rounded-xl p-3" :class="syncStep >= 1 ? 'bg-blue-50/70 text-blue-900' : 'bg-slate-50 text-slate-400'">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" :class="syncStep > 1 ? 'bg-emerald-500 text-white' : (syncStep === 1 ? 'bg-blue-600 text-white animate-pulse' : 'bg-slate-200 text-slate-500')">
                        <template x-if="syncStep > 1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </template>
                        <template x-if="syncStep <= 1">
                            <span class="text-xs font-bold">1</span>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold">1. Menghubungkan ke API Gateway SiPintu</p>
                        <p class="text-xs text-slate-500" x-show="syncStep === 1">Memeriksa status koneksi dan otentikasi...</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl p-3" :class="syncStep >= 2 ? 'bg-blue-50/70 text-blue-900' : 'bg-slate-50 text-slate-400'">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" :class="syncStep > 2 ? 'bg-emerald-500 text-white' : (syncStep === 2 ? 'bg-blue-600 text-white animate-pulse' : 'bg-slate-200 text-slate-500')">
                        <template x-if="syncStep > 2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </template>
                        <template x-if="syncStep <= 2">
                            <span class="text-xs font-bold">2</span>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold">2. Mengambil & Mengunduh Data</p>
                        <p class="text-xs text-slate-500" x-show="syncStep === 2">Mengunduh data seluruh siswa dan guru...</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-xl p-3" :class="syncStep >= 3 ? 'bg-blue-50/70 text-blue-900' : 'bg-slate-50 text-slate-400'">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" :class="syncStep >= 3 ? 'bg-blue-600 text-white animate-pulse' : 'bg-slate-200 text-slate-500'">
                        <span class="text-xs font-bold">3</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold">3. Memproses & Menyimpan ke SIPKL</p>
                        <p class="text-xs text-slate-500" x-show="syncStep >= 3">Mencocokkan NIS, NIP, dan mapping kelas lokal...</p>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-2xl bg-amber-50 p-3.5 text-xs text-amber-800 border border-amber-200/80">
                <span class="font-bold">Pemberitahuan:</span> Harap jangan menutup atau merefresh halaman ini sampai proses sinkronisasi selesai.
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Integrasi</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Sinkronisasi SiPintu</h1>
                <p class="mt-2 text-sm text-slate-500">Sinkronkan data Siswa, Guru, dan Master DUDI dari gateway SiPintu secara terisolasi maupun menyeluruh ke SIMONGAN.</p>
            </div>

            {{-- Sync / Preview Buttons --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <form method="POST" action="{{ route('admin.sipintu-sync.test-connection') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        <span>Test Connection</span>
                    </button>
                </form>
                <a href="{{ route('admin.sipintu-sync.preview') }}"
                   class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-xs font-semibold text-blue-700 shadow-sm transition hover:bg-blue-100">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>Preview (Dry Run)</span>
                </a>
                <form x-ref="syncForm" method="POST" action="{{ route('admin.sipintu-sync.sync') }}">
                    @csrf
                    <input type="hidden" name="entity" :value="syncEntity">
                    <input type="hidden" name="create_missing_dudi" :value="createMissingDudi ? '1' : '0'">
                    <button
                        type="button"
                        @click="startSync('all')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Mulai Sinkronisasi</span>
                    </button>
                </form>
                <a href="#riwayat-sinkronisasi"
                   class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    <span>Lihat Riwayat</span>
                </a>
            </div>
        </div>

        {{-- Connection + Stats Cards --}}
        @php
            $connBadge = match ($connectionStatus) {
                'connected' => ['label' => 'Terhubung Penuh', 'class' => 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
                'partial', 'warning' => ['label' => 'Sebagian Tersedia (Siswa Siap)', 'class' => 'bg-amber-50 text-amber-800 ring-amber-200'],
                'ready' => ['label' => 'Siap Dihubungkan', 'class' => 'bg-blue-50 text-blue-700 ring-blue-200'],
                'endpoint_unavailable' => ['label' => 'Endpoint Belum Tersedia', 'class' => 'bg-amber-50 text-amber-800 ring-amber-200'],
                'authentication_failed' => ['label' => 'Autentikasi Gagal', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
                'not_configured' => ['label' => 'Belum Dikonfigurasi', 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
                'gateway_unreachable', 'error' => ['label' => 'Gateway Tidak Dapat Dihubungi', 'class' => 'bg-rose-50 text-rose-700 ring-rose-200'],
                default => ['label' => 'Status: ' . ucfirst(str_replace('_', ' ', $connectionStatus)), 'class' => 'bg-slate-50 text-slate-700 ring-slate-200'],
            };
        @endphp

        {{-- 6-Point Endpoint Diagnosis Panel (Live Diagnostics) --}}
        @if (!empty($endpointsDiagnosis))
            @php
                $diagGateway = $endpointsDiagnosis['gateway'] ?? null;
                $diagStudents = $endpointsDiagnosis['students'] ?? null;
                $diagTeachers = $endpointsDiagnosis['teachers'] ?? null;
                $diagDudi = $endpointsDiagnosis['dudi'] ?? null;
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Diagnosis Ketersediaan Endpoint SiPintu (6 Titik Layanan)</h3>
                        <p class="text-xs text-slate-500">Pemeriksaan live ketersediaan gateway, otentikasi, dan endpoint data master (cache 5 menit)</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 w-fit">Pemeriksaan Live</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    {{-- 1. Gateway Health --}}
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 space-y-1">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold text-slate-700">1. Koneksi Gateway</span>
                            @if (!empty($diagGateway['status']))
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200">
                                    Online{{ !empty($diagGateway['http_status']) ? ' ('.$diagGateway['http_status'].')' : '' }}
                                </span>
                            @elseif ($diagGateway !== null)
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 ring-1 ring-rose-200">
                                    Offline{{ !empty($diagGateway['http_status']) ? ' ('.$diagGateway['http_status'].')' : '' }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-600 font-mono">/api/v1/health</p>
                        <p class="text-[11px] text-slate-500">{{ $diagGateway['message'] ?? 'Status gateway belum diperiksa.' }}</p>
                    </div>

                    {{-- 2. User Profile Endpoint --}}
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-3.5 space-y-1">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold text-slate-700">2. Endpoint Profil</span>
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700 ring-1 ring-blue-200">
                                OAuth SSO Aktif
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 font-mono">/api/v1/user</p>
                        <p class="text-[11px] text-slate-500">Otorisasi login SSO pengguna aktif.</p>
                    </div>

                    {{-- 3. Students Endpoint --}}
                    @php
                        $sOk = !empty($diagStudents['status']);
                        $sHttp = $diagStudents['http_status'] ?? null;
                        $sErrType = $diagStudents['error_type'] ?? null;
                    @endphp
                    <div class="rounded-xl border {{ $sOk ? 'border-emerald-200 bg-emerald-50/40' : ($diagStudents ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-slate-50/70') }} p-3.5 space-y-1">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold {{ $sOk ? 'text-emerald-900' : 'text-slate-800' }}">3. Endpoint Siswa</span>
                            @if ($sOk)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-300">
                                    Tersedia{{ $sHttp !== null ? ' ('.$sHttp.')' : '' }}
                                </span>
                            @elseif ($diagStudents !== null)
                                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-700 ring-1 ring-rose-200">
                                    {{ $sErrType === 'timeout' ? 'Timeout' : ($sHttp === 404 ? 'Belum Ada (404)' : ($sHttp !== null ? 'HTTP '.$sHttp : 'Error Koneksi')) }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>
                        <p class="text-xs {{ $sOk ? 'text-emerald-800' : 'text-slate-700' }} font-mono">/api/v1/sijuna/students</p>
                        <p class="text-[11px] {{ $sOk ? 'text-emerald-700' : 'text-slate-500' }}">{{ $diagStudents['message'] ?? 'Status endpoint siswa belum diperiksa.' }}</p>
                    </div>

                    {{-- 4. Teachers Endpoint --}}
                    @php
                        $tOk = !empty($diagTeachers['status']);
                        $tHttp = $diagTeachers['http_status'] ?? null;
                        $tErrType = $diagTeachers['error_type'] ?? null;
                    @endphp
                    <div class="rounded-xl border {{ $tOk ? 'border-emerald-200 bg-emerald-50/40' : ($diagTeachers ? 'border-amber-200 bg-amber-50/60' : 'border-slate-200 bg-slate-50/70') }} p-3.5 space-y-1">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold {{ $tOk ? 'text-emerald-900' : ($diagTeachers ? 'text-amber-900' : 'text-slate-700') }}">4. Endpoint Guru</span>
                            @if ($tOk)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-300">
                                    Tersedia{{ $tHttp !== null ? ' ('.$tHttp.')' : '' }}
                                </span>
                            @elseif ($diagTeachers !== null)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-900 ring-1 ring-amber-300">
                                    {{ $tErrType === 'timeout' ? 'Timeout' : ($tHttp === 404 ? 'Belum Ada (404)' : ($tHttp !== null ? 'HTTP '.$tHttp : 'Error Koneksi')) }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>
                        <p class="text-xs {{ $tOk ? 'text-emerald-800' : 'text-amber-900' }} font-mono">/api/v1/sijuna/teachers</p>
                        <p class="text-[11px] {{ $tOk ? 'text-emerald-700' : 'text-amber-800' }}">{{ $diagTeachers['message'] ?? 'Status endpoint guru belum diperiksa.' }}</p>
                    </div>

                    {{-- 5. DUDI Endpoint --}}
                    @php
                        $dOk = !empty($diagDudi['status']);
                        $dHttp = $diagDudi['http_status'] ?? null;
                        $dErrType = $diagDudi['error_type'] ?? null;
                    @endphp
                    <div class="rounded-xl border {{ $dOk ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200/80 bg-slate-50/70' }} p-3.5 space-y-1">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-bold {{ $dOk ? 'text-emerald-900' : 'text-slate-700' }}">5. Endpoint DUDI</span>
                            @if ($dOk)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-300">
                                    Tersedia{{ $dHttp !== null ? ' ('.$dHttp.')' : '' }}
                                </span>
                            @elseif ($diagDudi !== null)
                                <span class="inline-flex items-center rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-300">
                                    {{ $dErrType === 'timeout' ? 'Timeout' : ($dHttp === 404 ? 'Belum Ada (404)' : ($dHttp !== null ? 'HTTP '.$dHttp : 'Error Koneksi')) }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 ring-1 ring-slate-200">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>
                        <p class="text-xs {{ $dOk ? 'text-emerald-800' : 'text-slate-600' }} font-mono">/api/v1/sijuna/dudi</p>
                        <p class="text-[11px] {{ $dOk ? 'text-emerald-700' : 'text-slate-500' }}">{{ $diagDudi['message'] ?? 'Status endpoint DUDI belum diperiksa.' }}</p>
                    </div>
                </div>

                {{-- Status Sinkronisasi Terakhir (Titik ke-6) --}}
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700">6. Status Sinkronisasi Terakhir:</span>
                        <span class="text-slate-600">
                            @if ($lastSync)
                                Terakhir dijalankan {{ \Carbon\Carbon::parse($lastSync['ran_at'])->format('d M Y H:i') }} (Status: <strong class="{{ $lastSync['status'] === 'success' ? 'text-emerald-700' : ($lastSync['status'] === 'partial' ? 'text-amber-700' : 'text-rose-700') }}">{{ $lastSync['status'] === 'success' ? 'Berhasil' : ($lastSync['status'] === 'partial' ? 'Sebagian' : 'Gagal') }}</strong> · {{ $lastSync['message'] }})
                            @else
                                Belum pernah disinkronkan.
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Connection Diagnostics Alert (If actual gateway connection failure) --}}
        @if (in_array($connectionStatus, ['gateway_unreachable', 'authentication_failed', 'error']))
            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-5 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <div class="rounded-xl bg-rose-100 p-2.5 text-rose-600 shrink-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-rose-900">Diagnosis Gangguan Koneksi SiPintu</h3>
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700 uppercase tracking-wider">
                                {{ $connBadge['label'] }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-rose-800">{{ $connectionMessage }}</p>

                        @if (!empty($connectionDetail))
                            <div class="rounded-xl border border-rose-200/80 bg-white/80 p-3.5 text-xs text-rose-950 font-mono break-all">
                                <span class="font-bold text-rose-900 font-sans block mb-1">Penyebab / Detail Error API:</span>
                                {{ $connectionDetail }}
                            </div>
                        @endif

                        @if (!empty($connectionTroubleshooting))
                            <div class="flex items-start gap-2 text-xs font-medium text-rose-900 pt-1">
                                <svg class="h-4 w-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 2.625a3.375 3.375 0 00-3.75-3.375m-3.75 3.375a3.375 3.375 0 013.75-3.375m0 0V9a2.25 2.25 0 012.25-2.25h.375m-2.625 0H9.375A2.25 2.25 0 007.125 9v1.875" />
                                </svg>
                                <span><strong>Saran Perbaikan:</strong> {{ $connectionTroubleshooting }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif (in_array($connectionStatus, ['partial', 'warning', 'endpoint_unavailable']) && empty($endpointsDiagnosis))
            <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 shadow-sm">
                <div class="flex items-start gap-3.5">
                    <div class="rounded-xl bg-amber-100 p-2.5 text-amber-700 shrink-0">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-amber-900">Status Ketersediaan Layanan SiPintu</h3>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 uppercase tracking-wider">
                                {{ $connBadge['label'] }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-amber-900">{{ $connectionMessage }}</p>
                        @if (!empty($connectionDetail))
                            <p class="text-xs text-amber-800 font-mono">{{ $connectionDetail }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            {{-- Connection Status --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <div class="flex flex-col sm:flex-row gap-2 items-start justify-between">
                    <p class="text-xs font-semibold text-slate-500">Status Koneksi</p>
                    <span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $connBadge['class'] }}">{{ $connBadge['label'] }}</span>
                </div>
                <p class="mt-2 text-xs text-slate-600 line-clamp-2">{{ $connectionMessage }}</p>
                @if (!empty($connectionDetail))
                    <p class="mt-1 text-[11px] font-mono text-rose-600 truncate" title="{{ $connectionDetail }}">
                        Detail: {{ $connectionDetail }}
                    </p>
                @endif
            </article>

            {{-- Last Sync --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-slate-500">Sinkronisasi Terakhir</p>
                    @if ($lastSync && !empty($lastSync['entity_scope']))
                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
                            {{ $lastSync['entity_scope'] }}
                        </span>
                    @endif
                </div>
                @if ($lastSync)
                    @php
                        $lastSyncTime = $lastSync['finished_at'] ?? $lastSync['ran_at'] ?? null;
                    @endphp
                    <p class="mt-2 text-base font-bold text-slate-900">
                        {{ $lastSyncTime ? \Carbon\Carbon::parse($lastSyncTime)->format('d M Y H:i') : '-' }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-slate-500">
                        {{ $lastSync['status'] === 'success' ? 'Berhasil' : ($lastSync['status'] === 'partial' ? 'Sebagian' : 'Gagal') }}
                        · {{ number_format(($lastSync['duration_ms'] ?? 0) / 1000, 1) }} detik
                    </p>
                    <p class="mt-1 text-[11px] text-slate-500 truncate" title="{{ $lastSync['message'] ?? '' }}">
                        Remote {{ number_format((int) ($lastSync['remote_total'] ?? 0)) }} · +{{ number_format((int) ($lastSync['added'] ?? 0)) }} / ~{{ number_format((int) ($lastSync['updated'] ?? 0)) }} · Skip {{ number_format((int) ($lastSync['skipped'] ?? 0)) }}
                    </p>
                @else
                    <p class="mt-2 text-xs text-slate-500">Belum ada sinkronisasi.</p>
                @endif
            </article>

            {{-- SIPKL Students --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-semibold text-slate-500">Total Siswa SIPKL</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ number_format($localStudentCount) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    @if ($sipintuStudentCount !== null)
                        SiPintu: {{ number_format($sipintuStudentCount, 0, ',', '.') }}
                    @else
                        Data siswa lokal tersimpan
                    @endif
                    @if (($needsMappingCount ?? 0) > 0)
                        · <span class="font-semibold text-amber-700">{{ number_format($needsMappingCount) }} perlu pemetaan</span>
                    @endif
                </p>
            </article>

            {{-- SIPKL Teachers --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-semibold text-slate-500">Total Guru SIPKL</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ number_format($localTeacherCount) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    @if ($sipintuTeacherCount !== null)
                        SiPintu: {{ number_format($sipintuTeacherCount, 0, ',', '.') }} Guru
                    @else
                        Data guru lokal tersimpan
                    @endif
                </p>
            </article>

            {{-- SIPKL DUDI --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-semibold text-slate-500">Total DUDI SIPKL</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ number_format($localDudiCount ?? 0) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">
                    @if ($sipintuDudiCount !== null)
                        SiPintu: {{ number_format($sipintuDudiCount, 0, ',', '.') }} DUDI
                    @else
                        Data DUDI lokal tersimpan
                    @endif
                </p>
            </article>
        </div>

        {{-- Status Integrasi Entitas Terpisah (Siswa, Guru, DUDI) --}}
        @php
            $studentEndpoint = is_array($endpointsDiagnosis['students'] ?? null) ? $endpointsDiagnosis['students'] : null;
            $teacherEndpoint = is_array($endpointsDiagnosis['teachers'] ?? null) ? $endpointsDiagnosis['teachers'] : null;
            $dudiEndpoint = is_array($endpointsDiagnosis['dudi'] ?? null) ? $endpointsDiagnosis['dudi'] : null;

            $studentEndpointChecked = $studentEndpoint !== null;
            $teacherEndpointChecked = $teacherEndpoint !== null;
            $dudiEndpointChecked = $dudiEndpoint !== null;

            $studentEndpointAvailable = $studentEndpointChecked && ! empty($studentEndpoint['status']);
            $teacherEndpointAvailable = $teacherEndpointChecked && ! empty($teacherEndpoint['status']);
            $dudiEndpointAvailable = $dudiEndpointChecked && ! empty($dudiEndpoint['status']);

            $studentHttpStatus = $studentEndpoint['http_status'] ?? null;
            $teacherHttpStatus = $teacherEndpoint['http_status'] ?? null;
            $dudiHttpStatus = $dudiEndpoint['http_status'] ?? null;

            $studentErrorType = $studentEndpoint['error_type'] ?? null;
            $teacherErrorType = $teacherEndpoint['error_type'] ?? null;
            $dudiErrorType = $dudiEndpoint['error_type'] ?? null;

            $effectiveSipintuStudents = $studentEndpointAvailable
                ? ($sipintuStudentCount ?? (isset($studentEndpoint['count']) && is_numeric($studentEndpoint['count']) ? (int) $studentEndpoint['count'] : null))
                : ($studentEndpointChecked ? null : $sipintuStudentCount);
            $effectiveSipintuTeachers = $teacherEndpointAvailable
                ? ($sipintuTeacherCount ?? (isset($teacherEndpoint['count']) && is_numeric($teacherEndpoint['count']) ? (int) $teacherEndpoint['count'] : null))
                : ($teacherEndpointChecked ? null : $sipintuTeacherCount);
            $effectiveSipintuDudis = $dudiEndpointAvailable
                ? ($sipintuDudiCount ?? (isset($dudiEndpoint['count']) && is_numeric($dudiEndpoint['count']) ? (int) $dudiEndpoint['count'] : null))
                : ($dudiEndpointChecked ? null : $sipintuDudiCount);

            $studentCanSync = $studentEndpointAvailable || (! $studentEndpointChecked && in_array($connectionStatus, ['ready', 'connected', 'partial'], true));
        @endphp
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Status Integrasi Entitas SiPintu</h2>
                    <p class="text-xs text-slate-500">Pemantauan ketersediaan endpoint real-time, perbandingan data SiPintu vs lokal, dan tombol aksi per entitas</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-1 lg:grid-cols-3">
                {{-- Card A: SISWA --}}
                <div class="rounded-2xl border {{ $studentEndpointAvailable ? 'border-emerald-200 bg-emerald-50/30' : ($studentEndpointChecked ? 'border-rose-200 bg-rose-50/30' : 'border-slate-200 bg-white') }} p-5 shadow-card-sm flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $studentEndpointAvailable ? 'bg-emerald-100 text-emerald-700' : ($studentEndpointChecked ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">1. Data Siswa</h3>
                                    <p class="text-[11px] font-mono {{ $studentEndpointAvailable ? 'text-emerald-800' : 'text-slate-600' }}">/api/v1/sijuna/students</p>
                                </div>
                            </div>
                            @if ($studentEndpointAvailable)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-300">
                                    Tersedia{{ $studentHttpStatus !== null ? ' (HTTP '.$studentHttpStatus.')' : '' }}
                                </span>
                            @elseif ($studentEndpointChecked)
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800 ring-1 ring-rose-300">
                                    {{ $studentErrorType === 'timeout' ? 'Timeout' : ($studentHttpStatus !== null ? 'Tidak Tersedia (HTTP '.$studentHttpStatus.')' : 'Tidak Terhubung') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-300">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ $studentEndpoint['message'] ?? 'Status endpoint Siswa belum diperiksa. Klik "Test Connection" atau muat ulang halaman untuk memeriksa ketersediaan live.' }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-white/80 p-3 border border-slate-200/80 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Data dari SiPintu:</span>
                                <strong class="{{ $studentEndpointAvailable ? 'text-emerald-700' : 'text-slate-700' }} text-sm font-extrabold">
                                    @if ($effectiveSipintuStudents !== null)
                                        {{ number_format($effectiveSipintuStudents, 0, ',', '.') }} Siswa
                                    @elseif ($studentEndpointChecked)
                                        Tidak Tersedia
                                    @else
                                        Belum Dicek
                                    @endif
                                </strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Data Lokal SIMONGAN:</span>
                                <strong class="text-slate-800 text-sm font-extrabold">{{ number_format($localStudentCount) }} Siswa</strong>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200/70 flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="{{ $studentEndpointAvailable ? 'text-emerald-700' : 'text-slate-600' }} font-semibold flex items-center gap-1.5">
                                @if ($studentEndpointAvailable)
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                @else
                                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                @endif
                                @if (($needsMappingCount ?? 0) > 0)
                                    {{ number_format($needsMappingCount) }} Perlu Pemetaan Kelas
                                @elseif ($studentEndpointAvailable)
                                    Siap Disinkronkan
                                @elseif ($studentEndpointChecked)
                                    {{ $studentErrorType === 'timeout' ? 'Endpoint Timeout' : 'Endpoint Tidak Tersedia' }}
                                @else
                                    Belum Dicek
                                @endif
                            </span>
                            <span class="text-[11px] text-slate-400">{{ number_format($classroomMappingCount) }} Kelas Terpetakan</span>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            @if ($studentCanSync)
                                <a href="{{ route('admin.sipintu-sync.preview', ['entity' => 'siswa']) }}" class="flex-1 text-center py-2 px-3 rounded-xl bg-white border border-emerald-200 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition shadow-xs">
                                    Preview Siswa
                                </a>
                                <button type="button" @click="startSync('siswa')" class="flex-1 py-2 px-3 rounded-xl bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-xs">
                                    Sinkronkan Siswa
                                </button>
                            @else
                                <button type="button" disabled title="Endpoint /api/v1/sijuna/students {{ $studentErrorType === 'timeout' ? 'melebihi batas waktu (Timeout)' : 'tidak tersedia ('.($studentHttpStatus !== null ? 'HTTP '.$studentHttpStatus : 'Belum Dicek').')' }}" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                    Preview Siswa
                                </button>
                                <button type="button" disabled title="Endpoint /api/v1/sijuna/students {{ $studentErrorType === 'timeout' ? 'melebihi batas waktu (Timeout)' : 'tidak tersedia' }}" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                    Sinkronkan Siswa
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card B: GURU --}}
                <div class="rounded-2xl border {{ $teacherEndpointAvailable ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-200 bg-amber-50/30' }} p-5 shadow-card-sm flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $teacherEndpointAvailable ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">2. Data Guru</h3>
                                    <p class="text-[11px] font-mono {{ $teacherEndpointAvailable ? 'text-emerald-800' : 'text-amber-900' }}">/api/v1/sijuna/teachers</p>
                                </div>
                            </div>
                            @if ($teacherEndpointAvailable)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-300">
                                    Tersedia{{ $teacherHttpStatus !== null ? ' (HTTP '.$teacherHttpStatus.')' : '' }}
                                </span>
                            @elseif ($teacherEndpointChecked)
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900 ring-1 ring-amber-300">
                                    {{ $teacherErrorType === 'timeout' ? 'Timeout' : ($teacherHttpStatus !== null ? 'Belum Tersedia (HTTP '.$teacherHttpStatus.')' : 'Tidak Terhubung') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-300">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ $teacherEndpoint['message'] ?? 'Status endpoint Guru belum diperiksa. Jalankan "Test Connection" atau muat ulang halaman untuk memeriksa.' }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-white/80 p-3 border border-amber-100 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Data dari SiPintu:</span>
                                <strong class="{{ $teacherEndpointAvailable ? 'text-emerald-700' : 'text-amber-800' }} text-sm font-extrabold">
                                    @if ($effectiveSipintuTeachers !== null)
                                        {{ number_format($effectiveSipintuTeachers, 0, ',', '.') }} Guru
                                    @elseif ($teacherEndpointChecked)
                                        Tidak Tersedia
                                    @else
                                        Belum Dicek
                                    @endif
                                </strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Data Lokal SIMONGAN:</span>
                                <strong class="text-slate-800 text-sm font-extrabold">{{ number_format($localTeacherCount) }} Guru</strong>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-amber-100/80 flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="{{ $teacherEndpointAvailable ? 'text-emerald-700' : 'text-amber-800' }} font-semibold flex items-center gap-1.5">
                                @if ($teacherEndpointAvailable)
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                    Siap Disinkronkan
                                @elseif ($teacherEndpointChecked)
                                    <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                    {{ $teacherErrorType === 'timeout' ? 'Endpoint Timeout' : 'Menunggu Endpoint Aktif' }}
                                @else
                                    Belum Dicek
                                @endif
                            </span>
                            <span class="text-[11px] text-emerald-700 font-medium">✓ Data Lokal Aman</span>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            @if ($teacherEndpointAvailable)
                                <a href="{{ route('admin.sipintu-sync.preview', ['entity' => 'guru']) }}" class="flex-1 text-center py-2 px-3 rounded-xl bg-white border border-emerald-200 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition shadow-xs">
                                    Preview Guru
                                </a>
                                <button type="button" @click="startSync('guru')" class="flex-1 py-2 px-3 rounded-xl bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-xs">
                                    Sinkronkan Guru
                                </button>
                            @else
                                <button type="button" disabled title="Endpoint /api/v1/sijuna/teachers {{ $teacherErrorType === 'timeout' ? 'timeout' : 'belum tersedia ('.($teacherHttpStatus !== null ? 'HTTP '.$teacherHttpStatus : 'Belum Dicek').')' }}" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                    Preview Guru
                                </button>
                                <button type="button" disabled title="Menunggu endpoint guru aktif di gateway SiPintu" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                    Sinkronkan Guru
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card C: DUDI --}}
                <div class="rounded-2xl border {{ $dudiEndpointAvailable ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/50' }} p-5 shadow-card-sm flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $dudiEndpointAvailable ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">3. Data Master DUDI</h3>
                                    <p class="text-[11px] font-mono {{ $dudiEndpointAvailable ? 'text-emerald-800' : 'text-slate-600' }}">/api/v1/sijuna/dudi</p>
                                </div>
                            </div>
                            @if ($dudiEndpointAvailable)
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-300">
                                    Tersedia{{ $dudiHttpStatus !== null ? ' (HTTP '.$dudiHttpStatus.')' : '' }}
                                </span>
                            @elseif ($dudiEndpointChecked)
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-300">
                                    {{ $dudiErrorType === 'timeout' ? 'Timeout' : ($dudiHttpStatus !== null ? 'Belum Tersedia (HTTP '.$dudiHttpStatus.')' : 'Tidak Terhubung') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 ring-1 ring-slate-300">
                                    Belum Dicek
                                </span>
                            @endif
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ $dudiEndpoint['message'] ?? 'Status endpoint Master DUDI belum diperiksa. Kebijakan sinkronisasi default: Link-only (hanya menautkan DUDI yang sudah dibuat Admin).' }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 rounded-xl bg-white/80 p-3 border border-slate-200 text-xs">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Data dari SiPintu:</span>
                                <strong class="{{ $dudiEndpointAvailable ? 'text-emerald-700' : 'text-slate-600' }} text-sm font-extrabold">
                                    @if ($effectiveSipintuDudis !== null)
                                        {{ number_format($effectiveSipintuDudis, 0, ',', '.') }} DUDI
                                    @elseif ($dudiEndpointChecked)
                                        Tidak Tersedia
                                    @else
                                        Belum Dicek
                                    @endif
                                </strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Data Lokal SIMONGAN:</span>
                                <strong class="text-slate-800 text-sm font-extrabold">{{ number_format($localDudiCount ?? 0) }} DUDI</strong>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs">
                            <a href="{{ route('admin.sipintu-dudi-mapping.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1">
                                <span>Kelola SSO DUDI (Tahap A)</span>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </a>
                            <span class="text-[11px] text-emerald-700 font-medium">✓ Link-Only Aman</span>
                        </div>
                        @if ($dudiEndpointAvailable)
                            <label class="flex items-start gap-2 rounded-lg bg-white/80 p-2 border border-slate-200 text-[11px] text-slate-600 cursor-pointer">
                                <input type="checkbox" x-model="createMissingDudi" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span>Konfirmasi buat akun DUDI baru jika belum terdaftar lokal (opsional; default link-only)</span>
                            </label>
                            <div class="flex items-center gap-2 pt-1">
                                <a :href="'{{ route('admin.sipintu-sync.preview', ['entity' => 'dudi']) }}' + (createMissingDudi ? '&create_missing_dudi=1' : '')" class="flex-1 text-center py-2 px-3 rounded-xl bg-white border border-emerald-200 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 transition shadow-xs">
                                    Preview DUDI
                                </a>
                                <button type="button" @click="startSync('dudi')" class="flex-1 py-2 px-3 rounded-xl bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-700 transition shadow-xs">
                                    Sinkronkan DUDI
                                </button>
                            </div>
                        @else
                            <div class="flex items-center gap-2 pt-1">
                                <button type="button" disabled title="Endpoint /api/v1/sijuna/dudi {{ $dudiErrorType === 'timeout' ? 'timeout' : 'belum tersedia ('.($dudiHttpStatus !== null ? 'HTTP '.$dudiHttpStatus : 'Belum Dicek').')' }}" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                    Preview DUDI
                                </button>
                                <button type="button" disabled title="Menunggu endpoint master data DUDI aktif di gateway SiPintu" class="flex-1 py-2 px-3 rounded-xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-400 cursor-not-allowed">
                                    Sinkronkan DUDI
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($connectionStatus === 'connected' && $sipintuStudentCount === 0 && $sipintuTeacherCount === 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                Belum ada data yang tersedia untuk disinkronkan dari SiPintu. Tombol sinkronisasi tetap dapat digunakan; hasil 0 data akan dicatat sebagai berhasil.
            </div>
        @endif

        @if ($classroomMappingCount === 0)
            <div class="rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800">
                Belum ada classroom mapping SiPintu. Data siswa yang memerlukan kelas akan dilewati dengan aman sampai pemetaan dibuat di
                <a href="{{ route('admin.sipintu-classroom-mapping.index') }}" class="font-semibold underline">Pemetaan Kelas SiPintu</a>.
            </div>
        @elseif (($needsMappingCount ?? 0) > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span>
                    <strong>{{ number_format($needsMappingCount) }} siswa SiPintu</strong> masih memerlukan pemetaan kelas ({{ number_format($classroomMappingCount) }} kelas sudah terpetakan). Siswa yang melewati tahap pemetaan akan diproses pada sinkronisasi berikutnya.
                </span>
                <a href="{{ route('admin.sipintu-classroom-mapping.index') }}" class="shrink-0 font-semibold underline text-amber-900">Kelola Pemetaan Kelas</a>
            </div>
        @else
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-5 py-3.5 text-sm text-emerald-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span>
                    Pemetaan Kelas SiPintu aktif: <strong>{{ number_format($classroomMappingCount) }} kelas</strong> terpetakan · <strong>0 siswa</strong> perlu pemetaan kelas.
                </span>
                <a href="{{ route('admin.sipintu-classroom-mapping.index') }}" class="shrink-0 font-semibold underline text-emerald-900">Kelola Pemetaan Kelas</a>
            </div>
        @endif

        {{-- Banner Pemetaan DUDI SiPintu --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700">Single Sign-On (SSO) DUDI</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 mt-1">Pemetaan Identitas Akun DUDI SiPintu</h3>
                <p class="text-xs text-slate-500 mt-0.5">Petakan identitas SIPINTU User ID ke data DUDI lokal dan kelola otorisasi akses Single Sign-On perwakilan industri secara manual.</p>
            </div>
            <a href="{{ route('admin.sipintu-dudi-mapping.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 transition shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
                <span>Kelola Pemetaan DUDI</span>
            </a>
        </div>

        {{-- Info Banner --}}
        <div class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold">Sinkronisasi data <span class="font-bold">Siswa</span>, <span class="font-bold">Guru</span>, dan <span class="font-bold">Master DUDI</span> berjalan per cakupan entitas yang dipilih.</p>
                <p class="mt-1 text-blue-700">NIS (Siswa), NIP (Guru), dan identitas SiPintu (DUDI) digunakan sebagai kunci unik. Kebijakan DUDI default adalah <em>Link-only</em> (hanya menautkan &amp; memperbarui DUDI yang sudah dibuat Admin). Password lokal, role Super Admin, koordinat geofence, jam kerja DUDI, dan penempatan PKL tidak pernah ditimpa.</p>
            </div>
        </div>

        {{-- Preview Result --}}
        @if ($preview)
            @php
                $includedPreview = $preview['included'] ?? ['students' => true, 'teachers' => true, 'dudis' => true];
                $previewColCount = count(array_filter($includedPreview));
                $previewGridClass = $previewColCount === 1 ? 'lg:grid-cols-1' : ($previewColCount === 2 ? 'lg:grid-cols-2' : 'lg:grid-cols-3');
            @endphp
            <div class="rounded-2xl border {{ $preview['success'] ? 'border-emerald-200 bg-emerald-50/50' : 'border-red-200 bg-red-50/50' }} shadow-card-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200/60 px-6 py-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Hasil Preview (Dry Run) — Cakupan: {{ strtoupper((string) ($preview['scope'] ?? 'all')) }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            @if ($preview['success'])
                                Data TIDAK diubah. Berikut klasifikasi perbandingan data SiPintu vs data lokal.
                            @else
                                {{ $preview['message'] }}
                            @endif
                        </p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ number_format($preview['duration_ms'] / 1000, 2) }} detik</span>
                </div>

                @if ($preview['success'])
                    <div class="grid gap-6 p-6 {{ $previewGridClass }}">
                        {{-- Student Preview --}}
                        @if (!empty($includedPreview['students']))
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Siswa</h3>
                                    @if (!empty($preview['students']['endpoint_unavailable']))
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                            Endpoint Belum Tersedia (HTTP {{ $preview['students']['http_status'] ?? 404 }})
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($preview['students']['endpoint_unavailable']))
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 space-y-2">
                                        <p class="text-xs font-bold text-amber-900">{{ $preview['students']['endpoint_message'] ?? 'Endpoint Siswa belum tersedia.' }}</p>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @php
                                            $studentItems = [
                                                'Total Remote' => $preview['students']['total_remote'] ?? 0,
                                                'Baru (Akan Ditambah)' => $preview['students']['baru'] ?? 0,
                                                'Akan Diperbarui' => $preview['students']['diperbarui'] ?? 0,
                                                'Tidak Berubah' => $preview['students']['tidak_berubah'] ?? 0,
                                                'Perlu Pemetaan Kelas' => $preview['students']['perlu_pemetaan'] ?? 0,
                                                'Duplikat' => $preview['students']['duplikat'] ?? 0,
                                                'Konflik' => $preview['students']['konflik'] ?? 0,
                                                'Tidak Valid' => $preview['students']['tidak_valid'] ?? 0,
                                                'Gagal' => $preview['students']['gagal'] ?? ($preview['students']['error'] ?? 0),
                                                'Tidak Ditemukan' => $preview['students']['tidak_ditemukan'] ?? 0,
                                            ];
                                        @endphp
                                        @foreach ($studentItems as $label => $value)
                                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                                <p class="text-2xl font-extrabold text-slate-900">{{ number_format((int) $value) }}</p>
                                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $label }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Teacher Preview --}}
                        @if (!empty($includedPreview['teachers']))
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Guru</h3>
                                    @if (!empty($preview['teachers']['endpoint_unavailable']))
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                                            Endpoint Belum Tersedia (HTTP {{ $preview['teachers']['http_status'] ?? 404 }})
                                        </span>
                                    @endif
                                </div>

                                @if (!empty($preview['teachers']['endpoint_unavailable']))
                                    <div class="rounded-2xl border border-amber-200 bg-amber-50/80 p-5 space-y-2.5">
                                        <div class="flex items-center gap-2 text-amber-900 font-bold text-sm">
                                            <svg class="h-4 w-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                            <span>Data Guru Tidak Tersedia di Gateway SiPintu</span>
                                        </div>
                                        <p class="text-xs text-amber-800">
                                            {{ $preview['teachers']['endpoint_message'] ?? 'Server SiPintu belum mendaftarkan route endpoint data guru (/api/v1/sijuna/teachers).' }}
                                        </p>
                                        <p class="text-xs text-amber-800 font-semibold">
                                            ✓ Data guru lokal SIMONGAN tetap aman dan tidak akan diubah atau dihapus saat sinkronisasi dijalankan.
                                        </p>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @php
                                            $teacherItems = [
                                                'Total Remote' => $preview['teachers']['total_remote'] ?? 0,
                                                'Baru (Akan Ditambah)' => $preview['teachers']['baru'] ?? 0,
                                                'Akan Diperbarui' => $preview['teachers']['diperbarui'] ?? 0,
                                                'Tidak Berubah' => $preview['teachers']['tidak_berubah'] ?? 0,
                                                'Dilewati' => $preview['teachers']['dilewati'] ?? 0,
                                                'Duplikat' => $preview['teachers']['duplikat'] ?? 0,
                                                'Konflik' => $preview['teachers']['konflik'] ?? 0,
                                                'Tidak Valid' => $preview['teachers']['tidak_valid'] ?? 0,
                                                'Gagal' => $preview['teachers']['gagal'] ?? ($preview['teachers']['error'] ?? 0),
                                                'Tidak Ditemukan' => $preview['teachers']['tidak_ditemukan'] ?? 0,
                                            ];
                                        @endphp
                                        @foreach ($teacherItems as $label => $value)
                                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                                <p class="text-2xl font-extrabold text-slate-900">{{ number_format((int) $value) }}</p>
                                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $label }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- DUDI Preview --}}
                        @if (!empty($includedPreview['dudis']))
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">DUDI (Industri)</h3>
                                    @if (!empty($preview['dudis']['endpoint_unavailable']))
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-700">
                                            Endpoint Belum Tersedia (HTTP {{ $preview['dudis']['http_status'] ?? 404 }})
                                        </span>
                                    @endif
                                </div>

                                @if (!empty($preview['dudis']['endpoint_unavailable']))
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 space-y-2.5">
                                        <div class="flex items-center gap-2 text-slate-900 font-bold text-sm">
                                            <svg class="h-4 w-4 text-slate-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                            <span>Master Data DUDI Belum Dibuka di SiPintu</span>
                                        </div>
                                        <p class="text-xs text-slate-600">
                                            {{ $preview['dudis']['endpoint_message'] ?? 'Server SiPintu belum membuka endpoint master data DUDI (/api/v1/sijuna/dudi).' }}
                                        </p>
                                        <p class="text-xs text-emerald-800 font-semibold">
                                            ✓ {{ number_format($localDudiCount ?? 0) }} data DUDI lokal dan seluruh penempatan PKL aktif tetap dipertahankan dengan aman.
                                        </p>
                                        <div class="pt-2 border-t border-slate-200/80 text-[11px] text-slate-500">
                                            Autentikasi akun DUDI dikelola melalui fitur <a href="{{ route('admin.sipintu-dudi-mapping.index') }}" class="text-blue-600 font-semibold hover:underline">Pemetaan Identitas SSO DUDI (Tahap A)</a>.
                                        </div>
                                    </div>
                                @else
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @php
                                            $dudiItems = [
                                                'Total Remote' => $preview['dudis']['total_remote'] ?? 0,
                                                'Baru (Akan Ditambah)' => $preview['dudis']['baru'] ?? 0,
                                                'Akan Ditautkan / Diperbarui' => $preview['dudis']['diperbarui'] ?? 0,
                                                'Tidak Berubah' => $preview['dudis']['tidak_berubah'] ?? 0,
                                                'Dilewati (Belum Ada Lokal)' => $preview['dudis']['dilewati'] ?? 0,
                                                'Duplikat' => $preview['dudis']['duplikat'] ?? 0,
                                                'Konflik' => $preview['dudis']['konflik'] ?? 0,
                                                'Tidak Valid' => $preview['dudis']['tidak_valid'] ?? 0,
                                                'Gagal' => $preview['dudis']['gagal'] ?? ($preview['dudis']['error'] ?? 0),
                                                'Tidak Ditemukan' => $preview['dudis']['tidak_ditemukan'] ?? 0,
                                            ];
                                        @endphp
                                        @foreach ($dudiItems as $label => $value)
                                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                                <p class="text-2xl font-extrabold text-slate-900">{{ number_format((int) $value) }}</p>
                                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $label }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        {{-- History Table --}}
        <div id="riwayat-sinkronisasi" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm scroll-mt-6">
            <div class="flex flex-col sm:flex-row gap-4 sm: sm: border-b border-slate-200 px-6 py-5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Riwayat Sinkronisasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Log aktivitas sinkronisasi data siswa</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Admin &amp; Cakupan</th>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Remote</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Siswa Ditambah</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Siswa Diperbarui</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Tidak Berubah</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Dilewati / Bermasalah</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Guru (+ / ~)</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">DUDI (+ / ~)</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($history as $log)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                    <div>{{ ($log->started_at ?? $log->created_at)?->format('d M Y H:i:s') }}</div>
                                    @if ($log->finished_at)
                                        <div class="text-[11px] text-slate-400">s/d {{ $log->finished_at->format('H:i:s') }}</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-800">
                                    <div>{{ $log->admin_name ?? 'Sistem (terjadwal)' }}</div>
                                    <span class="mt-0.5 inline-block rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-600">
                                        {{ $log->entity_scope ?? 'all' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($log->status === 'success')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Berhasil</span>
                                    @elseif ($log->status === 'partial')
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800" title="{{ $log->message }}">Sebagian</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-bold text-red-800" title="{{ $log->message }}">Gagal</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-sm font-semibold text-slate-700">{{ number_format((int) ($log->remote_total ?? 0)) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-emerald-700 font-semibold">{{ number_format($log->added) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-blue-700 font-semibold">{{ number_format($log->updated) }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-slate-600">{{ number_format((int) ($log->unchanged ?? 0)) }}</td>
                                <td class="px-4 py-4 text-center text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ number_format((int) $log->skipped) }}</div>
                                    @if ($log->needs_mapping > 0 || $log->conflicts > 0 || $log->duplicates > 0 || $log->invalid > 0 || $log->failed > 0)
                                        <div class="mt-1 flex flex-wrap justify-center gap-1 text-[10px]">
                                            @if ($log->needs_mapping > 0)
                                                <span class="rounded bg-amber-100 px-1.5 py-0.5 text-amber-800">{{ $log->needs_mapping }} mapping</span>
                                            @endif
                                            @if ($log->conflicts > 0)
                                                <span class="rounded bg-orange-100 px-1.5 py-0.5 text-orange-800">{{ $log->conflicts }} konflik</span>
                                            @endif
                                            @if ($log->duplicates > 0)
                                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-700">{{ $log->duplicates }} duplikat</span>
                                            @endif
                                            @if ($log->invalid > 0)
                                                <span class="rounded bg-rose-100 px-1.5 py-0.5 text-rose-800">{{ $log->invalid }} tidak valid</span>
                                            @endif
                                            @if ($log->failed > 0)
                                                <span class="rounded bg-red-100 px-1.5 py-0.5 text-red-800">{{ $log->failed }} gagal</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-slate-600">
                                    +{{ number_format($log->teacher_added) }} / ~{{ number_format($log->teacher_updated) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-center text-sm text-slate-600">
                                    +{{ number_format((int) ($log->dudi_added ?? 0)) }} / ~{{ number_format((int) ($log->dudi_updated ?? 0)) }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm text-slate-600">{{ number_format($log->duration_ms / 1000, 1) }} s</td>
                            </tr>
                            @if (($log->status !== 'success' && $log->message) || (!empty($log->error_details) && is_array($log->error_details)))
                                <tr class="{{ $log->status === 'partial' ? 'bg-amber-50/50' : 'bg-red-50/40' }}">
                                    <td colspan="11" class="px-5 py-3 text-xs {{ $log->status === 'partial' ? 'text-amber-800' : 'text-red-700' }} space-y-1.5">
                                        @if ($log->message)
                                            <div class="font-medium">{{ $log->message }}</div>
                                        @endif
                                        @if (!empty($log->error_details) && is_array($log->error_details))
                                            <ul class="list-disc pl-4 space-y-0.5 text-[11px] font-mono">
                                                @foreach ($log->error_details as $issue)
                                                    @if (is_array($issue))
                                                        @php
                                                            $issueType = (string) ($issue['type'] ?? $issue['code'] ?? 'issue');
                                                            $issueDetail = (string) ($issue['detail'] ?? $issue['message'] ?? '');
                                                        @endphp
                                                        <li>
                                                            [{{ strtoupper((string) ($issue['entity'] ?? 'sync')) }}: {{ $issueType }}]
                                                            @if (!empty($issue['reference']))
                                                                ({{ $issue['reference'] }})
                                                            @elseif (!empty($issue['count']))
                                                                ({{ $issue['count'] }} data)
                                                            @endif
                                                            {{ $issueDetail }}
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="11">
                                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Belum ada riwayat sinkronisasi.</p>
                                        <p class="text-sm text-slate-500">Klik "Mulai Sinkronisasi" untuk menjalankan sinkronisasi pertama.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($history->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $history->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
