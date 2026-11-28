<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * READ-ONLY: Simulate real browser POST /login (Siswa) WITH valid CSRF token
 * through the full HTTP kernel.
 */

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('session')->start();

// Ensure a session and CSRF token exist
$token = csrf_token();

$payload = [
    '_token' => $token,
    'role' => 'siswa',
    'nis' => '4439',
    'password' => 'password',
    'remember' => '',
];

$request = Request::create('/login', 'POST', $payload);
$request->headers->set('Accept', 'text/html');
$request->setLaravelSession($app->make('session')->driver());

echo "=== SIMULASI HTTP POST /login (SISWA) DENGAN CSRF VALID ===\n\n";
echo "CSRF token: ".substr($token, 0, 10)."...\n\n";

try {
    $response = $httpKernel->handle($request);

    echo "Status: {$response->getStatusCode()}\n";
    $loc = $response->headers->get('Location');
    echo "Redirect Location: ".($loc ?: '(none)')."\n";
    echo "Content-type: {$response->headers->get('Content-Type')}\n";

    $content = $response->getContent();

    if ($response->getStatusCode() === 419) {
        echo "\n>>> 419 CSRF mismatch (simulasi token tidak sinkron dengan session).\n";
    } elseif ($loc && str_contains($loc, 'force-change-password')) {
        echo "\n>>> LOGIN SISWA BERHASIL -> redirect ke /force-change-password\n";
    } elseif ($loc && str_contains($loc, 'siswa/dashboard')) {
        echo "\n>>> LOGIN SISWA BERHASIL -> redirect ke /siswa/dashboard\n";
    } elseif (str_contains((string) $content, 'These credentials do not match our records')) {
        echo "\n>>> 'These credentials do not match our records.' DITEMUKAN di body.\n";
        echo ">>> Login SISWA GAGAL di backend response.\n";
    } else {
        echo "\n>>> (status tidak menunjukkan credential error)\n";
        // Show session errors
        $errors = Session::get('errors');
        if ($errors) {
            echo "Session errors: ".json_encode($errors->toArray())."\n";
        }
    }
} catch (\Throwable $e) {
    echo "EXCEPTION: ".get_class($e)."\n";
    echo "Message: ".$e->getMessage()."\n";
    if (method_exists($e, 'errors')) {
        echo "Errors: ".json_encode($e->errors())."\n";
    }
}

echo "\n=== SELESAI ===\n";
