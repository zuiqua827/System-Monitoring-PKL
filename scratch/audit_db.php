<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$totalSiswa = Siswa::count();
$siswaWithUser = Siswa::whereNotNull('user_id')->count();
$siswaWithoutUser = Siswa::whereNull('user_id')->count();

$validEmailCount = 0;
$invalidEmailCount = 0;
$mismatchPrefixCount = 0;

$siswaUsers = User::whereHas('siswa')->get();
$emails = [];
$duplicates = [];

foreach ($siswaUsers as $user) {
    if (preg_match('/^[0-9]+@smkn1bangsri\.sch\.id$/', $user->email)) {
        $validEmailCount++;
        // Check prefix vs NIS
        $siswa = $user->siswa;
        if ($siswa && $siswa->nis . '@smkn1bangsri.sch.id' !== $user->email) {
            $mismatchPrefixCount++;
        }
    } else {
        $invalidEmailCount++;
    }
    
    if (isset($emails[$user->email])) {
        $duplicates[] = $user->email;
    } else {
        $emails[$user->email] = true;
    }
}

$userWithoutSiswa = User::whereHas('roles', function($q) { $q->where('name', 'siswa'); })
                        ->whereDoesntHave('siswa')->count();

echo "A. AUDIT DATABASE SISWA\n";
echo "1. Total Siswa: $totalSiswa\n";
echo "2. Siswa with User: $siswaWithUser\n";
echo "3. Siswa without User (user_id = null): $siswaWithoutUser\n";
echo "4. User Siswa with valid email format: $validEmailCount\n";
echo "5. User Siswa with invalid email format: $invalidEmailCount\n";
echo "6. Mismatched prefix (Email != NIS): $mismatchPrefixCount\n";
echo "7. Duplicate emails: " . count($duplicates) . "\n";
echo "8. Users with role 'siswa' but no Siswa record: $userWithoutSiswa\n";
