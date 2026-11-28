<?php

declare(strict_types=1);

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'Students with force_change=true: '.User::role('Siswa')->where('must_change_password', true)->count().PHP_EOL;

$first = Siswa::query()->withoutTrashed()->orderBy('id')->first();
echo 'First siswa user email: '.($first->user->email ?? 'null').PHP_EOL;
echo 'Hash::check = '.((Hash::check('password', (string) $first->user->password)) ? 'TRUE' : 'FALSE').PHP_EOL;

