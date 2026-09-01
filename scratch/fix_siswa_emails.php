<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$siswas = Siswa::with('user')->get();
$updated = 0;
$skipped = 0;

foreach ($siswas as $siswa) {
    if (!$siswa->user) {
        $skipped++;
        continue;
    }

    $expectedEmail = $siswa->nis . '@smkn1bangsri.sch.id';
    
    if ($siswa->user->email !== $expectedEmail) {
        $siswa->user->email = $expectedEmail;
        $siswa->user->save();
        $updated++;
    } else {
        $skipped++;
    }
}

echo "Done! Updated: $updated, Skipped: $skipped\n";
