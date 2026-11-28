<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Enums\UserRole;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * READ-ONLY audit of the Siswa browser login path.
 * Simulates the EXACT payload the browser sends for the Siswa tab
 * and traces each step of PklLoginRequest::authenticateSiswa().
 */

$payload = [
    '_token' => str_random_placeholder(),
    'role' => 'siswa',
    'nis' => '4439',
    'password' => 'password',
];

function str_random_placeholder(): string
{
    return 'csrf-token-placeholder';
}

echo "=== AUDIT BROWSER LOGIN SISWA (READ-ONLY) ===\n\n";

echo "[1] Payload yang dikirim browser (Siswa tab):\n";
foreach ($payload as $k => $v) {
    if ($k === 'password') {
        echo "    {$k} = [HIDDEN]\n";
    } else {
        echo "    {$k} = ".var_export($v, true)."\n";
    }
}

echo "\n[2] Langkah authenticateSiswa() step-by-step:\n";

// Step A: resolve Siswa by NIS
$nis = (string) $payload['nis'];
$siswa = Siswa::query()->where('nis', $nis)->with('user')->first();
echo "    A. Siswa::where('nis','{$nis}')->with('user')->first()\n";
echo "       -> siswa = ".($siswa ? "id={$siswa->id}, nis={$siswa->nis}" : 'NULL')."\n";

if ($siswa === null || $siswa->user === null) {
    echo "       -> GAGAL: siswa/user tidak ditemukan. Ini memunculkan trans('auth.failed')\n";
    echo "          = 'These credentials do not match our records.'\n";
    echo "\n=== ROOT CAUSE: NIS tidak ditemukan di tabel siswa ===\n";
    exit;
}

$user = $siswa->user;
echo "       -> user = id={$user->id}, email={$user->email}\n";

// Step B: role check
$hasSiswaRole = $user->hasRole(UserRole::SISWA->value);
echo "    B. hasRole(Siswa) = ".var_export($hasSiswaRole, true)."\n";
if (! $hasSiswaRole) {
    echo "       -> GAGAL: user bukan role Siswa. Memunculkan cross-role error.\n";
    exit;
}

// Step C: Auth::attempt with email
$email = $user->email;
$password = (string) $payload['password'];
$result = Auth::attempt(['email' => $email, 'password' => $password]);
echo "    C. Auth::attempt(['email'=>'{$email}', 'password'=>[HIDDEN]])\n";
echo "       -> result = ".var_export($result, true)."\n";

if (! $result) {
    echo "       -> GAGAL: Auth::attempt false. Memunculkan trans('auth.failed').\n";
    echo "          = 'These credentials do not match our records.'\n";

    // Why would Auth::attempt fail when Hash::check passes?
    echo "\n[3] Investigasi Auth::attempt vs Hash::check:\n";
    echo "    Hash::check('{$password}', user->password) = ".var_export(Hash::check($password, (string) $user->password), true)."\n";
    echo "    Auth::validate(['email'=>'{$email}','password'=>'{$password}']) = "
        .var_export(Auth::validate(['email' => $email, 'password' => $password]), true)."\n";

    // Check if email lookup works
    $foundByEmail = User::where('email', $email)->first();
    echo "    User::where('email','{$email}')->first() = ".($foundByEmail ? "id={$foundByEmail->id}" : 'NULL')."\n";
    echo "    (email di DB) = '{$email}'\n";

    exit;
}

echo "       -> OK: Auth::attempt sukses. User berhasil diautentikasi.\n";
echo "\n[4] Setelah Auth::attempt sukses, redirect ke dashboard:\n";
echo "    must_change_password = ".var_export($user->must_change_password, true)."\n";
echo "    -> Jika true, ForceChangePassword middleware akan redirect ke /force-change-password.\n";
echo "       Ini BUKAN error credential.\n";

echo "\n=== KESIMPULAN ===\n";
echo "Auth::attempt ".(Hash::check($password, (string) $user->password) ? 'BERHASIL' : 'GAGAL')." di backend.\n";
echo "Jika browser menampilkan 'These credentials do not match our records,'\n";
echo "maka request yang sampai ke server membawa nilai role/nis/password yang BERBEDA\n";
echo "dari yang diuji di sini (mis. role='guru' atau 'dudi', atau nis kosong/berubah).\n";
