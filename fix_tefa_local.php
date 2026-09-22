<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "--- START FIX TEFA STUDIO LOCAL ---\n";

$user = User::where('email', 'tefa@smkn1bangsri.sch.id')->first();

if (!$user) {
    echo "ERROR: User 'tefa@smkn1bangsri.sch.id' not found!\n";
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
$user->save();

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
}

echo "\n--- SELESAI ---\n";
