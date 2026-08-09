@auth
@php
    $user = auth()->user();
    $roleName = $user->roles->first()?->name ?? 'User';
    $dashboardRoute = \App\Helpers\RoleRedirectHelper::getDashboardRouteName($user);
    $initial = strtoupper(substr($user->name ?? 'U', 0, 1));

$adminSections = [
        [
            'label' => 'MENU UTAMA',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => ['admin.dashboard'], 'icon' => 'dashboard'],
                ['label' => 'Kelola Guru', 'route' => 'admin.guru.index', 'active' => ['admin.guru.*'], 'icon' => 'teacher'],
                ['label' => 'Kelola Siswa', 'route' => 'admin.siswa.index', 'active' => ['admin.siswa.*'], 'icon' => 'students'],
                ['label' => 'Kelola DUDI', 'route' => 'admin.dudi.index', 'active' => ['admin.dudi.*'], 'icon' => 'building'],
            ],
        ],
        [
            'label' => 'AKADEMIK',
            'items' => [
                ['label' => 'Kelola Jurusan', 'route' => 'admin.jurusan.index', 'active' => ['admin.jurusan.*'], 'icon' => 'academic'],
                ['label' => 'Kelola Kelas', 'route' => 'admin.kelas.index', 'active' => ['admin.kelas.*'], 'icon' => 'classes'],
                ['label' => 'Periode PKL', 'route' => 'admin.periode-pkl.index', 'active' => ['admin.periode-pkl.*'], 'icon' => 'calendar'],
                ['label' => 'Penempatan PKL', 'route' => 'admin.penempatan-pkl.index', 'active' => ['admin.penempatan-pkl.*'], 'icon' => 'placement'],
            ],
        ],
        [
            'label' => 'MONITORING',
            'items' => [
['label' => 'Absensi', 'route' => 'admin.absensi.index', 'active' => ['admin.absensi.*'], 'icon' => 'attendance'],
                ['label' => 'Aktivitas', 'route' => 'admin.aktivitas.index', 'active' => ['admin.aktivitas.*'], 'icon' => 'activity'],
['label' => 'Penilaian', 'route' => 'admin.penilaian.index', 'active' => ['admin.penilaian.*'], 'icon' => 'grade'],
                ['label' => 'Pengaturan Akun', 'route' => 'account.index', 'active' => ['account.*'], 'icon' => 'profile'],
            ],
        ],
        [
            'label' => 'INTEGRASI',
            'items' => [
                ['label' => 'Sinkronisasi SiPintu', 'route' => 'admin.sipintu-sync.index', 'active' => ['admin.sipintu-sync.*'], 'icon' => 'sync'],
                ['label' => 'Pemetaan Kelas SiPintu', 'route' => 'admin.sipintu-classroom-mapping.index', 'active' => ['admin.sipintu-classroom-mapping.*'], 'icon' => 'classes'],
            ],
        ],
    ];

    $guruSections = [
        [
            'label' => 'MENU UTAMA',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'guru.dashboard', 'active' => ['guru.dashboard'], 'icon' => 'dashboard'],
                ['label' => 'Absensi Siswa', 'route' => 'guru.absensi.index', 'active' => ['guru.absensi.*'], 'icon' => 'attendance'],
                ['label' => 'Aktivitas Siswa', 'route' => 'guru.aktivitas.index', 'active' => ['guru.aktivitas.*'], 'icon' => 'activity'],
['label' => 'Penilaian', 'route' => 'guru.penilaian.index', 'active' => ['guru.penilaian.*'], 'icon' => 'grade'],
                ['label' => 'Pengaturan Akun', 'route' => 'account.index', 'active' => ['account.*'], 'icon' => 'profile'],
            ],
        ],
    ];

    $siswaSections = [
        [
            'label' => 'MENU UTAMA',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'siswa.dashboard', 'active' => ['siswa.dashboard'], 'icon' => 'dashboard'],
                ['label' => 'Absensi', 'route' => 'siswa.absensi.index', 'active' => ['siswa.absensi.*'], 'icon' => 'attendance'],
                ['label' => 'Aktivitas', 'route' => 'siswa.aktivitas.index', 'active' => ['siswa.aktivitas.*'], 'icon' => 'activity'],
['label' => 'Penilaian', 'route' => 'siswa.penilaian.index', 'active' => ['siswa.penilaian.*'], 'icon' => 'grade'],
                ['label' => 'Pengaturan Akun', 'route' => 'account.index', 'active' => ['account.*'], 'icon' => 'profile'],
            ],
        ],
    ];

    $dudiSections = [
        [
            'label' => 'MENU UTAMA',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dudi.dashboard', 'active' => ['dudi.dashboard'], 'icon' => 'dashboard'],
                ['label' => 'Siswa PKL', 'route' => 'dudi.siswa.index', 'active' => ['dudi.siswa.*'], 'icon' => 'students'],
                ['label' => 'Absensi', 'route' => 'dudi.absensi.index', 'active' => ['dudi.absensi.*'], 'icon' => 'attendance'],
                ['label' => 'Aktivitas', 'route' => 'dudi.aktivitas.index', 'active' => ['dudi.aktivitas.*'], 'icon' => 'activity'],
['label' => 'Penilaian', 'route' => 'dudi.penilaian.index', 'active' => ['dudi.penilaian.*'], 'icon' => 'grade'],
                ['label' => 'Pengaturan Akun', 'route' => 'account.index', 'active' => ['account.*'], 'icon' => 'profile'],
            ],
        ],
    ];

    $sections = match ($roleName) {
        'Super Admin' => $adminSections,
        'Guru' => $guruSections,
        'Siswa' => $siswaSections,
        'DUDI' => $dudiSections,
        default => [[
            'label' => 'MENU UTAMA',
            'items' => [
                ['label' => 'Dashboard', 'route' => $dashboardRoute, 'active' => [$dashboardRoute], 'icon' => 'dashboard'],
['label' => 'Pengaturan Akun', 'route' => 'account.index', 'active' => ['account.*'], 'icon' => 'profile'],
            ],
        ]],
    };
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[264px] flex-col border-r border-slate-800 bg-slate-950 text-slate-300 shadow-sidebar transition-transform duration-300 lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
>
{{-- Logo --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800/80 px-5">
        <a href="{{ route($dashboardRoute) }}" class="flex items-center gap-3">
            <img src="{{ asset('images/simongan-logo.png') }}" alt="SIMONGAN Logo" class="h-9 w-9 rounded-xl bg-white/10 object-contain ring-1 ring-white/20">
            <span class="leading-tight">
                <span class="block text-sm font-bold tracking-wide text-white">SIMONGAN</span>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">Sistem Monitoring Lapangan</span>
            </span>
        </a>
    </div>

    {{-- Nav --}}
    <div class="sidebar-scroll flex-1 space-y-7 overflow-y-auto px-4 py-6">
        @foreach ($sections as $section)
            <div>
                <p class="px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">{{ $section['label'] }}</p>
                <div class="mt-2.5 space-y-1">
                    @foreach ($section['items'] as $item)
                        @php
                            $isActive = request()->routeIs(...(array) $item['active']);
                            $href = \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : '#';
                        @endphp
                        <a
                            href="{{ $href }}"
                            @click="sidebarOpen = false"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] font-medium transition-all duration-150 {{ $isActive ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg shadow-blue-950/40' : 'text-slate-400 hover:bg-slate-900 hover:text-white' }}"
                        >
                            @if($isActive)
                                <span class="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-white"></span>
                            @endif
                            <svg class="h-5 w-5 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-500 group-hover:text-blue-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                @switch($item['icon'])
                                    @case('dashboard')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                        @break
                                    @case('teacher')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14.5c3.2 0 5.8-1.35 5.8-3V6.7L12 9.5 6.2 6.7v4.8c0 1.65 2.6 3 5.8 3Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5 12 2l8 3.5-8 3.5L4 5.5Zm4 11.8c-1.9.6-3 1.55-3 2.7h14c0-1.15-1.1-2.1-3-2.7" />
                                        @break
                                    @case('students')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 19c0-2.2-1.8-4-4-4s-4 1.8-4 4" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6.5 6.5c0-1.5-.8-2.75-2-3.45M5.5 18.5c0-1.5.8-2.75 2-3.45M17 11a2.3 2.3 0 1 0 0-4.6M7 11a2.3 2.3 0 1 1 0-4.6" />
                                        @break
                                    @case('building')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16M6 21V5.8C6 4.8 6.8 4 7.8 4h8.4c1 0 1.8.8 1.8 1.8V21M9 8h1.5M13.5 8H15M9 12h1.5M13.5 12H15M9 16h1.5M13.5 16H15" />
                                        @break
                                    @case('academic')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 4l8 3.5-8 3.5L4 7.5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 10v5.2c0 1.55 2.45 2.8 5.5 2.8s5.5-1.25 5.5-2.8V10M12 11v9" />
                                        @break
                                    @case('classes')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 6h14M7 6c0 2.5-1 4-3 4.5M17 6c0 2.5 1 4 3 4.5M5 18h14M7 18c0-2.5-1-4-3-4.5M17 18c0-2.5 1-4 3-4.5" />
                                        @break
                                    @case('calendar')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4.5 9.5h15M6.5 5h11A2.5 2.5 0 0 1 20 7.5v10A2.5 2.5 0 0 1 17.5 20h-11A2.5 2.5 0 0 1 4 17.5v-10A2.5 2.5 0 0 1 6.5 5Z" />
                                        @break
                                    @case('placement')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s6-4.55 6-10a6 6 0 0 0-12 0c0 5.45 6 10 6 10Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 12.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                        @break
                                    @case('attendance')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V4m8 3V4M5.5 9.5h13M7 20h10a2.5 2.5 0 0 0 2.5-2.5v-10A2.5 2.5 0 0 0 17 5H7a2.5 2.5 0 0 0-2.5 2.5v10A2.5 2.5 0 0 0 7 20Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 15 2 2 4-5" />
                                        @break
                                    @case('activity')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h9M8 12h9M8 18h5M5 6h.01M5 12h.01M5 18h.01" />
                                        @break
                                    @case('grade')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 4.5h10A2.5 2.5 0 0 1 19.5 7v13l-3.75-2-3.75 2-3.75-2-3.75 2V7A2.5 2.5 0 0 1 7 4.5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 9.5h7M8.5 13h5" />
                                        @break
@case('profile')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7 8a7 7 0 0 0-14 0" />
                                        @break
                                    @case('sync')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        @break
                                    @default
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 11.5 12 5l7.5 6.5M6.5 10.5V20h11v-9.5M10 20v-5h4v5" />
                                @endswitch
                            </svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- User card + logout --}}
    <div class="shrink-0 border-t border-slate-800/80 p-4">
        <div class="mb-3 flex items-center gap-3 rounded-xl bg-slate-900/70 p-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-sm font-bold text-white">{{ $initial }}</div>
            <div class="min-w-0">
                <p class="truncate text-[13px] font-semibold text-white">{{ $user->name }}</p>
                <p class="truncate text-[11px] text-slate-500">{{ $roleName }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700/80 px-3 py-2.5 text-[13px] font-semibold text-slate-300 transition-colors duration-150 hover:border-red-500/50 hover:bg-red-500/10 hover:text-red-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3 0H21m0 0l-3-3m3 3l-3 3" />
                </svg>
                Log Out
            </button>
        </form>
    </div>
</aside>
@endauth

