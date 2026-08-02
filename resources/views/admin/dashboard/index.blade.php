@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
@php
    $stat = fn (string $key): int => (int) ($stats[$key] ?? 0);

    $totalGuru = $stat('total_guru');
    $totalSiswa = $stat('total_siswa');
    $totalDudi = $stat('total_dudi');
    $totalJurusan = $stat('total_jurusan');
    $totalKelas = $stat('total_kelas');
    $totalPeriodePkl = $stat('total_periode_pkl');
    $totalPenempatan = $stat('total_penempatan');
    $totalPklAktif = $stat('total_pkl_aktif');
    $totalPklSelesai = $stat('total_pkl_selesai');
    $totalAbsensiHariIni = $stat('total_absensi_hari_ini');
    $totalAktivitasHariIni = $stat('total_aktivitas_hari_ini');
    $totalPenilaian = $stat('total_penilaian');

    $attendanceTrend = $charts['attendance_trend'] ?? [];
    $attendanceStatus = $charts['attendance_status'] ?? [];
    $studentsPerDudi = $charts['students_per_dudi'] ?? [];
    $gradeDistribution = $charts['grade_distribution'] ?? [];
    $activityTrend = $charts['activity_trend'] ?? [];
    $monitoringData = $monitoring ?? [];
    $activities = $recentActivities ?? [];

    $placementCoverage = $totalSiswa > 0 ? round(($totalPenempatan / $totalSiswa) * 100) : 0;
    $dudiAverage = $totalDudi > 0 ? round($totalPenempatan / $totalDudi, 1) : 0;
    $placementOther = max($totalPenempatan - $totalPklAktif - $totalPklSelesai, 0);

    $buildConic = function (array $items, int $total): string {
        if ($total <= 0) {
            return 'conic-gradient(#e2e8f0 0deg 360deg)';
        }

        $start = 0.0;
        $segments = [];

        foreach ($items as $item) {
            $value = max((int) ($item['value'] ?? 0), 0);

            if ($value === 0) {
                continue;
            }

            $end = $start + (($value / $total) * 360);
            $segments[] = ($item['hex'] ?? '#e2e8f0').' '.$start.'deg '.$end.'deg';
            $start = $end;
        }

        if ($start < 360) {
            $segments[] = '#e2e8f0 '.$start.'deg 360deg';
        }

        return 'conic-gradient('.implode(', ', $segments).')';
    };

    $primaryCards = [
        [
            'label' => 'Total Siswa',
            'value' => $totalSiswa,
            'hint' => $placementCoverage.'% sudah ditempatkan',
            'icon' => 'students',
            'tone' => 'blue',
            'iconClass' => 'bg-blue-50 text-blue-600',
        ],
        [
            'label' => 'Total Guru',
            'value' => $totalGuru,
            'hint' => 'Pembimbing aktif terdata',
            'icon' => 'teacher',
            'tone' => 'violet',
            'iconClass' => 'bg-violet-50 text-violet-600',
        ],
        [
            'label' => 'Total DUDI',
            'value' => $totalDudi,
            'hint' => $dudiAverage.' rata-rata siswa/DUDI',
            'icon' => 'building',
            'tone' => 'orange',
            'iconClass' => 'bg-orange-50 text-orange-600',
        ],
        [
            'label' => 'Penempatan Aktif',
            'value' => $totalPklAktif,
            'hint' => $totalPenempatan.' total penempatan',
            'icon' => 'placement',
            'tone' => 'emerald',
            'iconClass' => 'bg-emerald-50 text-emerald-600',
        ],
    ];

    $secondaryCards = [
        ['label' => 'Jurusan', 'value' => $totalJurusan, 'route' => 'admin.jurusan.index'],
        ['label' => 'Kelas', 'value' => $totalKelas, 'route' => 'admin.kelas.index'],
        ['label' => 'Periode PKL', 'value' => $totalPeriodePkl, 'route' => 'admin.periode-pkl.index'],
        ['label' => 'Penilaian', 'value' => $totalPenilaian, 'route' => 'admin.penilaian.index'],
        ['label' => 'Absensi Hari Ini', 'value' => $totalAbsensiHariIni, 'route' => 'admin.absensi.index'],
        ['label' => 'Aktivitas Hari Ini', 'value' => $totalAktivitasHariIni, 'route' => 'admin.aktivitas.index'],
        ['label' => 'PKL Aktif', 'value' => $totalPklAktif, 'route' => 'admin.penempatan-pkl.index'],
        ['label' => 'PKL Selesai', 'value' => $totalPklSelesai, 'route' => 'admin.penempatan-pkl.index'],
    ];

    $placementItems = [
        ['label' => 'PKL Aktif', 'value' => $totalPklAktif, 'hex' => '#2563eb', 'dot' => 'bg-blue-600'],
        ['label' => 'PKL Selesai', 'value' => $totalPklSelesai, 'hex' => '#10b981', 'dot' => 'bg-emerald-500'],
        ['label' => 'Lainnya', 'value' => $placementOther, 'hex' => '#cbd5e1', 'dot' => 'bg-slate-300'],
    ];
    $placementGradient = $buildConic($placementItems, max($totalPenempatan, 0));

    $statusPalette = [
        'Hadir' => ['hex' => '#10b981', 'dot' => 'bg-emerald-500'],
        'Terlambat' => ['hex' => '#f59e0b', 'dot' => 'bg-amber-500'],
        'Izin' => ['hex' => '#3b82f6', 'dot' => 'bg-blue-500'],
        'Sakit' => ['hex' => '#f97316', 'dot' => 'bg-orange-500'],
        'Alpha' => ['hex' => '#ef4444', 'dot' => 'bg-red-500'],
    ];
    $statusItems = collect($attendanceStatus)->map(fn ($value, $label) => [
        'label' => $label,
        'value' => (int) $value,
        'hex' => $statusPalette[$label]['hex'] ?? '#94a3b8',
        'dot' => $statusPalette[$label]['dot'] ?? 'bg-slate-400',
    ])->values()->all();
    $statusTotal = array_sum(array_map('intval', array_values($attendanceStatus)));
    $statusGradient = $buildConic($statusItems, $statusTotal);

    $attendanceTotals = array_map(fn ($day) => (int) ($day['hadir'] ?? 0) + (int) ($day['terlambat'] ?? 0) + (int) ($day['izin'] ?? 0) + (int) ($day['sakit'] ?? 0) + (int) ($day['alpha'] ?? 0), $attendanceTrend);
    $maxAttendance = max(array_merge([1], $attendanceTotals));
    $activityTotals = array_map(fn ($day) => (int) ($day['total'] ?? 0), $activityTrend);
    $maxActivity = max(array_merge([1], $activityTotals));
    $maxDudi = max(array_merge([1], array_map(fn ($item) => (int) ($item['total'] ?? 0), $studentsPerDudi)));
    $gradeTotal = array_sum(array_map('intval', array_values($gradeDistribution)));

    $monitoringItems = [
        ['label' => 'Belum Check In', 'value' => (int) ($monitoringData['belum_check_in'] ?? 0), 'route' => 'admin.absensi.index', 'warn' => 'red'],
        ['label' => 'Belum Check Out', 'value' => (int) ($monitoringData['belum_check_out'] ?? 0), 'route' => 'admin.absensi.index', 'warn' => 'amber'],
        ['label' => 'Aktivitas Menunggu Validasi', 'value' => (int) ($monitoringData['aktivitas_menunggu_validasi'] ?? 0), 'route' => 'admin.aktivitas.index', 'warn' => 'amber'],
        ['label' => 'Penilaian Draft', 'value' => (int) ($monitoringData['penilaian_draft'] ?? 0), 'route' => 'admin.penilaian.index', 'warn' => 'orange'],
        ['label' => 'PKL Akan Berakhir (7 hari)', 'value' => (int) ($monitoringData['pkl_akan_berakhir'] ?? 0), 'route' => 'admin.penempatan-pkl.index', 'warn' => 'blue'],
        ['label' => 'PKL Terlambat Mulai', 'value' => (int) ($monitoringData['pkl_terlambat_mulai'] ?? 0), 'route' => 'admin.penempatan-pkl.index', 'warn' => 'red'],
    ];

    $activityRoutes = [
        'checkin' => 'admin.absensi.index',
        'checkout' => 'admin.absensi.index',
        'aktivitas' => 'admin.aktivitas.index',
        'penilaian' => 'admin.penilaian.index',
    ];
