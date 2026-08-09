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

echo "======== VALIDASI SETELAH UPDATE ========\n\n";

// Ambil 1 contoh masing-masing
$siswa = Siswa::query()->with('user')->first();
$guru  = Guru::query()->with('user')->first();
$dudi  = Dudi::query()->with('user')->first();

$cekSiswa = $siswa && $siswa->user
    ? Hash::check('password', (string) $siswa->user->password)
    : null;
$cekGuru = $guru && $guru->user
    ? Hash::check('password', (string) $guru->user->password)
    : null;
$cekDudi = $dudi && $dudi->user
    ? Hash::check('password', (string) $dudi->user->password)
    : null;

echo "Test login contoh akun (Hash::check('password', user->password)):\n";
echo "  Siswa (NIS={$siswa?->nis}, user={$siswa?->user_id}) = ".(($cekSiswa === true) ? 'true' : (($cekSiswa === null) ? 'N/A' : 'false'))."\n";
echo "  Guru  (user={$guru?->user_id}) = ".(($cekGuru === true) ? 'true' : (($cekGuru === null) ? 'N/A' : 'false'))."\n";
echo "  DUDI  (user={$dudi?->user_id}) = ".(($cekDudi === true) ? 'true' : (($cekDudi === null) ? 'N/A' : 'false'))."\n";
echo "\n";

// Hitung total password yang terverifikasi 'password' per kategori
$siswaUsers = User::query()->whereHas('siswa')->get();
$guruUsers  = User::query()->whereHas('guru')->get();
$dudiUsers  = User::query()->whereHas('dudi')->get();

$siswaOk = $siswaUsers->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();
$guruOk  = $guruUsers->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();
$dudiOk  = $dudiUsers->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();

echo "Jumlah password terverifikasi (Hash::check ...= true):\n";
echo "  Siswa = {$siswaOk} / {$siswaUsers->count()}\n";
echo "  Guru  = {$guruOk} / {$guruUsers->count()}\n";
echo "  DUDI  = {$dudiOk} / {$dudiUsers->count()}\n";
echo "\n";

// Super Admin tidak berubah (tidak boleh password 'password')
$superAdmins = User::query()->role(UserRole::SUPER_ADMIN->value)->get();
$saUsingDefaultPassword = $superAdmins->filter(fn ($u) => Hash::check('password', (string) $u->password))->count();
echo "Super Admin count = {$superAdmins->count()}, menggunakan password 'password' = {$saUsingDefaultPassword} (harus 0)\n";
echo ($saUsingDefaultPassword === 0) ? "  SUPER ADMIN AMAN (TIDAK BERUBAH)\n" : "  !!! PERINGATAN: SUPER ADMIN MENGGUNAKAN PASSWORD DEFAULT !!!\n";
echo "\n";

echo "======== DATA TOTAL (AFTER) ========\n";
echo "  User            = ".User::withTrashed()->count()." (soft-deleted included)\n";
echo "  User (aktif)    = ".User::count()."\n";
echo "  Siswa           = ".Siswa::withTrashed()->count()."\n";
echo "  Guru            = ".Guru::withTrashed()->count()."\n";
echo "  DUDI            = ".Dudi::withTrashed()->count()."\n";
echo "  PenempatanPKL   = ".PenempatanPKL::withTrashed()->count()."\n";
echo "  Aktivitas       = ".Aktivitas::withTrashed()->count()."\n";
echo "  Absensi         = ".Absensi::withTrashed()->count()."\n";
echo "  Penilaian       = ".Penilaian::withTrashed()->count()."\n";
echo "===================================\n\n";
echo "VALIDASI SELESAI.\n";
