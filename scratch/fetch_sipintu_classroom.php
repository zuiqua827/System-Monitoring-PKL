<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Interfaces\SiPintuServiceInterface;

$sipintu = app(SiPintuServiceInterface::class);

try {
    $students = $sipintu->fetchStudents();
    foreach ($students as $student) {
        if (!empty($student['classroom'])) {
            print_r($student['classroom']);
            break;
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
