<?php

declare(strict_types=1);

use App\Models\Dudi;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "===== DIAGNOSTIK DUDI =====" . PHP_EOL;

// 1. Semua user yang punya relasi dudi (yang jadi target)
$dudiRelationUsers = User::query()->whereHas('dudi')->get();
echo "Target user via whereHas('dudi'): {$dudiRelationUsers->count()}\n";
foreach ($dudiRelationUsers as $u) {
    echo "  - user_id={$u->id} role=" . ($u->roles->pluck('name')->implode(',') ?: 'none')
        . " hash_password=" . (Hash::check('password', (string) $u->password) ? 'true' : 'false')
        . " must_change=" . ($u->must_change_password ? 'true' : 'false')
        . " trashed=" . ($u->trashed() ? 'true' : 'false') . PHP_EOL;
}

echo PHP_EOL;
echo "Semua Dudi aktif (tanpa user filter):\n";
$dudis = Dudi::query()->with('user')->get();
echo "  Total dudi aktif = {$dudis->count()}\n";
foreach ($dudis as $d) {
    $u = $d->user;
    $role = $u ? ($u->roles->pluck('name')->implode(',') ?: 'none') : 'NO USER';
    echo "  dudi_id={$d->id} user_id={$d->user_id} nama={$d->nama_perusahaan} user_role={$role}"
        . ($u ? " hash=" . (Hash::check('password', (string) $u->password) ? 'true' : 'false') : '') . PHP_EOL;
}

echo PHP_EOL;
echo "Semua user id 2426:\n";
foreach (User::withTrashed()->where('id', 2426)->get() as $u) {
    echo "  user_id={$u->id} name={$u->name} role=" . ($u->roles->pluck('name')->implode(',') ?: 'none')
        . " trashed=" . ($u->trashed() ? 'true' : 'false')
        . " must_change=" . ($u->must_change_password ? 'true' : 'false')
        . " hash_password=" . (Hash::check('password', (string) $u->password) ? 'true' : 'false') . PHP_EOL;
}

echo PHP_EOL . "SELESAI DIAGNOSTIK.\n";
