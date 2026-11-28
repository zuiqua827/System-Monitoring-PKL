<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

/**
 * READ-ONLY FINAL PROOF:
 * Simulate a REAL browser flow for Siswa login with a FRESH session + CSRF
 * token per attempt (exactly like a real page load -> submit). This removes
 * the CSRF-reuse artifact from the previous test.
 */

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function attemptLogin(string $label, array $formFields, $httpKernel, $app): void
{
    // Fresh session + fresh CSRF token = exactly what a real browser gets.
    $app->make('session')->start();
    $token = csrf_token();

    $payload = array_merge(['_token' => $token], $formFields);

    $request = Request::create('/login', 'POST', $payload);
    $request->headers->set('Accept', 'text/html');
    $request->setLaravelSession($app->make('session')->driver());

    echo "--- {$label} ---\n";
    try {
        $response = $httpKernel->handle($request);
        $status = $response->getStatusCode();
        $loc = $response->headers->get('Location');
        $content = $response->getContent();

        echo "  Status: {$status}\n";
        echo "  Redirect: ".($loc ?: '(none)')."\n";

        if ($status === 419) {
            echo "  -> 419 CSRF mismatch\n";
        } elseif ($loc && str_contains($loc, 'siswa/dashboard')) {
            echo "  -> [SUKSES] login siswa -> /siswa/dashboard\n";
        } elseif ($loc && str_contains($loc, 'force-change-password')) {
            echo "  -> [SUKSES] login siswa -> /force-change-password (harus ganti password)\n";
        } elseif (str_contains((string) $content, 'These credentials do not match our records')) {
            echo "  -> [GAGAL] 'These credentials do not match our records.'\n";
        } else {
            echo "  -> (lainnya)\n";
        }
    } catch (\Throwable $e) {
        echo "  EXCEPTION: ".$e->getMessage()."\n";
        if (method_exists($e, 'errors')) {
            echo "  errors: ".json_encode($e->errors())."\n";
        }
    }
    echo "\n";
}

echo "=== PROOF FINAL LOGIN SISWA (masing-masing dengan CSRF FRESH) ===\n\n";

// Kasus yang benar: di tab Siswa, NIS + password 'password'
attemptLogin('role=siswa, nis=4439, password=password', [
    'role' => 'siswa', 'nis' => '4439', 'password' => 'password',
], $httpKernel, $app);

// Simulasi user mengetik password SALAH di tab Siswa
attemptLogin('role=siswa, nis=4439, password=salah', [
    'role' => 'siswa', 'nis' => '4439', 'password' => 'salahpass',
], $httpKernel, $app);

// Simulasi user di tab Guru (role=guru) tapi hanya mengisi NIS (tanpa email)
attemptLogin('role=guru, nis terisi, tanpa email', [
    'role' => 'guru', 'nis' => '4439', 'password' => 'password',
], $httpKernel, $app);

echo "=== SELESAI ===\n";
