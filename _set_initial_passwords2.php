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

// Diagnostic + targeted fix, idempotent, transactional.
$siswaUserIds = User::query()->whereHas('siswa')->pluck('id');
$guruUserIds  = User::query()->whereHas('guru')->pluck('id');
$dudiUserIds  = User::query()->whereHas('dudi')->pluck('id');

$targetIds = $siswaUserIds->merge($guruUserIds)->merge($dudiUserIds)->unique();

$superAdminIds = User::query()->role(UserRole::SUPER_ADMIN->value)->pluck('id');
$targetIds = $targetIds->diff($superAdminIds)->values();

echo "Target (setelah eksklusi Super Admin): {$targetIds->count()}\n";

$beforeSuper = [];
foreach (User::query()->role(UserRole::SUPER_ADMIN->value)->get() as $sa) {
    $beforeSuper[$sa->id] = (string) $sa->password;
}

$updated = 0; $already = 0; $skipped = 0; $failed = 0;

DB::transaction(function () use ($targetIds, &$updated, &$already, &$skipped, &$failed): void {
    foreach (User::whereIn('id', $targetIds)->get() as $user) {
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) { $skipped++; continue; }

        if (Hash::check('password', (string) $user->password) && (bool) $user->must_change_password) {
            $already++; continue;
        }

        $user->forceFill([
            'password' => Hash::make('password'),
            'must_change_password' => true,
        ]);
        $user->save();
        $updated++;
    }
});

echo "Updated: {$updated}, Already OK: {$already}, Skipped: {$skipped}, Failed: {$failed}\n";

$saOk = true;
foreach (User::query()->role(UserRole::SUPER_ADMIN->value)->get() as $sa) {
    if ((string) $sa->password !== ($beforeSuper[$sa->id] ?? null)) $saOk = false;
}
echo $saOk ? "SUPER ADMIN TIDAK BERUBAH (OK)\n" : "PERINGATAN: SUPER ADMIN BERUBAH\n";
