<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Requests\Auth\PklLoginRequest;

/**
 * Simulate exactly what the browser sends to POST /login for Siswa.
 * This exercises PklLoginRequest::rules() -> authenticate() -> authenticateSiswa().
 */
function simulatePklLogin(array $payload): array
{
    $request = \Illuminate\Http\Request::create('/login', 'POST', $payload);
    $request->setLaravelSession(app('session')->driver());

    // Merge request so the FormRequest reads the payload
    app()->instance('request', $request);

    /** @var PklLoginRequest $formRequest */
    $formRequest = PklLoginRequest::createFrom($request);

    $result = [];

    // Validate rules first (this is what the framework does before authenticate)
    $validator = validator($payload, $formRequest->rules());
    $result['validation_passes'] = $validator->passes();
    $result['validation_errors'] = $validator->errors()->toArray();

    if (! $result['validation_passes']) {
        return $result;
    }

    // Now run authenticate() (the actual login logic)
    try {
        $formRequest->authenticate();
        $result['authenticate'] = 'OK';
        $result['auth_user'] = auth()->user()?->email;
    } catch (\Throwable $e) {
        $result['authenticate'] = 'EXCEPTION';
        $result['exception_class'] = get_class($e);
        $result['exception_message'] = $e->getMessage();
        if (method_exists($e, 'errors')) {
            $result['validation_errors'] = $e->errors();
        }
    }

    // clean up
    auth()->logout();
    app()->forgetInstance('request');

    return $result;
}

echo "=== TEST PklLoginRequest (SISWA) ===\n";
$siswa = simulatePklLogin([
    'role' => 'siswa',
    'nis' => '4439',
    'password' => 'password',
]);
echo json_encode($siswa, JSON_PRETTY_PRINT)."\n\n";

echo "=== TEST PklLoginRequest (SISWA - role missing) ===\n";
$siswaNoRole = simulatePklLogin([
    'nis' => '4439',
    'password' => 'password',
]);
echo json_encode($siswaNoRole, JSON_PRETTY_PRINT)."\n\n";

echo "=== TEST PklLoginRequest (SISWA - nis as numeric) ===\n";
$siswaNumeric = simulatePklLogin([
    'role' => 'siswa',
    'nis' => 4439,
    'password' => 'password',
]);
echo json_encode($siswaNumeric, JSON_PRETTY_PRINT)."\n\n";
