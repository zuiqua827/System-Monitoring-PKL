<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Dudi;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use App\Services\AbsensiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$service = app(AbsensiService::class);
$timezone = config('app.timezone');

// Find a student with a placement
$penempatan = PenempatanPKL::with('dudi')->first();
if (!$penempatan) {
    echo "No penempatan found\n";
    exit;
}
$dudi = $penempatan->dudi;

// Scenario tests
$tests = [
    [
        'time' => '08:10:00',
        'batas_terlambat' => '08:15:00',
        'batas_sangat_terlambat' => '09:00:00',
        'expected' => 'hadir'
    ],
    [
        'time' => '08:30:00',
        'batas_terlambat' => '08:15:00',
        'batas_sangat_terlambat' => '09:00:00',
        'expected' => 'terlambat'
    ],
    [
        'time' => '09:15:00',
        'batas_terlambat' => '08:15:00',
        'batas_sangat_terlambat' => '09:00:00',
        'expected' => 'sangat_terlambat'
    ]
];

// Test logic by invoking private method via Reflection
$reflectionMethod = new ReflectionMethod($service, 'determineCheckInStatus');
$reflectionMethod->setAccessible(true);

echo "--- RUNNING TESTS ---\n";
foreach ($tests as $t) {
    $now = Carbon::createFromFormat('H:i:s', $t['time'], $timezone);
    $result = $reflectionMethod->invokeArgs($service, [$now, '08:00:00', $t['batas_terlambat'], $t['batas_sangat_terlambat']]);
    
    $status = $result[0];
    if ($result[1] === 'Sangat Terlambat') {
        $status = 'sangat_terlambat';
    }
    
    echo "Test checkin at {$t['time']}: ";
    if ($status === $t['expected']) {
        echo "PASSED\n";
    } else {
        echo "FAILED (Expected {$t['expected']}, got {$status})\n";
    }
}

echo "--- HARI OPERASIONAL TESTS ---\n";
// Update DUDI to Mon-Fri
$dudi->update(['hari_operasional' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat']]);

$saturday = Carbon::parse('next saturday', $timezone);
$isOperasional = $dudi->isHariOperasional($saturday);
echo "Saturday with Mon-Fri: " . ($isOperasional ? "FAILED" : "PASSED") . "\n";

// Update DUDI to Mon-Sat
$dudi->update(['hari_operasional' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']]);
$isOperasional = $dudi->isHariOperasional($saturday);
echo "Saturday with Mon-Sat: " . ($isOperasional ? "PASSED" : "FAILED") . "\n";

// Revert to original
$dudi->update(['hari_operasional' => null]);
echo "Tests complete.\n";
