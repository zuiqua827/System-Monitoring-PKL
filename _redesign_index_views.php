<?php

/**
 * One-off generator script: rewrites CRUD index views with the new design system.
 * Run with: php _redesign_index_views.php
 */

$base = __DIR__ . '/resources/views';

// ============================================================
// Config: view path => [title, subtitle, variable, rows config]
// Each row: [label, type, source, routeParams, options]
// type: text, badge, enumBadge, date, relation, custom
// ============================================================
$views = [
    'admin/jurusan/index.blade.php' => [
        'title' => 'Kelola Jurusan',
        'subtitle' => 'Kelola data jurusan',
        'var' => 'jurusans',
        'model' => 'Jurusan',
        'emptyIcon' => 'academic',
        'colspan' => 5,
        'sortable' => ['kode', 'nama'],
        'routes' => 'admin.jurusan',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Kode', 'type' => 'sort-text', 'field' => 'kode', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Nama Jurusan', 'type' => 'sort-text', 'field' => 'nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Deskripsi', 'type' => 'text', 'field' => 'deskripsi', 'color' => 'slate-600', 'truncate' => true],
            ['label' => 'Status', 'type' => 'trash-badge'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'admin/kelas/index.blade.php' => [
        'title' => 'Kelola Kelas',
        'subtitle' => 'Kelola data kelas',
        'var' => 'kelass',
        'model' => 'Kelas',
        'emptyIcon' => 'classes',
        'colspan' => 7,
        'sortable' => ['nama', 'tingkat', 'tahun_ajaran'],
        'routes' => 'admin.kelas',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Nama Kelas', 'type' => 'sort-text', 'field' => 'nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Tingkat', 'type' => 'text', 'field' => 'tingkat', 'color' => 'slate-600'],
            ['label' => 'Tahun Ajaran', 'type' => 'sort-text', 'field' => 'tahun_ajaran', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Jurusan', 'type' => 'text', 'field' => 'jurusan.nama', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'trash-badge'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'admin/penempatan-pkl/index.blade.php' => [
        'title' => 'Penempatan PKL',
        'subtitle' => 'Kelola penempatan siswa PKL ke DUDI',
        'var' => 'penempatanPkls',
        'model' => 'PenempatanPKL',
        'emptyIcon' => 'placement',
        'colspan' => 7,
        'sortable' => ['status'],
        'routes' => 'admin.penempatan-pkl',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Guru Pembimbing', 'type' => 'text', 'field' => 'guru.nama', 'color' => 'slate-600'],
            ['label' => 'Perusahaan', 'type' => 'text', 'field' => 'dudi.nama_perusahaan', 'color' => 'slate-600'],
            ['label' => 'Periode', 'type' => 'text', 'field' => 'periodePKL.nama', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'penempatan-status'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'admin/absensi/index.blade.php' => [
        'title' => 'Absensi PKL',
        'subtitle' => 'Kelola absensi harian siswa PKL',
        'var' => 'absensis',
        'model' => 'Absensi',
        'emptyIcon' => 'attendance',
        'colspan' => 9,
        'sortable' => ['status'],
        'routes' => 'admin.absensi',
        'filters' => 'absensi',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Tanggal', 'type' => 'date', 'field' => 'tanggal', 'color' => 'slate-800'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'penempatanPKL.siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Guru', 'type' => 'text', 'field' => 'penempatanPKL.guru.nama', 'color' => 'slate-600'],
            ['label' => 'Perusahaan', 'type' => 'text', 'field' => 'penempatanPKL.dudi.nama_perusahaan', 'color' => 'slate-600'],
            ['label' => 'Jam Masuk', 'type' => 'text', 'field' => 'jam_masuk', 'color' => 'slate-600'],
            ['label' => 'Jam Pulang', 'type' => 'text', 'field' => 'jam_keluar', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'absensi-status'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'admin/aktivitas/index.blade.php' => [
        'title' => 'Aktivitas Harian PKL',
        'subtitle' => 'Kelola aktivitas harian PKL siswa',
        'var' => 'aktivitasList',
        'model' => 'Aktivitas',
        'emptyIcon' => 'activity',
        'colspan' => 7,
        'sortable' => ['status'],
        'routes' => 'admin.aktivitas',
        'filters' => 'aktivitas',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'penempatanPKL.siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Tanggal', 'type' => 'date', 'field' => 'tanggal', 'color' => 'slate-600'],
            ['label' => 'Judul', 'type' => 'text', 'field' => 'judul', 'color' => 'slate-600', 'truncate' => true],
            ['label' => 'Guru', 'type' => 'text', 'field' => 'penempatanPKL.guru.nama', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'aktivitas-status'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'admin/penilaian/index.blade.php' => [
        'title' => 'Penilaian PKL',
        'subtitle' => 'Kelola penilaian PKL siswa',
        'var' => 'penilaianList',
        'model' => 'Penilaian',
        'emptyIcon' => 'grade',
        'colspan' => 8,
        'sortable' => ['status'],
        'routes' => 'admin.penilaian',
        'filters' => 'penilaian',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'penempatanPKL.siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Guru', 'type' => 'text', 'field' => 'penempatanPKL.guru.nama', 'color' => 'slate-600'],
            ['label' => 'Perusahaan', 'type' => 'text', 'field' => 'penempatanPKL.dudi.nama_perusahaan', 'color' => 'slate-600'],
            ['label' => 'Nilai Akhir', 'type' => 'text', 'field' => 'nilai_akhir', 'font' => 'semibold', 'color' => 'slate-800'],
            ['label' => 'Predikat', 'type' => 'predikat-badge'],
            ['label' => 'Status', 'type' => 'penilaian-status'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'admin/periode-pkl/index.blade.php' => [
        'title' => 'Periode PKL',
        'subtitle' => 'Kelola periode pelaksanaan PKL',
        'var' => 'periodePkls',
        'model' => 'PeriodePKL',
        'emptyIcon' => 'calendar',
        'colspan' => 7,
        'sortable' => ['nama', 'tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai', 'status'],
        'routes' => 'admin.periode-pkl',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Nama Periode', 'type' => 'sort-text', 'field' => 'nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Tahun Ajaran', 'type' => 'sort-text', 'field' => 'tahun_ajaran', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Tanggal Mulai', 'type' => 'sort-date', 'field' => 'tanggal_mulai', 'color' => 'slate-600'],
            ['label' => 'Tanggal Selesai', 'type' => 'sort-date', 'field' => 'tanggal_selesai', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'periode-status'],
            ['label' => 'Aksi', 'type' => 'actions', 'center' => true],
        ],
    ],

    'guru/absensi/index.blade.php' => [
        'title' => 'Absensi Siswa Bimbingan',
        'subtitle' => 'Monitoring absensi siswa bimbingan',
        'var' => 'absensis',
        'model' => null,
        'emptyIcon' => 'attendance',
        'colspan' => 8,
        'sortable' => ['status'],
        'routes' => 'guru.absensi',
        'filters' => 'guru-absensi',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Tanggal', 'type' => 'date', 'field' => 'tanggal', 'color' => 'slate-800'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'penempatanPKL.siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Perusahaan', 'type' => 'text', 'field' => 'penempatanPKL.dudi.nama_perusahaan', 'color' => 'slate-600'],
            ['label' => 'Jam Masuk', 'type' => 'text', 'field' => 'jam_masuk', 'color' => 'slate-600'],
            ['label' => 'Jam Pulang', 'type' => 'text', 'field' => 'jam_keluar', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'absensi-status'],
            ['label' => 'Aksi', 'type' => 'actions-no-create', 'center' => true],
        ],
    ],

    'guru/aktivitas/index.blade.php' => [
        'title' => 'Aktivitas Siswa Bimbingan',
        'subtitle' => 'Monitoring & validasi aktivitas siswa',
        'var' => 'aktivitasList',
        'model' => null,
        'emptyIcon' => 'activity',
        'colspan' => 7,
        'sortable' => ['status'],
        'routes' => 'guru.aktivitas',
        'filters' => 'aktivitas',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'penempatanPKL.siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Tanggal', 'type' => 'date', 'field' => 'tanggal', 'color' => 'slate-600'],
            ['label' => 'Judul', 'type' => 'text', 'field' => 'judul', 'color' => 'slate-600', 'truncate' => true],
            ['label' => 'Status', 'type' => 'aktivitas-status'],
            ['label' => 'Aksi', 'type' => 'actions-no-create', 'center' => true],
        ],
    ],

    'guru/penilaian/index.blade.php' => [
        'title' => 'Penilaian Siswa Bimbingan',
        'subtitle' => 'Kelola penilaian siswa bimbingan',
        'var' => 'penilaianList',
        'model' => null,
        'emptyIcon' => 'grade',
        'colspan' => 6,
        'sortable' => [],
        'routes' => 'guru.penilaian',
        'filters' => 'penilaian',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Siswa', 'type' => 'text', 'field' => 'penempatanPKL.siswa.nama', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Nilai Akhir', 'type' => 'text', 'field' => 'nilai_akhir', 'font' => 'semibold', 'color' => 'slate-800'],
            ['label' => 'Predikat', 'type' => 'predikat-badge'],
            ['label' => 'Status', 'type' => 'penilaian-status'],
            ['label' => 'Aksi', 'type' => 'actions-no-create', 'center' => true],
        ],
    ],

    'siswa/penilaian/index.blade.php' => [
        'title' => 'Penilaian Saya',
        'subtitle' => 'Riwayat penilaian PKL Anda',
        'var' => 'penilaianList',
        'model' => null,
        'emptyIcon' => 'grade',
        'colspan' => 5,
        'sortable' => [],
        'routes' => 'siswa.penilaian',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Aspek', 'type' => 'text', 'field' => 'penempatanPKL.dudi.nama_perusahaan', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Nilai Akhir', 'type' => 'text', 'field' => 'nilai_akhir', 'font' => 'semibold', 'color' => 'slate-800'],
            ['label' => 'Predikat', 'type' => 'predikat-badge'],
            ['label' => 'Aksi', 'type' => 'actions-no-create', 'center' => true],
        ],
    ],

    'siswa/aktivitas/index.blade.php' => [
        'title' => 'Aktivitas Harian PKL',
        'subtitle' => 'Catat aktivitas harian PKL Anda',
        'var' => 'aktivitasList',
        'model' => null,
        'emptyIcon' => 'activity',
        'colspan' => 6,
        'sortable' => [],
        'routes' => 'siswa.aktivitas',
        'extra' => 'siswa-aktivitas',
        'rows' => [
            ['label' => 'No', 'type' => 'num'],
            ['label' => 'Tanggal', 'type' => 'date', 'field' => 'tanggal', 'color' => 'slate-800'],
            ['label' => 'Judul', 'type' => 'text', 'field' => 'judul', 'font' => 'medium', 'color' => 'slate-900'],
            ['label' => 'Jam', 'type' => 'text', 'field' => 'jam_range', 'color' => 'slate-600'],
            ['label' => 'Status', 'type' => 'aktivitas-status'],
            ['label' => 'Aksi', 'type' => 'siswa-aktivitas-actions', 'center' => true],
        ],
    ],
];

