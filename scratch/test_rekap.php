<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AbsensiService;
use App\Models\PenempatanPKL;
use Illuminate\Support\Carbon;

$service = app(AbsensiService::class);
$penempatan = PenempatanPKL::with('dudi')->first();

echo "E. AUDIT REKAP PKL\n";

$rekap = $service->getRekapPresensi($penempatan->id);

echo "1. Total Hari PKL: " . $rekap['total_hari'] . "\n";
echo "2. Hari Berjalan: " . $rekap['hari_berjalan'] . "\n";
echo "3. Hadir: " . count($rekap['hadir']) . "\n";
echo "4. Terlambat: " . count($rekap['terlambat']) . "\n";
echo "5. Sangat Terlambat: " . count($rekap['sangat_terlambat']) . "\n";
echo "6. Bolos: " . count($rekap['bolos']) . "\n";
