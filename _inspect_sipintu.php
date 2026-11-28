<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Interfaces\SiPintuServiceInterface;

$service = app(SiPintuServiceInterface::class);
$students = $service->fetchStudents();

echo "Total fetched: ".count($students)."\n\n";

// Show the keys of the first record
if (count($students) > 0) {
    echo "=== KEYS OF FIRST RECORD ===\n";
    print_r(array_keys($students[0]));

    echo "\n\n=== FIRST 5 RECORDS ===\n";
    foreach (array_slice($students, 0, 5) as $s) {
        print_r($s);
        echo "---\n";
    }
}
