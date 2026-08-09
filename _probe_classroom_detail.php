<?php

declare(strict_types=1);

use App\Services\Interfaces\SiPintuServiceInterface;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(SiPintuServiceInterface::class);
$students = $service->fetchStudents();

echo "Total fetched: ".count($students)."\n\n";

// Show records that have a non-empty classroom_id, check for nested relations
$shown = 0;
foreach ($students as $s) {
    $cid = $s['classroom_id'] ?? null;
    if (!empty($cid) && $cid !== 0 && $cid !== '') {
        echo "classroom_id={$cid} nis={$s['nis']}\n";
        echo "  full record: ".json_encode($s)."\n\n";
        $shown++;
        if ($shown >= 3) {
            break;
        }
    }
}

echo "---- Distinct classroom_ids (first 60) ----\n";
$ids = [];
foreach ($students as $s) {
    $cid = $s['classroom_id'] ?? null;
    if (!empty($cid) && $cid !== 0 && $cid !== '') {
        $ids[(int) $cid] = ($ids[(int) $cid] ?? 0) + 1;
    }
}
ksort($ids);
foreach ($ids as $id => $cnt) {
    echo "classroom_id={$id} count={$cnt}\n";
}
