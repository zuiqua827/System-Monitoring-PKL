@extends('layouts.app')

@section('title', 'Pemetaan Akun DUDI SiPintu')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8" x-data="{
    mappingModalOpen: false,
    selectedDudi: null,
    sipintuUserId: '',
    sipintuId: '',
    sipintuEmail: '',
    openMappingModal(dudi) {
        this.selectedDudi = dudi;
        this.sipintuUserId = dudi.sipintu_user_id || '';
        this.sipintuId = dudi.sipintu_id || '';
        this.sipintuEmail = dudi.sipintu_email || '';
        this.mappingModalOpen = true;
    },
    closeMappingModal() {
        this.mappingModalOpen = false;
        this.selectedDudi = null;
    }
}">
    {{-- Modal Pemetaan Identitas SIPINTU --}}
    <div x-show="mappingModalOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeMappingModal()"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl border border-slate-100 z-10">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Pemetaan Identitas SiPintu</h3>
                        <p class="text-xs text-slate-500" x-text="selectedDudi ? selectedDudi.nama_perusahaan : ''"></p>
                    </div>
                </div>
                <button type="button" @click="closeMappingModal()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form :action="selectedDudi ? '{{ url('admin/sipintu-dudi-mapping') }}/' + selectedDudi.id : '#'" method="POST" class="mt-5 space-y-4">
                @csrf

                <div class="rounded-xl bg-amber-50 p-3.5 border border-amber-200/80 text-xs text-amber-900">
                    <p class="font-semibold mb-1">Ketentuan Keamanan & Identitas:</p>
                    <ul class="list-disc pl-4 space-y-0.5 text-amber-800">
                        <li><strong>SIPINTU User ID:</strong> Identitas akun pengguna SiPintu untuk autentikasi SSO perwakilan industri.</li>
                        <li><strong>ID Perusahaan SiPintu:</strong> Identitas master entitas industri untuk mencegah duplikasi saat sinkronisasi DUDI.</li>
                        <li>Satu ID SiPintu hanya boleh dipetakan ke <strong>satu</strong> DUDI lokal (1:1 strict).</li>
                        <li>Setelah pemetaan disimpan, akses SSO otomatis berstatus <strong>Nonaktif</strong> dan memerlukan aktivasi manual.</li>
                    </ul>
                </div>

                <div>
                    <label for="sipintu_user_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        SIPINTU User ID (Akun Login Industri / SSO)
                    </label>
                    <input
                        type="text"
                        name="sipintu_user_id"
                        id="sipintu_user_id"
                        x-model="sipintuUserId"
                        placeholder="Contoh: 1042 atau usr_abc123"
                        class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm font-mono text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                    >
                    <p class="mt-1 text-xs text-slate-500">ID numerik atau alfanumerik akun SiPintu yang mewakili industri ini saat login SSO.</p>
                </div>

                <div>
                    <label for="sipintu_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        ID Perusahaan SiPintu (Master Company ID)
                    </label>
                    <input
                        type="text"
                        name="sipintu_id"
                        id="sipintu_id"
                        x-model="sipintuId"
                        placeholder="Contoh: 52 atau dudi_xyz99"
                        class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm font-mono text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                    >
                    <p class="mt-1 text-xs text-slate-500">ID unik perusahaan di SiPintu untuk sinkronisasi master DUDI jika API tersedia.</p>
                </div>

                <div>
                    <label for="sipintu_email" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Email Akun SiPintu (Opsional)
                    </label>
                    <input
                        type="email"
                        name="sipintu_email"
                        id="sipintu_email"
                        x-model="sipintuEmail"
                        placeholder="perwakilan@industri.com"
                        class="mt-1.5 w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                    >
                    <p class="mt-1 text-xs text-slate-500">Digunakan untuk informasi, verifikasi silang, dan audit.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="closeMappingModal()" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 transition">
                        Simpan Pemetaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Header Section --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Integrasi & SSO</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pemetaan Akun DUDI SiPintu</h1>
                <p class="mt-2 text-sm text-slate-500">Hubungkan identitas akun pengguna SiPintu dengan data DUDI lokal agar perwakilan industri dapat login melalui SSO.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.sipintu-sync.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Dashboard SiPintu</span>
                </a>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total DUDI Lokal</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ number_format($totalDudi) }}</p>
                <p class="mt-1 text-xs text-slate-500">Profil perusahaan terdaftar</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Terhubung SiPintu</p>
                <p class="mt-2 text-2xl font-extrabold text-blue-600">{{ number_format($connectedDudi) }}</p>
                <p class="mt-1 text-xs text-slate-500">Memiliki SIPINTU User ID</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Belum Terhubung</p>
                <p class="mt-2 text-2xl font-extrabold text-amber-600">{{ number_format($unconnectedDudi) }}</p>
                <p class="mt-1 text-xs text-slate-500">Perlu dipetakan manual</p>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">SSO Aktif</p>
                <p class="mt-2 text-2xl font-extrabold text-emerald-600">{{ number_format($ssoActive) }}</p>
                <p class="mt-1 text-xs text-slate-500">Izin login SSO diaktifkan</p>
            </article>
        </div>

        {{-- Security Advisory Notice --}}
        <div class="flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4.5 text-sm text-blue-900">
            <svg class="h-5 w-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            <div class="space-y-1">
                <p class="font-bold">Prinsip Keamanan Pemetaan Identitas DUDI:</p>
                <p class="text-xs text-blue-800">
                    1. <strong>Identifikasi Berbasis User ID:</strong> Autentikasi SSO mencocokkan identitas pengguna akun SiPintu secara ketat, bukan berdasarkan kemiripan nama perusahaan.<br>
                    2. <strong>Aktivasi Manual Super Admin:</strong> Akun DUDI yang baru dipetakan atau baru saja diubah pemetaannya wajib diverifikasi dan diaktifkan manual oleh Super Admin melalui tombol <em>Aktivasi SSO</em>.<br>
                    3. <strong>Perlindungan Data PKL:</strong> Memetakan atau membatalkan pemetaan tidak akan menghapus data DUDI lokal, akun login lokal, penempatan siswa, maupun penilaian yang sudah ada.
                </p>
            </div>
        </div>

        {{-- Filter and Search Controls --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-1.5 rounded-xl bg-slate-100 p-1">
                <a href="{{ route('admin.sipintu-dudi-mapping.index', ['status' => 'all', 'search' => $search]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'all' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Semua ({{ $totalDudi }})
                </a>
                <a href="{{ route('admin.sipintu-dudi-mapping.index', ['status' => 'mapped', 'search' => $search]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'mapped' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Terhubung ({{ $connectedDudi }})
                </a>
                <a href="{{ route('admin.sipintu-dudi-mapping.index', ['status' => 'unmapped', 'search' => $search]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'unmapped' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    Belum Terhubung ({{ $unconnectedDudi }})
                </a>
                <a href="{{ route('admin.sipintu-dudi-mapping.index', ['status' => 'sso_active', 'search' => $search]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold transition {{ $status === 'sso_active' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                    SSO Aktif ({{ $ssoActive }})
                </a>
            </div>

            <form method="GET" action="{{ route('admin.sipintu-dudi-mapping.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari DUDI / User ID..."
                        class="w-full sm:w-64 rounded-xl border-slate-200 pl-9 pr-3.5 py-2 text-xs text-slate-700 placeholder-slate-400 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                    >
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </div>
                <button type="submit" class="rounded-xl bg-slate-800 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-slate-900 transition">
                    Cari
                </button>
                @if ($search !== '')
                    <a href="{{ route('admin.sipintu-dudi-mapping.index', ['status' => $status]) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        {{-- Main Table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-base font-bold text-slate-900">Daftar DUDI & Identitas SiPintu</h2>
                <p class="text-xs text-slate-500">Kelola pemetaan identitas eksternal dan kontrol otorisasi Single Sign-On</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider">Perusahaan DUDI</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider">Akun Login SIMONGAN</th>
                            <th class="px-6 py-3.5 text-left text-xs font-bold uppercase tracking-wider">Identitas Akun SiPintu</th>
                            <th class="px-6 py-3.5 text-center text-xs font-bold uppercase tracking-wider">Status SSO</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($dudis as $dudi)
                            <tr class="transition hover:bg-slate-50/70">
                                {{-- Nama Perusahaan & Penanggung Jawab --}}
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ $dudi->nama_perusahaan }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        PJ: {{ $dudi->penanggung_jawab ?: '—' }} · Telp: {{ $dudi->no_telepon ?: '—' }}
                                    </div>
                                    @if (!$dudi->status_aktif)
                                        <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700 ring-1 ring-rose-200 mt-1">
                                            Operasional Nonaktif
                                        </span>
                                    @endif
                                </td>

                                {{-- Akun Login SIMONGAN --}}
                                <td class="px-6 py-4">
                                    <div class="text-xs font-medium text-slate-800">{{ $dudi->user?->email ?: ($dudi->email_perusahaan ?: '—') }}</div>
                                    <div class="text-[11px] text-slate-400 mt-0.5">
                                        @if ($dudi->user_id !== null)
                                            User ID Lokal: {{ $dudi->user_id }}
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200">DUDI tanpa akun lokal</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Identitas SiPintu --}}
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        @if ($dudi->sipintu_user_id)
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200">
                                                    <span class="text-[10px] text-blue-500 uppercase font-sans">SSO ID:</span> {{ $dudi->sipintu_user_id }}
                                                </span>
                                            </div>
                                        @endif
                                        @if ($dudi->sipintu_id)
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-purple-700 bg-purple-50 px-2 py-0.5 rounded-md border border-purple-200">
                                                    <span class="text-[10px] text-purple-500 uppercase font-sans">Master ID:</span> {{ $dudi->sipintu_id }}
                                                </span>
                                            </div>
                                        @endif
                                        @if (! $dudi->sipintu_user_id && ! $dudi->sipintu_id)
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">
                                                Belum Dipetakan
                                            </span>
                                        @endif
                                        @if ($dudi->sipintu_email)
                                            <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-xs" title="{{ $dudi->sipintu_email }}">
                                                {{ $dudi->sipintu_email }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Status SSO --}}
                                <td class="px-6 py-4 text-center">
                                    @if ($dudi->sso_enabled)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                                {{-- Aksi --}}
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        {{-- Tombol Petakan / Edit --}}
                                        <button
                                            type="button"
                                            @click="openMappingModal({{ json_encode($dudi) }})"
                                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-blue-600 transition"
                                            title="Atur Pemetaan SiPintu"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                            <span>{{ ($dudi->sipintu_user_id || $dudi->sipintu_id) ? 'Ubah' : 'Petakan' }}</span>
                                        </button>

                                        {{-- Tombol Toggle SSO --}}
                                        @if ($dudi->sipintu_user_id)
                                            <form method="POST" action="{{ route('admin.sipintu-dudi-mapping.toggle-sso', $dudi) }}" class="inline">
                                                @csrf
                                                @if ($dudi->sso_enabled)
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition"
                                                        title="Nonaktifkan Akses SSO"
                                                    >
                                                        <span>Nonaktifkan SSO</span>
                                                    </button>
                                                @else
                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition"
                                                        title="Verifikasi & Aktifkan Akses SSO"
                                                    >
                                                        <span>Aktifkan SSO</span>
                                                    </button>
                                                @endif
                                            </form>
                                        @endif

                                        {{-- Tombol Putus Pemetaan (Unmap) --}}
                                        @if ($dudi->sipintu_user_id || $dudi->sipintu_id)
                                            <form method="POST" action="{{ route('admin.sipintu-dudi-mapping.destroy', $dudi) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan seluruh pemetaan SiPintu untuk DUDI ini? Akses SSO akan otomatis dinonaktifkan, tetapi data DUDI lokal dan penempatan PKL tetap aman.');">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-white px-2 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition"
                                                    title="Putus Pemetaan SiPintu"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                                    </svg>
                                                    <span>Putus</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="rounded-full bg-slate-100 p-3 text-slate-400">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">Tidak ada data DUDI yang ditemukan</p>
                                        <p class="text-xs text-slate-500">Coba ubah kata kunci pencarian atau filter status pemetaan.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($dudis->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $dudis->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
