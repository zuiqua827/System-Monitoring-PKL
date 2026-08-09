<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

/**
 * READ-ONLY: Test the hypothesis that the Siswa browser login fails because
 * the submitted `role` is NOT "siswa" (e.g. "guru"/"dudi") while the form
 * only contains `nis` (no `email`). If role != siswa, authenticateEmailRole()
 * runs Auth::attempt(['email'=>null, 'password'=>...]) -> fails with
 * "These credentials do not match our records."
 */

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make('session')->start();
$token = csrf_token();

function simulate(string $label, array $payload, $httpKernel, $app): void
{
    echo "--- {$label} ---\n";
    $request = Request::create('/login', 'POST', $payload);
    $request->headers->set('Accept', 'text/html');
    $request->setLaravelSession($app->make('session')->driver());

    try {
        $response = $httpKernel->handle($request);
        $status = $response->getStatusCode();
        $loc = $response->headers->get('Location');
        $content = $response->getContent();

        echo "  Status: {$status}\n";
        echo "  Redirect: ".($loc ?: '(none)')."\n";

        if ($status === 419) {
            echo "  -> 419 CSRF\n";
        } elseif ($loc && str_contains($loc, 'siswa/dashboard')) {
            echo "  -> LOGIN BERHASIL (siswa/dashboard)\n";
        } elseif ($loc && str_contains($loc, 'force-change-password')) {
            echo "  -> LOGIN BERHASIL (force-change-password)\n";
        } elseif (str_contains((string) $content, 'These credentials do not match our records')) {
            echo "  -> 'These credentials do not match our records.' (GAGAL)\n";
        } else {
            $errors = session('errors');
            echo "  -> (lainnya) errors=".($errors ? json_encode($errors->toArray()) : 'none')."\n";
        }
    } catch (\Throwable $e) {
        echo "  EXCEPTION: ".$e->getMessage()."\n";
        if (method_exists($e, 'errors')) {
            echo "  errors: ".json_encode($e->errors())."\n";
        }
    }
    echo "\n";
}

echo "=== UJI HIPOTESIS ROLE SALAH ===\n\n";

// Correct: role=siswa, nis=4439
simulate('role=siswa, nis=4439, password=password', [
    '_token' => $token, 'role' => 'siswa', 'nis' => '4439', 'password' => 'password',
], $httpKernel, $app);

// Hypothesis A: role=guru, only nis present (no email)
simulate('role=guru, nis=4439 (no email)', [
    '_token' => $token, 'role' => 'guru', 'nis' => '4439', 'password' => 'password',
], $httpKernel, $app);

// Hypothesis B: role=dudi, only nis present (no email)
simulate('role=dudi, nis=4439 (no email)', [
    '_token' => $token, 'role' => 'dudi', 'nis' => '4439', 'password' => 'password',
], $httpKernel, $app);

// Hypothesis C: role empty
simulate('role empty, nis=4439', [
    '_token' => $token, 'role' => '', 'nis' => '4439', 'password' => 'password',
], $httpKernel, $app);

echo "=== SELESAI ===\n";
