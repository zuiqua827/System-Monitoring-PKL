<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "--- START FIX TEFA STUDIO PRODUCTION ---\n";

// Target User ID 1780 explicitly as instructed for production
$user = User::find(1780);

if (!$user) {
    echo "ERROR: User ID 1780 not found!\n";
    exit(1);
}

// Verify it's actually Tefa Studio before proceeding to prevent accidental changes
if ($user->email !== 'tefa@smkn1bangsri.sch.id') {
    echo "ERROR: User ID 1780 email is not 'tefa@smkn1bangsri.sch.id'. It is '{$user->email}'. Aborting to prevent modifying wrong user.\n";
    exit(1);
}

echo "1. VERIFY USER SEBELUM UPDATE:\n";
echo "ID: {$user->id}\n";
echo "Nama: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "Role: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "Must Change Password: {$user->must_change_password}\n";
echo "Password Hash Check ('password'): " . (Hash::check('password', $user->password) ? 'TRUE' : 'FALSE') . "\n";

echo "\n2. MELAKUKAN UPDATE PASSWORD KE Hash::make('password')...\n";
$user->password = Hash::make('password');
// Explicitly saving only password to avoid other field changes
$user->save();

// Refresh to ensure we get data straight from DB
$user = $user->fresh();

echo "\n3. VERIFY USER SETELAH UPDATE:\n";
echo "Password Hash Check ('password'): " . (Hash::check('password', $user->password) ? 'TRUE' : 'FALSE') . "\n";
echo "Must Change Password: {$user->must_change_password}\n";

echo "\n4. TEST LOGIN MANUAL (Auth::attempt)...\n";
$credentials = [
    'email' => 'tefa@smkn1bangsri.sch.id',
    'password' => 'password',
];

$loginSuccess = Auth::attempt($credentials);
if ($loginSuccess) {
    $loggedInUser = Auth::user();
    echo "STATUS: SUCCESS!\n";
    echo "Login Berhasil sebagai: {$loggedInUser->email}\n";
    echo "Role User Login: " . $loggedInUser->roles->pluck('name')->join(', ') . "\n";
    echo "Must Change Password User Login: {$loggedInUser->must_change_password}\n";
} else {
    echo "STATUS: FAILED!\n";
    echo "Login gagal.\n";
}

echo "\n--- SELESAI ---\n";
