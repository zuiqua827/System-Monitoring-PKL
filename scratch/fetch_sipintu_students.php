<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Interfaces\SiPintuServiceInterface;

$sipintu = app(SiPintuServiceInterface::class);

try {
    $students = $sipintu->fetchStudents();
    echo "Found " . count($students) . " students.\n";
    if (count($students) > 0) {
        print_r($students[0]);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
