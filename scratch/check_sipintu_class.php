<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sipintu = app(App\Services\Interfaces\SiPintuServiceInterface::class);
$students = $sipintu->fetchStudents();

$found = false;
foreach ($students as $student) {
    if (!empty($student['classroom'])) {
        print_r($student['classroom']);
        $found = true;
        break;
    }
}
if (!$found) echo "No classroom found in SIPINTU data.\n";