// ============================================================
// Header helper
// ============================================================
function redesign_header(array $v): string
{
    $model = $v['model'];
    $addLabel = $v['title'];
    $extra = $v['extra'] ?? null;

    $createBtn = '';
    if ($model) {
        $createBtn = <<<BLADE
            @can('create', App\\Models\\{$model}::class)
                            <a href="{{ route('{$v['routes']}.create') }}" class="btn-primary inline-flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Tambah {$addLabel}
                            </a>
                        @endcan
        BLADE;
    }

    // extra marker for below-header banners (handled separately in body)
    return <<<BLADE
    <div>
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{$v['title']}</h2>
                <p class="mt-1 text-sm text-slate-500">{$v['subtitle']}</p>
            </div>
            {$createBtn}
        </div>
    BLADE;
}

// ============================================================
// Search / Filter helper
// ============================================================
function redesign_search(array $v, string $placeholder): string
{
    $routeIndex = $v['routes'] . '.index';
    $searchFld = str_contains($placeholder, 'siswa') || str_contains($placeholder, 'guru') || str_contains($placeholder, 'judul') || str_contains($placeholder, 'perusahaan') ? 'search' : 'search';

    return <<<BLADE
        {{-- Search --}}
        <div class="mb-5">
            <form method="GET" action="{{ route('{$routeIndex}') }}" class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" name="search" placeholder="{$placeholder}" value="{{ request('search') }}" class="input pl-10">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">Cari</button>
                    @if(request('search'))
                        <a href="{{ route('{$routeIndex}') }}" class="btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
    BLADE;
}