@endphp

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        {{-- Header Row --}}
        <div class="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Dashboard Super Admin</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Selamat datang, {{ auth()->user()->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500 sm:text-base">Ringkasan data sistem monitoring PKL hari ini.</p>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-card-sm">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4M5.5 9.5h13M7 20h10a2.5 2.5 0 0 0 2.5-2.5v-10A2.5 2.5 0 0 0 17 5H7a2.5 2.5 0 0 0-2.5 2.5v10A2.5 2.5 0 0 0 7 20Z" />
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Tanggal</p>
                    <p class="text-sm font-bold text-slate-800">{{ now()->format('d M Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Primary Stat Cards --}}
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($primaryCards as $card)
                <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm transition hover:-translate-y-0.5 hover:shadow-card-md">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($card['value']) }}</p>
                        </div>
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $card['iconClass'] }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                @switch($card['icon'])
                                    @case('teacher')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14.5c3.2 0 5.8-1.35 5.8-3V6.7L12 9.5 6.2 6.7v4.8c0 1.65 2.6 3 5.8 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5 12 2l8 3.5-8 3.5L4 5.5Zm4 11.8c-1.9.6-3 1.55-3 2.7h14c0-1.15-1.1-2.1-3-2.7" />
                                        @break
                                    @case('building')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16M6 21V5.8C6 4.8 6.8 4 7.8 4h8.4c1 0 1.8.8 1.8 1.8V21M9 8h1.5M13.5 8H15M9 12h1.5M13.5 12H15" />
                                        @break
                                    @case('placement')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-4.55 6-10a6 6 0 0 0-12 0c0 5.45 6 10 6 10Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                        @break
                                    @default
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 19c0-2.2-1.8-4-4-4s-4 1.8-4 4M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6.5 6.5c0-1.5-.8-2.75-2-3.45M5.5 18.5c0-1.5.8-2.75 2-3.45" />
                                @endswitch
                            </svg>
                        </span>
                    </div>
                    <p class="mt-4 text-sm text-slate-500">{{ $card['hint'] }}</p>
                </article>
            @endforeach
        </div>

        {{-- Secondary stat mini-cards --}}
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($secondaryCards as $card)
                <a href="{{ route($card['route']) }}" class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-card-sm transition hover:border-blue-200 hover:bg-blue-50/40 hover:shadow-card-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 group-hover:text-blue-600">{{ $card['label'] }}</p>
                    <p class="text-xl font-extrabold text-slate-900">{{ number_format($card['value']) }}</p>
                </a>
            @endforeach
        </div>

        {{-- Charts Row 1: Placement & Attendance --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-12">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm xl:col-span-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Status Penempatan</h2>
                        <p class="mt-1 text-sm text-slate-500">Total penempatan PKL</p>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-600">{{ number_format($totalPenempatan) }} data</span>
                </div>

                <div class="mt-8 flex items-center justify-center">
                    <div class="relative h-44 w-44 rounded-full" style="background: {{ $placementGradient }};">
                        <div class="absolute inset-5 rounded-full bg-white"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-extrabold text-slate-900">{{ number_format($totalPenempatan) }}</span>
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Total</span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 space-y-3">
                    @foreach ($placementItems as $item)
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2.5 text-slate-600">
                                <span class="h-2.5 w-2.5 rounded-full {{ $item['dot'] }}"></span>
                                <span class="font-medium">{{ $item['label'] }}</span>
                            </div>
                            <span class="font-bold text-slate-900">{{ number_format($item['value']) }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm xl:col-span-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Statistik Kehadiran PKL</h2>
                        <p class="mt-1 text-sm text-slate-500">7 hari terakhir dari data absensi</p>
                    </div>
                    <span class="self-start rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">7 Hari Terakhir</span>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-[1fr_220px]">
                    <div class="flex h-64 items-end gap-3 rounded-2xl bg-slate-50 px-4 py-5">
                        @forelse ($attendanceTrend as $day)
                            @php
                                $segments = [
                                    ['value' => (int) ($day['hadir'] ?? 0), 'color' => '#10b981'],
                                    ['value' => (int) ($day['terlambat'] ?? 0), 'color' => '#f59e0b'],
                                    ['value' => (int) ($day['izin'] ?? 0), 'color' => '#3b82f6'],
                                    ['value' => (int) ($day['sakit'] ?? 0), 'color' => '#f97316'],
                                    ['value' => (int) ($day['alpha'] ?? 0), 'color' => '#ef4444'],
                                ];
                                $dayTotal = array_sum(array_column($segments, 'value'));
                                $barHeight = $dayTotal > 0 ? max(10, round(($dayTotal / $maxAttendance) * 100)) : 4;
                                $label = \Carbon\Carbon::parse($day['tanggal'])->format('d/m');
                            @endphp
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                                <div class="flex h-44 w-full items-end justify-center">
                                    <div class="flex w-8 flex-col-reverse overflow-hidden rounded-t-lg bg-slate-200" style="height: {{ $barHeight }}%;">
                                        @foreach ($segments as $segment)
                                            @if ($dayTotal > 0 && $segment['value'] > 0)
                                                <span style="height: {{ ($segment['value'] / $dayTotal) * 100 }}%; background: {{ $segment['color'] }};"></span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                <span class="text-[11px] font-semibold text-slate-500">{{ $label }}</span>
                            </div>
                        @empty
                            <div class="flex h-full w-full items-center justify-center text-sm text-slate-500">Belum ada data kehadiran.</div>
                        @endforelse
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="mx-auto h-28 w-28 rounded-full" style="background: {{ $statusGradient }};"></div>
                        <div class="mt-5 space-y-2">
                            @forelse ($statusItems as $item)
                                <div class="flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-slate-600">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $item['dot'] }}"></span>
                                        <span class="font-medium">{{ $item['label'] }}</span>
                                    </div>
                                    <span class="font-bold text-slate-900">{{ number_format($item['value']) }}</span>
                                </div>
                            @empty
                                <p class="text-center text-sm text-slate-500">Belum ada status absensi.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        </div>

        {{-- Charts Row 2: DUDI, Activity, Grade --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h2 class="text-base font-bold text-slate-900">Siswa per DUDI</h2>
                <p class="mt-1 text-sm text-slate-500">Distribusi penempatan per mitra</p>

                <div class="mt-6 space-y-4">
                    @forelse (array_slice($studentsPerDudi, 0, 6) as $item)
                        @php
                            $total = (int) ($item['total'] ?? 0);
                            $width = round(($total / $maxDudi) * 100);
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-semibold text-slate-700">{{ $item['nama_perusahaan'] ?? 'DUDI' }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($total) }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ $width }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Belum ada penempatan per DUDI.</div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h2 class="text-base font-bold text-slate-900">Tren Aktivitas</h2>
                <p class="mt-1 text-sm text-slate-500">Aktivitas harian minggu ini</p>

                <div class="mt-8 flex h-56 items-end gap-3 rounded-2xl bg-slate-50 px-4 py-5">
                    @forelse ($activityTrend as $day)
                        @php
                            $total = (int) ($day['total'] ?? 0);
                            $height = $total > 0 ? max(10, round(($total / $maxActivity) * 100)) : 4;
                            $label = \Carbon\Carbon::parse($day['tanggal'])->format('D');
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-2">
                            <div class="flex h-36 w-full items-end justify-center">
                                <div class="w-8 rounded-t-lg bg-blue-600" style="height: {{ $height }}%;"></div>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-500">{{ $label }}</span>
                        </div>
                    @empty
                        <div class="flex h-full w-full items-center justify-center text-sm text-slate-500">Belum ada data aktivitas.</div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h2 class="text-base font-bold text-slate-900">Distribusi Nilai PKL</h2>
                <p class="mt-1 text-sm text-slate-500">{{ number_format($gradeTotal) }} penilaian final</p>

                <div class="mt-6 space-y-3">
                    @forelse ($gradeDistribution as $grade => $value)
                        @php
                            $value = (int) $value;
                            $width = $gradeTotal > 0 ? round(($value / $gradeTotal) * 100) : 0;
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between text-sm">
                                <span class="font-semibold text-slate-700">Predikat {{ $grade }}</span>
                                <span class="font-bold text-slate-900">{{ number_format($value) }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $width }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Belum ada penilaian final.</div>
                    @endforelse
                </div>
            </article>
        </div>

        {{-- Monitoring & Recent Activity --}}
        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card-sm">
                <h2 class="text-base font-bold text-slate-900">Ringkasan Monitoring</h2>
                <p class="mt-1 text-sm text-slate-500">Item yang membutuhkan perhatian</p>

                <div class="mt-6 space-y-3">
                    @foreach ($monitoringItems as $item)
                        @php
                            $hasIssue = $item['value'] > 0;
                            $badgeClass = $hasIssue
                                ? match ($item['warn']) {
                                    'red' => 'bg-red-50 text-red-700 ring-red-100',
                                    'orange' => 'bg-orange-50 text-orange-700 ring-orange-100',
                                    'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                                    default => 'bg-amber-50 text-amber-700 ring-amber-100',
                                }
                                : 'bg-emerald-50 text-emerald-700 ring-emerald-100';
                        @endphp
                        <a href="{{ route($item['route']) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50/40">
                            <span class="text-sm font-medium text-slate-600">{{ $item['label'] }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold ring-1 {{ $badgeClass }}">{{ number_format($item['value']) }}</span>
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Aktivitas Terbaru</h2>
                        <p class="mt-1 text-sm text-slate-500">Gabungan check-in, check-out, aktivitas, dan penilaian</p>
                    </div>
                    <a href="{{ route('admin.aktivitas.index') }}" class="hidden rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 sm:inline-flex">Lihat Semua</a>
                </div>

                @if (count($activities) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Aktivitas</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Waktu</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Tipe</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($activities as $activity)
                                    @php
                                        $type = $activity['tipe'] ?? 'aktivitas';
                                        $routeName = $activityRoutes[$type] ?? 'admin.dashboard';
                                        $typeClass = match ($type) {
                                            'checkin' => 'bg-emerald-50 text-emerald-700',
                                            'checkout' => 'bg-blue-50 text-blue-700',
                                            'penilaian' => 'bg-violet-50 text-violet-700',
                                            default => 'bg-amber-50 text-amber-700',
                                        };
                                    @endphp
                                    <tr class="transition hover:bg-slate-50">
                                        <td class="max-w-md px-6 py-4">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ $activity['deskripsi'] ?? '-' }}</p>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">{{ $activity['user'] ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $activity['waktu'] ?? '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-bold uppercase {{ $typeClass }}">{{ $type }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <a href="{{ route($routeName) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Buka</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <p class="mt-4 text-sm font-bold text-slate-700">Belum ada aktivitas terbaru</p>
                        <p class="mt-1 text-sm text-slate-500">Aktivitas akan muncul setelah data absensi, jurnal, atau penilaian dibuat.</p>
                    </div>
                @endif
            </article>
        </div>
    </div>
</div>
@endsection

