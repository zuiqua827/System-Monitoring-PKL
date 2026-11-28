<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Support\Facades\DB;

$service = app(SiPintuServiceInterface::class);
$students = $service->fetchStudents();

echo "Total fetched: ".count($students)."\n";

// Check classroom_id distribution
$classroomDist = [];
$emptyClassroom = 0;
foreach ($students as $s) {
    $cid = $s['classroom_id'] ?? null;
    if ($cid === null || $cid === '' || $cid === 0) {
        $emptyClassroom++;
    } else {
        $classroomDist[(string) $cid] = ($classroomDist[(string) $cid] ?? 0) + 1;
    }
}
echo "Empty classroom_id: ".$emptyClassroom."\n";
echo "Non-empty classroom distribution:\n";
foreach ($classroomDist as $cid => $c) {
    echo "  classroom_id={$cid} count={$c}\n";
}

// Check what the local dummy students look like (ids 1-15)
echo "\n=== Local dummy students (first 16 by id) ===\n";
foreach (DB::table('siswa')->orderBy('id')->limit(16)->get() as $s) {
    echo "id={$s->id} | nis={$s->nis} | nama={$s->nama} | class_id={$s->class_id}\n";
}

// Check the synchronized students - what NIS do they have? Are they in the API?
echo "\n=== Local siswa NIS sample (ids 16-25) ===\n";
foreach (DB::table('siswa')->orderBy('id')->skip(15)->limit(10)->get() as $s) {
    echo "id={$s->id} | nis={$s->nis} | nama={$s->nama} | class_id={$s->class_id}\n";
}
