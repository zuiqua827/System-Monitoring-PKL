<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Repositories\DashboardRepository;
use App\Models\PenempatanPKL;
use Illuminate\Support\Carbon;
use Carbon\CarbonImmutable;

$repo = app(DashboardRepository::class);
$penempatan = PenempatanPKL::with('dudi')->first();
$dudi = $penempatan->dudi;

$scenarios = [
    [
        'name' => 'Normal Mutable',
        'start' => Carbon::parse('2026-08-01'),
        'end' => Carbon::parse('2026-08-31'),
        'dudi_hari' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat']
    ],
    [
        'name' => 'Normal Immutable',
        'start' => CarbonImmutable::parse('2026-08-01'),
        'end' => CarbonImmutable::parse('2026-08-31'),
        'dudi_hari' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat']
    ],
    [
        'name' => 'Start > End',
        'start' => Carbon::parse('2026-09-01'),
        'end' => Carbon::parse('2026-08-31'),
        'dudi_hari' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat']
    ],
    [
        'name' => 'Senin-Sabtu',
        'start' => Carbon::parse('2026-08-01'),
        'end' => Carbon::parse('2026-08-31'),
        'dudi_hari' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']
    ],
];

foreach ($scenarios as $s) {
    echo "Testing: {$s['name']}\n";
    $penempatan->tanggal_mulai = $s['start'];
    $penempatan->tanggal_selesai = $s['end'];
    $dudi->hari_operasional = $s['dudi_hari'];
    $penempatan->setRelation('dudi', $dudi);
    $penempatan->save();
    $dudi->save();

    $start = microtime(true);
    $data = $repo->getSiswaDashboardData($penempatan->siswa_id);
    $time = round(microtime(true) - $start, 4);

    echo "- Done in {$time}s. Total Hari: {$data['totalHari']}, Hari Berjalan: {$data['hariBerjalan']}\n\n";
}