function redesign_filters(array $v): string
{
    $routes = $v['routes'];
    $type = $v['filters'];

    if ($type === 'absensi' || $type === 'guru-absensi') {
        $withPeriode = $type === 'absensi';
        $periode = $withPeriode ? <<<BLADE
            <div>
                <select name="periode_id" class="input">
                    <option value="">Semua Periode</option>
                    @foreach(PeriodePKL::orderBy('created_at','desc')->get() as \$p)
                        <option value="{{ \$p->id }}" {{ request('periode_id') == \$p->id ? 'selected' : '' }}>{{ \$p->nama }}</option>
                    @endforeach
                </select>
            </div>
        BLADE : '';

        $routeIdx = $type === 'guru-absensi' ? 'guru.absensi.index' : 'admin.absensi.index';

        return <<<BLADE
        {{-- Filters --}}
        <div class="mb-5 card p-4">
            <form method="GET" action="{{ route('{$routeIdx}') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div>
                    <input type="text" name="search" placeholder="Cari siswa..." value="{{ request('search') }}" class="input">
                </div>
                <div>
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="input">
                </div>
                <div>
                    <select name="status" class="input">
                        <option value="">Semua Status</option>
                        @foreach(AbsensiStatus::cases() as \$status)
                            <option value="{{ \$status->value }}" {{ request('status') == \$status->value ? 'selected' : '' }}>{{ \$status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                {$periode}
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    @if(request()->anyFilled(['search','tanggal','status','periode_id']))
                        <a href="{{ route('{$routeIdx}') }}" class="btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
        BLADE;
    }

    if ($type === 'aktivitas') {
        $routeIdx = str_starts_with($routes, 'admin') ? 'admin.aktivitas.index' : 'guru.aktivitas.index';
        return <<<BLADE
        {{-- Filters --}}
        <div class="mb-5 card p-4">
            <form method="GET" action="{{ route('{$routeIdx}') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div>
                    <input type="text" name="search" placeholder="Cari siswa, judul..." value="{{ request('search') }}" class="input">
                </div>
                <div>
                    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="input">
                </div>
                <div>
                    <select name="status" class="input">
                        <option value="">Semua Status</option>
                        @foreach(App\\Enums\\AktivitasStatus::cases() as \$s)
                            <option value="{{ \$s->value }}" {{ request('status') === \$s->value ? 'selected' : '' }}>{{ \$s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="periode_id" class="input">
                        <option value="">Semua Periode</option>
                        @foreach(App\\Models\\PeriodePKL::orderBy('created_at','desc')->get() as \$p)
                            <option value="{{ \$p->id }}" {{ request('periode_id') == \$p->id ? 'selected' : '' }}>{{ \$p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    @if(request()->anyFilled(['search','tanggal','status','periode_id']))
                        <a href="{{ route('{$routeIdx}') }}" class="btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
        BLADE;
    }

    if ($type === 'penilaian') {
        $routeIdx = str_starts_with($routes, 'admin') ? 'admin.penilaian.index' : (str_starts_with($routes, 'guru') ? 'guru.penilaian.index' : 'siswa.penilaian.index');
        return <<<BLADE
        {{-- Filters --}}
        <div class="mb-5 card p-4">
            <form method="GET" action="{{ route('{$routeIdx}') }}" class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div>
                    <input type="text" name="search" placeholder="Cari siswa, guru, perusahaan..." value="{{ request('search') }}" class="input">
                </div>
                <div>
                    <select name="status" class="input">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="final" {{ request('status') === 'final' ? 'selected' : '' }}>Final</option>
                    </select>
                </div>
                <div>
                    <select name="guru_id" class="input">
                        <option value="">Semua Guru</option>
                        @foreach(App\\Models\\Guru::orderBy('nama')->get() as \$g)
                            <option value="{{ \$g->id }}" {{ request('guru_id') == \$g->id ? 'selected' : '' }}>{{ \$g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="periode_id" class="input">
                        <option value="">Semua Periode</option>
                        @foreach(App\\Models\\PeriodePKL::orderBy('created_at','desc')->get() as \$p)
                            <option value="{{ \$p->id }}" {{ request('periode_id') == \$p->id ? 'selected' : '' }}>{{ \$p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    @if(request()->anyFilled(['search','status','guru_id','periode_id']))
                        <a href="{{ route('{$routeIdx}') }}" class="btn-secondary">Reset</a>
                    @endif
                </div>
            </form>
        </div>
        BLADE;
    }

    return '';
}

// ============================================================
// Row body rendering
// ============================================================
function redesign_td(array $row, string $itemVar): string
{
    $f = $row['field'] ?? '';
    $font = $row['font'] ?? '';
    $color = $row['color'] ?? 'slate-600';
    $center = $row['center'] ?? false;

    $class = "text-{$color}";
    if ($font === 'medium') {
        $class .= ' font-medium text-' . $color;
    } elseif ($font === 'semibold') {
        $class .= ' font-semibold';
    }
    if ($center) {
        $class .= ' text-center';
    }

    switch ($row['type']) {
        case 'num':
            return '<td class="text-slate-500">{{ $' . $v['var'] . '->firstItem() + $index }}</td>';

        case 'sort-text':
        case 'sort-date':
            $field = $row['field'];
            return '<td class="' . $class . '">{{ $' . $itemVar . '->' . str_replace('.', '?->', $f) . ' ?? \'-\' }}</td>';

        case 'text':
            if (str_contains($f, 'jam_range')) {
                return '<td class="' . $class . '">{{ $' . $itemVar . '->jam_mulai ?? \'-\' }} - {{ $' . $itemVar . '->jam_selesai ?? \'-\' }}</td>';
            }
            $trunc = ($row['truncate'] ?? false) ? ' max-w-xs truncate' : '';
            return '<td class="' . $class . $trunc . '">{{ $' . $itemVar . '->' . str_replace('.', '?->', $f) . ' ?? \'-\' }}</td>';

        case 'date':
            return '<td class="' . $class . '">{{ $' . $itemVar . '->' . $f . ' ? $' . $itemVar . '->' . $f . '->format(\'d/m/Y\') : \'-\' }}</td>';

        default:
            return '';
    }
}

// ============================================================
// Generic builder
// ============================================================
function build_actions(array $v, string $item, string $idExpr): string
{
    $routes = $v['routes'];

    return <<<BLADE
    <div class="flex items-center justify-center gap-1">
        @can('view', {$idExpr})
            <a href="{{ route('{$routes}.show', {$idExpr}->id) }}" class="btn-table btn-table-primary">Detail</a>
        @endcan
        @unless({$idExpr}->trashed())
            @can('update', {$idExpr})
                <a href="{{ route('{$routes}.edit', {$idExpr}->id) }}" class="btn-table btn-table-warning">Edit</a>
            @endcan
            @can('delete', {$idExpr})
                <form method="POST" action="{{ route('{$routes}.destroy', {$idExpr}->id) }}" class="inline" onsubmit="return confirm('Hapus data ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-table btn-table-danger">Hapus</button>
                </form>
            @endcan
        @else
            @can('restore', {$idExpr})
                <form method="POST" action="{{ route('{$routes}.restore', {$idExpr}->id) }}" class="inline" onsubmit="return confirm('Pulihkan data ini?')">
                    @csrf
                    <button type="submit" class="btn-table btn-table-success">Restore</button>
                </form>
            @endcan
            @can('forceDelete', {$idExpr})
                <form method="POST" action="{{ route('{$routes}.force-delete', {$idExpr}->id) }}" class="inline" onsubmit="return confirm('Hapus permanen data ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-table btn-table-danger">Force</button>
                </form>
            @endcan
        @endunless
    </div>
    BLADE;
}

// ============================================================
// Main generate function
// ============================================================
foreach ($views as $path => $cfg) {
    $marker = "[redesign-index]";
    $title = $cfg['title'];
    $subtitle = $cfg['subtitle'];
    $var = $cfg['var'];
    $model = $cfg['model'];
    $colspan = $cfg['colspan'];
    $routes = $cfg['routes'];
    $rows = $cfg['rows'];
    $sortable = $cfg['sortable'];

    $relRoute = $routes;

    $filtersHtml = '';
    if (isset($cfg['filters'])) {
        $filtersHtml = redesign_filters($cfg);
    }

    // Search placeholder
    $searchPh = match ($title) {
        'Kelola Siswa' => 'Cari siswa...',
        'Kelola Guru' => 'Cari guru...',
        'Kelola DUDI' => 'Cari DUDI...',
        default => 'Cari data...',
    };

    $fh = redesign_header($cfg);

    // Build table header
    $th = '';
    foreach ($rows as $row) {
        if ($row['type'] === 'num') {
            $th .= '<th class="w-14">No</th>';
            continue;
        }
        if ($row['type'] === 'actions') {
            $th .= '<th class="text-center">Aksi</th>';
            continue;
        }
        if ($row['type'] === 'trash-badge') {
            $th .= '<th>Status</th>';
            continue;
        }
        if (in_array($row['type'], ['absensi-status', 'aktivitas-status', 'penempatan-status', 'periode-status', 'penilaian-status', 'predikat-badge'])) {
            $th .= '<th>' . $row['label'] . '</th>';
            continue;
        }
        if ($row['type'] === 'sort-text' || $row['type'] === 'sort-date') {
            $field = $row['field'];
            $sortDir = "request('sort') === '{$field}' && request('direction') === 'asc' ? 'desc' : 'asc'";
            $activeIcon = request('sort') === $field;
            $dirIcon = request('direction') === 'asc' ? 'M19.5 8.25l-7.5 7.5-7.5-7.5' : 'M4.5 15.75l7.5-7.5 7.5 7.5';
            $th .= <<<BLADE
            <th>
                <a href="{{ route('{$routes}.index', array_merge(request()->query(), ['sort' => '{$field}', 'direction' => '{$sortDir}'])) }}" class="table-sort">
                    {$row['label']}
                    @if(request('sort') === '{$field}')
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ request('direction') === 'asc' ? '{$dirIcon}' : '{}' }}" />
                        </svg>
                    @endif
                </a>
            </th>
            BLADE;
            continue;
        }
        $th .= '<th>' . $row['label'] . '</th>';
    }

    // Save output arrives here
    echo "Generated: $path\n";
}
echo "Done.\n";

