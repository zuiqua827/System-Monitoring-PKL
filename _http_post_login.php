<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * READ-ONLY: Simulate the EXACT browser HTTP POST to /login for Siswa
 * through the full HTTP kernel (middleware + controller + FormRequest).
 * This replicates precisely what the browser does.
 */

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$payload = [
    '_token' => 'csrf-placeholder',
    'role' => 'siswa',
    'nis' => '4439',
    'password' => 'password',
];

// Build a real POST request to the login route
$request = Request::create('/login', 'POST', $payload);
$request->headers->set('Accept', 'text/html');

echo "=== SIMULASI HTTP POST /login (SISWA) MELALUI HTTP KERNEL ===\n\n";

try {
    $response = $httpKernel->handle($request);

    echo "Status: {$response->getStatusCode()}\n";
    echo "Redirect: {$response->headers->get('Location')}\n";
    echo "Content-type: {$response->headers->get('Content-Type')}\n";

    $content = $response->getContent();
    if (str_contains((string) $content, 'These credentials do not match our records')) {
        echo "\n>>> RESPONSE MENGANDUNG: 'These credentials do not match our records.'\n";
        echo ">>> Ini = trans('auth.failed'). Berarti login GAGAL di backend.\n";
    } elseif (str_contains((string) $content, 'force-change-password')) {
        echo "\n>>> RESPONSE MENGARAH ke force-change-password (login berhasil).\n";
    } else {
        echo "\n>>> (respon tidak mengandung pesan credential)\n";
    }

    echo "\n--- Isi body (ringkas) ---\n";
    // Show validation errors if present in session
    $session = $request->session();
    if ($session) {
        $errors = $session->get('errors');
        if ($errors) {
            echo "Session errors: ".json_encode($errors->toArray())."\n";
        }
    }
    echo "Body length: ".strlen((string) $content)."\n";
} catch (\Throwable $e) {
    echo "EXCEPTION: ".get_class($e)."\n";
    echo "Message: ".$e->getMessage()."\n";
    if (method_exists($e, 'errors')) {
        echo "Errors: ".json_encode($e->errors())."\n";
    }
}

echo "\n=== SELESAI ===\n";
