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
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "======== VALIDASI FINAL (READ-ONLY) ========\n\n";

$siswaUsers = User::query()->whereHas('siswa')->get();
$guruUsers  = User::query()->whereHas('guru')->get();
$dudiUsers  = User::query()->whereHas('dudi')->get();
$superAdmins = User::query()->role(UserRole::SUPER_ADMIN->value)->get();

$siswaOk = $siswaUsers->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();
$guruOk  = $guruUsers->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();
$dudiOk  = $dudiUsers->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();

echo "Password terverifikasi Hash::check('password', ...)=true:\n";
echo "  Siswa = {$siswaOk} / {$siswaUsers->count()}\n";
echo "  Guru  = {$guruOk} / {$guruUsers->count()}\n";
echo "  DUDI  = {$dudiOk} / {$dudiUsers->count()}\n";
echo "\n";

$saUsingDefaultPassword = $superAdmins->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();
echo "Super Admin count = {$superAdmins->count()}, menggunakan password 'password' = {$saUsingDefaultPassword} (harus 0)\n";
foreach ($superAdmins as $sa) {
    $default = Hash::check('password', (string) $sa->password);
    echo "  - SA user_id={$sa->id} mrk default='password'=".($default ? 'YA (PERLU PERHATIAN)' : 'tidak').", must_change=".($sa->must_change_password ? 'true' : 'false').PHP_EOL;
}
echo "\n";

echo "must_change_password terhadap user ber-password default:\n";
$siswaMcp = $siswaUsers->filter(fn ($u) => Hash::check('password', (string) $u->password) && (bool) $u->must_change_password)->count();
$guruMcp  = $guruUsers->filter(fn ($u) => Hash::check('password', (string) $u->password) && (bool) $u->must_change_password)->count();
$dudiMcp  = $dudiUsers->filter(fn ($u) => Hash::check('password', (string) $u->password) && (bool) $u->must_change_password)->count();
echo "  Siswa (password default + must_change=true) = {$siswaMcp} / {$siswaOk}\n";
echo "  Guru  (password default + must_change=true) = {$guruMcp} / {$guruOk}\n";
echo "  DUDI  (password default + must_change=true) = {$dudiMcp} / {$dudiOk}\n";
echo "\n";

// Detail DUDI
echo "Detil DUDI:\n";
foreach ($dudiUsers as $u) {
    echo "  user_id={$u->id} role=".($u->roles->pluck('name')->implode(',') ?: 'none')
        ." hash_password=".(Hash::check('password',(string)$u->password)?'true':'false')
        ." must_change=".($u->must_change_password?'true':'false').PHP_EOL;
}

echo "\n======== DATA TOTAL (AFTER FINAL) ========\n";
echo "  User            = ".User::withTrashed()->count()."\n";
echo "  User (aktif)    = ".User::count()."\n";
echo "  Siswa           = ".Siswa::withTrashed()->count()."\n";
echo "  Guru            = ".Guru::withTrashed()->count()."\n";
echo "  DUDI            = ".Dudi::withTrashed()->count()."\n";
echo "  PenempatanPKL   = ".PenempatanPKL::withTrashed()->count()."\n";
echo "  Aktivitas       = ".Aktivitas::withTrashed()->count()."\n";
echo "  Absensi         = ".Absensi::withTrashed()->count()."\n";
echo "  Penilaian       = ".Penilaian::withTrashed()->count()."\n";
echo "============================================\n";
echo "VALIDASI FINAL SELESAI.\n";
