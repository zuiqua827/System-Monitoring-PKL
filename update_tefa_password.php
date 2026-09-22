<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

$user = User::find(1780);

if (!$user) {
    echo "User ID 1780 not found.\n";
    exit;
}

echo "BEFORE UPDATE:\n";
echo "Email: " . $user->email . "\n";
echo "Role: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "Must Change Password: " . $user->must_change_password . "\n";
echo "Hash Check 'password': " . (Hash::check('password', $user->password) ? 'TRUE' : 'FALSE') . "\n";

// Update password
$user->password = Hash::make('password');
$user->save();

// Refresh user model
$user = $user->fresh();

echo "\nAFTER UPDATE:\n";
echo "Email: " . $user->email . "\n";
echo "Role: " . $user->roles->pluck('name')->join(', ') . "\n";
echo "Must Change Password: " . $user->must_change_password . "\n";
echo "Hash Check 'password': " . (Hash::check('password', $user->password) ? 'TRUE' : 'FALSE') . "\n";

// Test Auth::attempt
$credentials = [
    'email' => 'tefa@smkn1bangsri.sch.id',
    'password' => 'password',
];

$authResult = Auth::attempt($credentials);

echo "\nAUTH TEST:\n";
echo "Auth::attempt result: " . ($authResult ? 'SUCCESS' : 'FAILED') . "\n";
if ($authResult) {
    $authUser = Auth::user();
    echo "Logged in as: " . $authUser->email . "\n";
    echo "Role of logged in user: " . $authUser->roles->pluck('name')->join(', ') . "\n";
    echo "Must Change Password of logged in user: " . $authUser->must_change_password . "\n";
}

?>
