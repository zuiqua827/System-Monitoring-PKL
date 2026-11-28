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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$nis = '4439';
$password = 'password';

echo "=== AUDIT DALAM LOGIN SISWA (READ-ONLY) ===\n\n";

// 1. Reproduce the exact Siswa query used by PklLoginRequest::authenticateSiswa()
$siswa = Siswa::query()->where('nis', $nis)->with('user')->first();
echo "[A] Query: Siswa::where('nis', '{$nis}')->with('user')->first()\n";
if ($siswa === null) {
    echo "    -> siswa TIDAK ditemukan untuk NIS '{$nis}'\n";
} else {
    echo "    -> siswa ditemukan: id={$siswa->id}, nis={$siswa->nis} (raw type: ".gettype($siswa->nis).")\n";
    echo "    -> user_id={$siswa->user_id}\n";
    echo "    -> user: ".($siswa->user ? "id={$siswa->user->id}, email={$siswa->user->email}" : 'NULL')."\n";
}

// 2. Show the nis column definition
echo "\n[B] Kolom nis pada tabel siswa (SHOW COLUMNS):\n";
try {
    $cols = DB::select("SHOW COLUMNS FROM siswa LIKE 'nis'");
    foreach ($cols as $c) {
        echo "    Field={$c->Field}, Type={$c->Type}, Null={$c->Null}, Key={$c->Key}, Default=".var_export($c->Default, true)."\n";
    }
} catch (\Throwable $e) {
    echo "    (gagal query SHOW COLUMNS: ".$e->getMessage().")\n";
}

// 3. Check if there are multiple siswa with same NIS (or leading zeros)
echo "\n[C] Semua siswa dengan NIS mengandung '4439':\n";
$all = DB::table('siswa')->where('nis', 'like', '%4439%')->get(['id', 'user_id', 'nis']);
foreach ($all as $s) {
    echo "    id={$s->id}, user_id={$s->user_id}, nis='{$s->nis}' (len=".strlen((string) $s->nis).")\n";
}

// 4. If siswa found, verify role + password + Auth::attempt
if ($siswa !== null && $siswa->user !== null) {
    $user = $siswa->user;
    echo "\n[D] User hasil resolusi:\n";
    echo "    user id={$user->id}\n";
    echo "    email={$user->email}\n";
    echo "    must_change_password=".var_export($user->must_change_password, true)."\n";
    echo "    hasRole(Siswa)=".var_export($user->hasRole(UserRole::SISWA->value), true)."\n";
    echo "    roles=".collect($user->roles)->pluck('name')->implode(',')."\n";
    echo "    Hash::check('password', user->password)=".var_export(Hash::check($password, (string) $user->password), true)."\n";
    echo "    Auth::validate(['email'=>user->email,'password'=>'password'])=".var_export(Auth::validate(['email' => $user->email, 'password' => $password]), true)."\n";
}

// 5. Check the user's email exactly as stored
echo "\n[E] Cek pencocokan email user (Auth::attempt uses email):\n";
if ($siswa !== null && $siswa->user !== null) {
    $user = $siswa->user;
    $foundByEmail = User::where('email', $user->email)->first();
    echo "    User::where('email', '{$user->email}')->first() => ".($foundByEmail ? "id={$foundByEmail->id}" : 'NULL')."\n";
    if ($foundByEmail) {
        echo "    Hash::check='".var_export(Hash::check($password, (string) $foundByEmail->password), true)."'\n";
    }
}

echo "\n=== SELESAI ===\n";
