<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * SET PASSWORD AWAL SISWA, GURU, DUDI
 *
 * - password = Hash::make('password')
 * - must_change_password = true (agar dipaksa ganti di login pertama)
 * - HANYA user yang punya relasi siswa/guru/dudi (eksklusi Super Admin)
 * - Transaksi database (rollback jika exception)
 * - Tidak menghapus/mengubah data lain, role, permission
 */

$siswaUserIds = User::query()->whereHas('siswa')->pluck('id');
$guruUserIds  = User::query()->whereHas('guru')->pluck('id');
$dudiUserIds  = User::query()->whereHas('dudi')->pluck('id');

// Gabungkan id target (unik). Ini murni berbasis relasi, bukan hardcode.
$targetIds = $siswaUserIds
    ->merge($guruUserIds)
    ->merge($dudiUserIds)
    ->unique();

// Eksklusi tegas: jangan pernah menyentuh Super Admin
$superAdminIds = User::query()->role(UserRole::SUPER_ADMIN->value)->pluck('id');
$targetIds = $targetIds->diff($superAdminIds)->values();

echo "======== SET PASSWORD AWAL ========\n";
echo "Target user dengan relasi (setelah eksklusi Super Admin): {$targetIds->count()}\n\n";

$beforeSuperAdminPass = [];
foreach (User::query()->role(UserRole::SUPER_ADMIN->value)->get() as $sa) {
    $beforeSuperAdminPass[$sa->id] = (string) $sa->password;
}

$updated = 0;
$skipped = 0;
$failed  = 0;

DB::transaction(function () use ($targetIds, &$updated, &$skipped, &$failed): void {
    $targetIds->chunk(200)->each(function ($ids) use (&$updated, &$skipped, &$failed): void {
        $users = User::whereIn('id', $ids)->get();

        foreach ($users as $user) {
            // Eksklusi ganda (defense in depth): jangan ubah Super Admin.
            if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
                $skipped++;
                continue;
            }

            $user->forceFill([
                'password' => Hash::make('password'),
                'must_change_password' => true,
            ]);
            $user->save();
            $updated++;
        }
    });
});

echo "Hasil update:\n";
echo "  Updated: {$updated}\n";
echo "  Skipped (Super Admin/other): {$skipped}\n";
echo "  Failed:  {$failed}\n";
echo "\n";

// Verifikasi Super Admin tidak berubah
$superAdminUnchanged = true;
foreach (User::query()->role(UserRole::SUPER_ADMIN->value)->get() as $sa) {
    if ((string) $sa->password !== ($beforeSuperAdminPass[$sa->id] ?? null)) {
        $superAdminUnchanged = false;
        echo "  !!! Super Admin #{$sa->id} PASSWORD BERUBAH !!!\n";
    }
}
echo $superAdminUnchanged
    ? "Super Admin password: TIDAK BERUBAH (OK)\n"
    : "Super Admin password: ADA PERUBAHAN (PERLU INVESTIGASI)\n";

echo "\nSELESAI.\n";
