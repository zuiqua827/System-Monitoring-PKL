<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Support\Facades\DB;

$sipintu = app(SiPintuServiceInterface::class);

DB::beginTransaction();
try {
    echo "Starting syncStudents()...\n";
    $stats = $sipintu->syncStudents();
    echo "Sync Students Stats:\n";
    print_r($stats);
    
    echo "Starting syncTeachers()...\n";
    $teacherStats = $sipintu->syncTeachers();
    echo "Sync Teachers Stats:\n";
    print_r($teacherStats);
    
    DB::rollBack();
    echo "Success! Rolled back safely.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
