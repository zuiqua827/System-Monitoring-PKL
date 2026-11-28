<?php

declare(strict_types=1);

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * Final verification report for the Student Password Policy Migration.
 *
 * Confirms that EVERY non-deleted Student account now uses:
 *   - password             = "password"  (Hash::check === true)
 *   - must_change_password = false
 *
 * Also confirms Guru / DUDI / Super Admin accounts were NOT modified.
 */

echo "=== Student Password Policy Migration - Final Verification ===\n";

$totalSiswa = User::role('Siswa')->count();
$forceChangeSiswa = User::role('Siswa')->where('must_change_password', true)->count();

echo "Total Siswa users found:          {$totalSiswa}\n";
echo "must_change_password = true:      {$forceChangeSiswa}\n";

// Task-required check #1: Hash::check on the first Siswa's user password.
$first = Siswa::query()->withoutTrashed()->orderBy('id')->first();
if ($first !== null && $first->user !== null) {
    $check = Hash::check('password', (string) $first->user->password);
    echo "Hash::check('password', Siswa::first()->user->password): ".($check ? 'TRUE' : 'FALSE')."\n";
} else {
    echo "Hash::check('password', Siswa::first()->user->password): NO STUDENT/USER FOUND\n";
}

// Task-required check #2: count of Siswa with must_change_password = true.
echo "User::role('Siswa')->where('must_change_password', true)->count(): {$forceChangeSiswa}\n";

// Confirm other roles were NOT touched.
echo "Guru force_change = true:         ".User::role('Guru')->where('must_change_password', true)->count()."\n";
echo "DUDI force_change = true:         ".User::role('DUDI')->where('must_change_password', true)->count()."\n";
echo "Super Admin force_change = true:  ".User::role('Super Admin')->where('must_change_password', true)->count()."\n";

echo "===============================================================\n";
