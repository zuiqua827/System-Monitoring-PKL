<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use App\Models\Siswa;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "======== AUDIT READ-ONLY: TARGET AKUN ========\n\n";

// Target: users yang punya relasi siswa/guru/dudi (excluding Super Admin role)
$siswaUsers = User::query()->whereHas('siswa')->get();
$guruUsers  = User::query()->whereHas('guru')->get();
$dudiUsers  = User::query()->whereHas('dudi')->get();

// Super Admin detection by role
$superAdminUsers = User::query()->role(UserRole::SUPER_ADMIN->value)->get();

// Overlap check (a user sharing multiple relations)
$targetUserIds = collect()
    ->merge($siswaUsers->pluck('id'))
    ->merge($guruUsers->pluck('id'))
    ->merge($dudiUsers->pluck('id'))
    ->unique();

$superAdminIds = collect($superAdminUsers->pluck('id'));

// Users that have a relation AND are Super Admin (should be excluded)
$relationSuperAdmin = User::query()
    ->where(function ($q) {
        $q->whereHas('siswa')->orWhereHas('guru')->orWhereHas('dudi');
    })
    ->role(UserRole::SUPER_ADMIN->value)
    ->count();

echo "Jumlah akun target:\n";
echo "  Siswa users (relasi siswa)          = {$siswaUsers->count()}\n";
echo "  Guru users (relasi guru)            = {$guruUsers->count()}\n";
echo "  DUDI users (relasi dudi)            = {$dudiUsers->count()}\n";
echo "  Super Admin (role)                  = {$superAdminUsers->count()}\n";
echo "\n";
echo "  Total user dengan relasi (unik)     = {$targetUserIds->count()}\n";
echo "  Relasi + Super Admin (akan dieksklusi) = {$relationSuperAdmin}\n";
echo "\n";

echo "======== DATA TOTAL (BEFORE) ========\n";
echo "  User            = ".User::withTrashed()->count()." (soft-deleted included)\n";
echo "  User (aktif)    = ".User::count()."\n";
echo "  Siswa           = ".Siswa::withTrashed()->count()."\n";
echo "  Guru            = ".Guru::withTrashed()->count()."\n";
echo "  DUDI            = ".Dudi::withTrashed()->count()."\n";
echo "  PenempatanPKL   = ".PenempatanPKL::withTrashed()->count()."\n";
echo "  Aktivitas       = ".Aktivitas::withTrashed()->count()."\n";
echo "  Absensi         = ".Absensi::withTrashed()->count()."\n";
echo "  Penilaian       = ".Penilaian::withTrashed()->count()."\n";
echo "=====================================\n\n";

// Also show how many already have password 'password' as reference
$siswaAlready = 0; $guruAlready = 0; $dudiAlready = 0;
foreach ($siswaUsers as $u) { if (Illuminate\Support\Facades\Hash::check('password', (string) $u->password)) { $siswaAlready++; } }
foreach ($guruUsers as $u) { if (Illuminate\Support\Facades\Hash::check('password', (string) $u->password)) { $guruAlready++; } }
foreach ($dudiUsers as $u) { if (Illuminate\Support\Facades\Hash::check('password', (string) $u->password)) { $dudiAlready++; } }

echo "Sudah ber-password 'password' (Hash::check):\n";
echo "  Siswa = {$siswaAlready}\n";
echo "  Guru  = {$guruAlready}\n";
echo "  DUDI  = {$dudiAlready}\n";
echo "\n";
echo "AUDIT READ-ONLY SELESAI. TIDAK ADA DATA YANG DIUBAH.\n";
