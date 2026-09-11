@php
    /**
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $absensis
     * @var \App\Models\PenempatanPKL|null $penempatanAktif
     * @var \App\Models\Absensi|null $todayAbsensi
     * @var \App\Models\Siswa $siswa
     * @var array|null $watermarkData
     * @var bool $sudahCheckIn
     * @var bool $sudahCheckOut
     */
    use App\Enums\AbsensiStatus;
@endphp

@extends('layouts.app')

@section('title', 'Presensi Saya')

@section('content')
<div class="px-4 py-4 sm:px-6 sm:py-8 lg:px-8">
    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800 shadow-sm">{{ session('error') }}</div>
        @endif

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Siswa</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Presensi Saya</h1>
                <p class="mt-2 text-sm text-slate-500">Lakukan check-in dan check-out harian Anda</p>
            </div>
        </div>

        {{-- Card Check In / Check Out with Camera --}}
        @if($penempatanAktif)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
                <div class="p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Status Hari Ini</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Penempatan: <span class="font-semibold text-slate-700">{{ $penempatanAktif->dudi?->nama_perusahaan ?? '-' }}</span>
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            @if($todayAbsensi === null)
                                <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                    Belum Check In
                                </span>
                            @elseif($todayAbsensi->jam_keluar === null)
                                <div class="text-sm text-slate-500">
                                    <span class="font-bold text-emerald-600">✓ Check In</span>
                                    <span class="ml-1 font-medium">{{ $todayAbsensi->jam_masuk }}</span>
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1.5 text-sm font-bold text-blue-800">
                                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    Belum Check Out
                                </span>
                            @else
                                <div class="text-sm">
                                    <span class="font-bold text-emerald-600">✓ Check In</span>
                                    <span class="ml-1 text-slate-600">{{ $todayAbsensi->jam_masuk }}</span>
                                    <span class="mx-2 text-slate-400">|</span>
                                    <span class="font-bold text-orange-600">✓ Check Out</span>
                                    <span class="ml-1 text-slate-600">{{ $todayAbsensi->jam_keluar }}</span>
                                </div>
                                @php
                                    $sEnum = AbsensiStatus::tryFrom($todayAbsensi->status);
                                    $sColors = [
                                        'hadir' => 'bg-emerald-100 text-emerald-800',
                                        'terlambat' => 'bg-amber-100 text-amber-800',
                                        'izin' => 'bg-blue-100 text-blue-800',
                                        'sakit' => 'bg-orange-100 text-orange-800',
                                        'alpha' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusLabel = $todayAbsensi->status === 'terlambat' && $todayAbsensi->keterangan === 'Sangat Terlambat'
                                        ? 'Sangat Terlambat'
                                        : $sEnum?->label();
                                    $statusColor = $statusLabel === 'Sangat Terlambat'
                                        ? 'bg-red-100 text-red-800'
                                        : ($sColors[$todayAbsensi->status] ?? 'bg-slate-100 text-slate-800');
                                @endphp
                                @if($sEnum)
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $statusColor }}">
                                        {{ $statusLabel }}
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Pilih jenis Presensi sebelum membuka kamera --}}
                    @if($todayAbsensi === null || ($sudahCheckIn && !$sudahCheckOut))
                        <div class="mt-6 border-t border-slate-100 pt-6">
                            <div id="presensi-type-selection" class="rounded-2xl bg-slate-50 p-5">
                                <h4 class="text-sm font-bold text-slate-700">Pilih Jenis Presensi</h4>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" id="select-checkin" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50" @disabled($sudahCheckIn)>Check In</button>
                                    <button type="button" id="select-checkout" class="inline-flex items-center rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50" @disabled(!$sudahCheckIn || $sudahCheckOut)>Check Out</button>
                                </div>
                                @if(!$sudahCheckIn)
                                    <p class="mt-3 text-xs text-slate-500">Silakan lakukan Check In terlebih dahulu sebelum Check Out.</p>
                                @else
                                    <p class="mt-3 text-xs text-slate-500">Anda sudah melakukan Check In hari ini. Pilih Check Out untuk melanjutkan.</p>
                                @endif
                            </div>

                            <div id="camera-workspace" class="mt-6" style="display: none;">
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-1 sm:grid-cols-2">
                                {{-- Left: Camera Preview --}}
                                <div>
                                    <div class="relative overflow-hidden rounded-2xl bg-slate-900" style="min-height: 300px;">
                                        <video id="camera-preview" class="h-full w-full object-cover" style="display: none;" autoplay playsinline></video>
                                        <canvas id="camera-canvas" class="w-full" style="display: none;"></canvas>
                                        <img id="photo-preview" class="h-full w-full object-cover" style="display: none;" alt="Preview Foto">

                                        <div id="camera-placeholder" class="flex h-64 items-center justify-center" style="display: none;">
                                            <div class="text-center text-gray-400">
                                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                <p class="mt-2 text-sm">Mengakses kamera...</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="camera-status" class="mt-2 text-sm" style="display: none;"></div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <button type="button" id="btn-capture" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700" style="display: none;">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                                            Ambil Foto
                                        </button>
                                        <button type="button" id="btn-retake" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600" style="display: none;">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 1 9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 16v-4h4"/></svg>
                                            Ulang
                                        </button>
                                        <button type="button" id="btn-confirm" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" style="display: none;">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            Konfirmasi & {{ $todayAbsensi === null ? 'Check In' : 'Check Out' }}
                                        </button>
                                        <button type="button" id="btn-cancel-camera" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Batal</button>
                                    </div>
                                </div>

                                {{-- Right: GPS Status & Info --}}
                                <div id="camera-fallback-container" style="display: none;">
                                    <div class="space-y-3 rounded-2xl bg-slate-50 p-5">
                                        <h4 class="text-sm font-bold text-slate-700" style="display: none;">Informasi Lokasi</h4>

                                        <div class="flex items-center gap-2 text-sm" style="display: none;">
                                            <span class="font-medium text-slate-500">GPS:</span>
                                            <span id="gps-status" class="text-slate-400">Mendeteksi lokasi...</span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs" style="display: none;">
                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                <span class="text-slate-500">Latitude</span>
                                                <p id="gps-lat" class="mt-1 font-mono font-semibold text-slate-700">-</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                <span class="text-slate-500">Longitude</span>
                                                <p id="gps-lng" class="mt-1 font-mono font-semibold text-slate-700">-</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                <span class="text-slate-500">Akurasi</span>
                                                <p id="gps-accuracy" class="mt-1 font-mono font-semibold text-slate-700">-</p>
                                            </div>
                                            <div class="rounded-xl border border-slate-200 bg-white p-3">
                                                <span class="text-slate-500">Radius</span>
                                                <p id="radius-status" class="mt-1 font-mono font-semibold text-slate-700">100m</p>
                                            </div>
                                        </div>

                                        <div class="border-t border-slate-200 pt-3" style="display: none;">
                                            <p class="text-xs font-medium text-slate-500">Lokasi DUDI:</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-700">
                                                {{ $penempatanAktif->dudi->latitude ?? '-' }}, {{ $penempatanAktif->dudi->longitude ?? '-' }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $penempatanAktif->dudi->alamat ?? '-' }}</p>
                                        </div>

                                        <div class="border-t border-slate-200 pt-3" style="display: none;">
                                            <p class="text-xs font-medium text-slate-500">Foto akan diberi watermark:</p>
                                            <ul class="mt-1 space-y-0.5 text-xs text-slate-600">
                                                <li>• Nama: {{ $siswa->nama }}</li>
                                                <li>• DUDI: {{ $penempatanAktif->dudi?->nama_perusahaan ?? '-' }}</li>
                                                <li>• Tanggal & Jam otomatis</li>
                                                <li>• GPS Coordinates</li>
                                            </ul>
                                        </div>

                                        <div id="file-upload-fallback" class="border-t border-slate-200 pt-3" style="display: none;">
                                            <p class="mb-1 text-xs font-medium text-red-500">Kamera tidak tersedia. Upload foto manual:</p>
                                            <form method="POST" action="{{ $todayAbsensi === null ? route('siswa.absensi.check-in') : route('siswa.absensi.check-out') }}" enctype="multipart/form-data" class="flex flex-wrap items-center flex-wrap gap-2">
                                                @csrf
                                                <input type="file" name="{{ $todayAbsensi === null ? 'foto_masuk' : 'foto_pulang' }}" accept="image/*" class="text-sm text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700 hover:file:bg-blue-100" />
                                                <button type="submit" class="inline-flex items-center rounded-lg bg-{{ $todayAbsensi === null ? 'emerald' : 'orange' }}-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-{{ $todayAbsensi === null ? 'emerald' : 'orange' }}-700">
                                                    {{ $todayAbsensi === null ? 'Check In' : 'Check Out' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden form for camera capture submission --}}
                            <form id="camera-form" method="POST" action="{{ $todayAbsensi === null ? route('siswa.absensi.check-in') : route('siswa.absensi.check-out') }}" enctype="multipart/form-data" class="hidden">
                                @csrf
                                <input type="hidden" name="foto_base64" id="foto_base64" value="">
                                <input type="hidden" name="latitude" id="latitude" value="">
                                <input type="hidden" name="longitude" id="longitude" value="">
                                <input type="hidden" name="accuracy" id="accuracy" value="">
                                <input type="hidden" name="lokasi_masuk" id="lokasi_masuk" value="">
                                <input type="hidden" name="lokasi_pulang" id="lokasi_pulang" value="">
                            </form>
                            </div>
                        </div>
                    @elseif($sudahCheckIn && $sudahCheckOut)
                        <div class="mt-6 border-t border-slate-100 pt-6">
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                                    <svg class="h-7 w-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-bold text-emerald-800">Presensi Hari Ini Sudah Lengkap</p>
                                <p class="mt-1 text-xs text-emerald-600">
                                    Check In: {{ $todayAbsensi->jam_masuk }} | Check Out: {{ $todayAbsensi->jam_keluar }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-card-sm">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    <p class="text-sm font-medium text-amber-800">Anda belum memiliki penempatan PKL yang aktif. Silahkan hubungi admin.</p>
                </div>
            </div>
        @endif

        {{-- Rekap Presensi PKL --}}
        @if(isset($rekapPresensi))
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900">Rekap Presensi Selama Periode PKL</h3>
                <p class="mt-1 text-sm text-slate-500">Masa PKL: {{ $rekapPresensi['hari_berjalan'] }} hari berjalan dari total {{ $rekapPresensi['total_hari'] }} hari wajib.</p>
            </div>
            <div class="p-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-100">
                    <p class="text-sm font-semibold text-emerald-800">Hadir</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ count($rekapPresensi['hadir']) }}</p>
                    <div class="mt-2 space-y-1 text-xs text-emerald-700 max-h-32 overflow-y-auto">
                        @forelse($rekapPresensi['hadir'] as $ab)
                            <div>• {{ \Carbon\Carbon::parse($ab->tanggal)->format('d M Y') }}</div>
                        @empty
                            <div class="text-emerald-600/50 italic">-</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-xl bg-amber-50 p-4 border border-amber-100">
                    <p class="text-sm font-semibold text-amber-800">Terlambat</p>
                    <p class="mt-1 text-2xl font-bold text-amber-600">{{ count($rekapPresensi['terlambat']) }}</p>
                    <div class="mt-2 space-y-1 text-xs text-amber-700 max-h-32 overflow-y-auto">
                        @forelse($rekapPresensi['terlambat'] as $ab)
                            <div>• {{ \Carbon\Carbon::parse($ab->tanggal)->format('d M Y') }}</div>
                        @empty
                            <div class="text-amber-600/50 italic">-</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-xl bg-red-50 p-4 border border-red-100">
                    <p class="text-sm font-semibold text-red-800">Sangat Terlambat</p>
                    <p class="mt-1 text-2xl font-bold text-red-600">{{ count($rekapPresensi['sangat_terlambat']) }}</p>
                    <div class="mt-2 space-y-1 text-xs text-red-700 max-h-32 overflow-y-auto">
                        @forelse($rekapPresensi['sangat_terlambat'] as $ab)
                            <div>• {{ \Carbon\Carbon::parse($ab->tanggal)->format('d M Y') }}</div>
                        @empty
                            <div class="text-red-600/50 italic">-</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-xl bg-slate-50 p-4 border border-slate-200">
                    <p class="text-sm font-semibold text-slate-800">Tidak Hadir (Bolos)</p>
                    <p class="mt-1 text-2xl font-bold text-slate-600">{{ count($rekapPresensi['bolos']) }}</p>
                    <div class="mt-2 space-y-1 text-xs text-slate-600 max-h-32 overflow-y-auto">
                        @forelse($rekapPresensi['bolos'] as $date)
                            <div>• {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</div>
                        @empty
                            <div class="text-slate-500/50 italic">-</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-xl bg-orange-50 p-4 border border-orange-100">
                    <p class="text-sm font-semibold text-orange-800">Sakit</p>
                    <p class="mt-1 text-2xl font-bold text-orange-600">{{ count($rekapPresensi['sakit']) }}</p>
                    <div class="mt-2 space-y-1 text-xs text-orange-700 max-h-32 overflow-y-auto">
                        @forelse($rekapPresensi['sakit'] as $ab)
                            <div>• {{ \Carbon\Carbon::parse(is_object($ab) ? $ab->tanggal : $ab)->format('d M Y') }}</div>
                        @empty
                            <div class="text-orange-600/50 italic">-</div>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-xl bg-blue-50 p-4 border border-blue-100">
                    <p class="text-sm font-semibold text-blue-800">Izin</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ count($rekapPresensi['izin']) }}</p>
                    <div class="mt-2 space-y-1 text-xs text-blue-700 max-h-32 overflow-y-auto">
                        @forelse($rekapPresensi['izin'] as $ab)
                            <div>• {{ \Carbon\Carbon::parse(is_object($ab) ? $ab->tanggal : $ab)->format('d M Y') }}</div>
                        @empty
                            <div class="text-blue-600/50 italic">-</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Riwayat Presensi --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-bold text-slate-900">Riwayat Presensi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-14 px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">No</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jam Masuk</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Jam Pulang</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Lokasi</th>
                            <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($absensis as $index => $absensi)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ $absensis->firstItem() + $index }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-800">{{ $absensi->tanggal ? $absensi->tanggal->format('d/m/Y') : '-' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $absensi->jam_masuk ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ $absensi->jam_keluar ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    @php
                                        $sEnum = AbsensiStatus::tryFrom($absensi->status);
                                        $sColors = [
                                            'hadir' => 'bg-emerald-100 text-emerald-800',
                                            'terlambat' => 'bg-amber-100 text-amber-800',
                                            'izin' => 'bg-blue-100 text-blue-800',
                                            'sakit' => 'bg-orange-100 text-orange-800',
                                            'alpha' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusLabel = $absensi->status === 'terlambat' && $absensi->keterangan === 'Sangat Terlambat'
                                            ? 'Sangat Terlambat'
                                            : $sEnum?->label();
                                        $statusColor = $statusLabel === 'Sangat Terlambat'
                                            ? 'bg-red-100 text-red-800'
                                            : ($sColors[$absensi->status] ?? 'bg-slate-100 text-slate-800');
                                    @endphp
                                    @if($sEnum)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">{{ $absensi->status }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-xs text-slate-500">
                                    @if($absensi->latitude_masuk && $absensi->longitude_masuk)
                                        <span title="Lat: {{ $absensi->latitude_masuk }}, Lng: {{ $absensi->longitude_masuk }}">
                                            📍 {{ number_format((float)$absensi->latitude_masuk, 4) }}, {{ number_format((float)$absensi->longitude_masuk, 4) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <a href="{{ route('siswa.absensi.show', $absensi->id) }}" class="inline-flex items-center rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">Detail</a>
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
                                        <p class="text-sm font-semibold text-slate-700">
                                            Belum ada data Presensi.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($absensis->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $absensis->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelection = document.getElementById('presensi-type-selection');
        const cameraWorkspace = document.getElementById('camera-workspace');
        if (!typeSelection || !cameraWorkspace) return;

        let camera = null;
        const startCamera = function(mode) {
            typeSelection.style.display = 'none';
            cameraWorkspace.style.display = 'block';
            camera = new AbsensiCamera({
            videoId: 'camera-preview',
            canvasId: 'camera-canvas',
            photoInputId: 'foto_base64',
            latInputId: 'latitude',
            lngInputId: 'longitude',
            accuracyInputId: 'accuracy',
            statusId: 'camera-status',
            captureBtnId: 'btn-capture',
            retakeBtnId: 'btn-retake',
            confirmBtnId: 'btn-confirm',
            photoPreviewId: 'photo-preview',
            fileUploadId: 'file-upload-fallback',
            fileUploadContainerId: 'camera-fallback-container',
            mode: mode,
            watermarkData: @json($watermarkData ?? []),
            onComplete: function(data) {
                document.getElementById('foto_base64').value = data.foto_base64;
                document.getElementById('latitude').value = data.latitude || '';
                document.getElementById('longitude').value = data.longitude || '';
                document.getElementById('accuracy').value = data.accuracy || '';

                const lokasiField = mode === 'checkin' ? 'lokasi_masuk' : 'lokasi_pulang';
                const lokasiValue = data.latitude && data.longitude
                    ? `${data.latitude}, ${data.longitude}`
                    : '';
                document.getElementById(lokasiField).value = lokasiValue;

                document.getElementById('camera-form').submit();
            }
            });
        };

        const gpsStatus = document.getElementById('gps-status');
        const gpsLat = document.getElementById('gps-lat');
        const gpsLng = document.getElementById('gps-lng');
        const gpsAccuracy = document.getElementById('gps-accuracy');

        setInterval(function() {
            if (!camera) return;

            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            const acc = document.getElementById('accuracy').value;

            if (lat) {
                gpsLat.textContent = parseFloat(lat).toFixed(6);
                gpsLng.textContent = parseFloat(lng).toFixed(6);
                gpsAccuracy.textContent = acc ? parseFloat(acc).toFixed(0) + 'm' : '-';
                gpsStatus.textContent = '✅ Lokasi terdeteksi';
                gpsStatus.className = 'font-medium text-emerald-600';
            }
        }, 2000);

        function closeCamera() {
            if (camera) {
                camera.destroy();
                camera = null;
            }

            ['foto_base64', 'latitude', 'longitude', 'accuracy', 'lokasi_masuk', 'lokasi_pulang'].forEach(function(id) {
                document.getElementById(id).value = '';
            });

            document.getElementById('photo-preview').style.display = 'none';
            document.getElementById('camera-preview').style.display = 'none';
            document.getElementById('camera-canvas').style.display = 'none';
            document.getElementById('btn-capture').style.display = 'none';
            document.getElementById('btn-retake').style.display = 'none';
            document.getElementById('btn-confirm').style.display = 'none';
            document.getElementById('file-upload-fallback').style.display = 'none';
            document.getElementById('camera-fallback-container').style.display = 'none';
        }

        document.getElementById('select-checkin').addEventListener('click', function() {
            startCamera('checkin');
        });
        document.getElementById('select-checkout').addEventListener('click', function() {
            startCamera('checkout');
        });
        document.getElementById('btn-cancel-camera').addEventListener('click', function() {
            closeCamera();
            cameraWorkspace.style.display = 'none';
            typeSelection.style.display = 'block';
        });
        window.addEventListener('pagehide', closeCamera);
    });
</script>
@endpush
