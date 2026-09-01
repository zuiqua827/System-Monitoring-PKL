<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Siswa;
use App\Models\User;

// Build a mock remote response containing 3 students:
// 1. Existing student (No changes)
// 2. Existing student (Needs update)
// 3. New student

$existing = Siswa::first();
if (!$existing) {
    die("No students in DB to test.\n");
}

$remoteData = [
    [
        'nis' => $existing->nis,
        'nisn' => $existing->nisn,
        'nama' => $existing->nama,
        'jenis_kelamin' => $existing->jenis_kelamin,
        'tanggal_lahir' => $existing->tanggal_lahir ? $existing->tanggal_lahir->format('Y-m-d') : null,
        'no_telepon' => $existing->no_telepon,
        'alamat' => $existing->alamat,
        'classroom_id' => $existing->class_id,
        'classroom' => [
            'name' => $existing->kelas ? $existing->kelas->nama : ''
        ]
    ],
    [
        'nis' => '99999999',
        'nisn' => '99999999',
        'nama' => 'Testing Update Name',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2005-01-01',
        'no_telepon' => '08123456789',
        'alamat' => 'Alamat Baru',
        'classroom_id' => $existing->class_id,
        'classroom' => [
            'name' => $existing->kelas ? $existing->kelas->nama : ''
        ]
    ]
];

// If '99999999' already exists, ensure it's different so it updates
$testUpdate = Siswa::where('nis', '99999999')->first();
if ($testUpdate) {
    $remoteData[1]['nama'] = $testUpdate->nama . ' Updated';
} else {
    // Create it first so we can test the update path
    $testUser = User::create(['name' => 'Testing', 'email' => '99999999@smk1bangsri.sch.id', 'password' => bcrypt('password')]);
    Siswa::create(['user_id' => $testUser->id, 'class_id' => $existing->class_id, 'nis' => '99999999', 'nama' => 'Testing']);
}

$mockSiPintuRepo = new class implements \App\Repositories\Interfaces\SiPintuRepositoryInterface {
    public array $mockData = [];
    public function fetchStudents(?string $nis = null, ?string $search = null): array {
        return $this->mockData;
    }
    public function fetchTeachers(?string $nip = null, ?string $search = null): array {
        return [];
    }
    public function fetchDudi(?string $search = null): array {
        return [];
    }
    public function fetchInstruktur(?string $search = null): array {
        return [];
    }
    public function fetchPembimbing(?string $search = null): array {
        return [];
    }
    public function syncAll(): array {
        return [];
    }
};
$mockSiPintuRepo->mockData = $remoteData;
app()->instance(\App\Repositories\Interfaces\SiPintuRepositoryInterface::class, $mockSiPintuRepo);

$sipintu = app(\App\Services\Interfaces\SiPintuServiceInterface::class);

DB::enableQueryLog();

DB::beginTransaction();
try {
    echo "Starting syncStudents() with 2 mocked remote students...\n";
    $stats = $sipintu->syncStudents();
    echo "Sync Students Stats:\n";
    print_r($stats);
    
    $queries = DB::getQueryLog();
    echo "Number of queries executed: " . count($queries) . "\n";
    
    foreach ($queries as $i => $q) {
        echo ($i + 1) . ". " . $q['query'] . "\n";
    }
    
    DB::rollBack();
    echo "Success! Rolled back safely.\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
