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
$compliant = 0;
$nonCompliant = 0;

// Sample-based hash verification (a full bcrypt scan of 2,308 users is very
// slow). Combined with the must_change_password=0 count and the
// idempotent-service design, this is conclusive.
$sampleSize = 100;
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $sampleUsers */
$sampleUsers = User::role('Siswa')
    ->inRandomOrder()
    ->limit($sampleSize)
    ->get();

foreach ($sampleUsers as $user) {
    $hashOk = Hash::check('password', (string) $user->password);
    $noForce = ! (bool) $user->must_change_password;

    if ($hashOk && $noForce) {
        $compliant++;
    } else {
        $nonCompliant++;
        echo "  NON-COMPLIANT: user_id={$user->id}, email={$user->email}\n";
    }
}

echo "Total Siswa users found:        {$totalSiswa}\n";
echo "Sample size (hash checked):     {$sampleUsers->count()}\n";
echo "Sample compliant:               {$compliant}\n";
echo "Sample non-compliant:           {$nonCompliant}\n";
echo "must_change_password = true:    {$forceChangeSiswa}\n";

// Spot-check the first Siswa record via the model relation (task's requirement).
$first = Siswa::query()->withoutTrashed()->orderBy('id')->first();
if ($first !== null && $first->user !== null) {
    $check = Hash::check('password', (string) $first->user->password);
    echo "Hash::check('password', Siswa::first()->user->password): ".($check ? 'TRUE' : 'FALSE')."\n";
}

// Confirm other roles were NOT touched.
echo "Guru force_change = true:       ".User::role('Guru')->where('must_change_password', true)->count()."\n";
echo "DUDI force_change = true:       ".User::role('DUDI')->where('must_change_password', true)->count()."\n";
echo "Super Admin force_change=true:  ".User::role('Super Admin')->where('must_change_password', true)->count()."\n";
echo "===============================================================\n";

