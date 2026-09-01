<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Requests\UpdateOperasionalRequest;
use Illuminate\Support\Facades\Validator;

$request = new UpdateOperasionalRequest();
$rules = $request->rules();
$messages = $request->messages();

echo "F. AUDIT PENGATURAN OPERASIONAL DUDI\n";

$scenarios = [
    [
        'name' => 'Valid',
        'data' => [
            'jam_masuk' => '08:00',
            'batas_terlambat' => '08:15',
            'batas_sangat_terlambat' => '09:00',
            'jam_pulang' => '16:00',
            'hari_operasional' => ['senin', 'selasa']
        ]
    ],
    [
        'name' => 'Batas Terlambat < Jam Masuk',
        'data' => [
            'jam_masuk' => '08:00',
            'batas_terlambat' => '07:30', // Invalid
            'batas_sangat_terlambat' => '09:00',
            'jam_pulang' => '16:00',
            'hari_operasional' => ['senin']
        ]
    ],
    [
        'name' => 'Batas Sangat Terlambat < Batas Terlambat',
        'data' => [
            'jam_masuk' => '08:00',
            'batas_terlambat' => '08:15',
            'batas_sangat_terlambat' => '08:10', // Invalid
            'jam_pulang' => '16:00',
            'hari_operasional' => ['senin']
        ]
    ],
    [
        'name' => 'Jam Pulang < Batas Sangat Terlambat',
        'data' => [
            'jam_masuk' => '08:00',
            'batas_terlambat' => '08:15',
            'batas_sangat_terlambat' => '09:00',
            'jam_pulang' => '08:30', // Invalid
            'hari_operasional' => ['senin']
        ]
    ]
];

foreach ($scenarios as $s) {
    echo "- Testing: {$s['name']}\n";
    $validator = Validator::make($s['data'], $rules, $messages);
    if ($validator->fails()) {
        echo "  Failed: " . implode(', ', $validator->errors()->all()) . "\n";
    } else {
        echo "  Passed (Valid)\n";
    }
}
