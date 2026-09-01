<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Validator;

echo "B. AUDIT LOGIN MULTI ROLE\n";

$request = new LoginRequest();
$rules = $request->rules();

$scenarios = [
    [
        'name' => 'Siswa Valid Email',
        'data' => [
            'email' => '4715@smkn1bangsri.sch.id',
            'password' => 'password',
            'role' => 'siswa'
        ]
    ],
    [
        'name' => 'Siswa Invalid Domain',
        'data' => [
            'email' => '4715@gmail.com',
            'password' => 'password',
            'role' => 'siswa'
        ]
    ],
    [
        'name' => 'Siswa Invalid Username (Not NIS)',
        'data' => [
            'email' => 'nama@smkn1bangsri.sch.id',
            'password' => 'password',
            'role' => 'siswa'
        ]
    ],
    [
        'name' => 'Guru Valid',
        'data' => [
            'email' => 'guru@gmail.com',
            'password' => 'password',
            'role' => 'guru'
        ]
    ],
    [
        'name' => 'DUDI Valid',
        'data' => [
            'email' => 'dudi@perusahaan.com',
            'password' => 'password',
            'role' => 'dudi'
        ]
    ]
];

foreach ($scenarios as $s) {
    echo "- Testing: {$s['name']}\n";
    // Simulate request input
    $request->merge($s['data']);
    $validator = Validator::make($s['data'], $request->rules(), $request->messages());
    
    if ($validator->fails()) {
        echo "  Failed: " . implode(', ', $validator->errors()->all()) . "\n";
    } else {
        echo "  Passed (Valid)\n";
    }
}
