<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

$baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
$clientId = (string) config('services.sipintu.client_id');
$clientSecret = (string) config('services.sipintu.client_secret');
$timeout = (int) config('services.sipintu.timeout', 15);
$verifySsl = (bool) config('services.sipintu.verify_ssl', true);

echo "Base URL: " . $baseUrl . "\n";
echo "Client ID is " . ($clientId ? "SET" : "EMPTY") . "\n";
echo "Client Secret is " . ($clientSecret ? "SET" : "EMPTY") . "\n";
echo "Timeout: " . $timeout . "\n";
echo "Verify SSL: " . ($verifySsl ? "Yes" : "No") . "\n";

$endpoint = $baseUrl . '/api/v1/sijuna/students';
echo "Testing Endpoint: " . $endpoint . "\n\n";

try {
    $client = Http::withHeaders([
        'X-Client-ID' => $clientId,
        'X-Client-Secret' => $clientSecret,
        'Accept' => 'application/json',
    ])
    ->connectTimeout(min($timeout, 5))
    ->timeout($timeout);

    if (! $verifySsl) {
        $client = $client->withoutVerifying();
    }

    $startTime = microtime(true);
    $response = $client->get($endpoint, ['limit' => 1]);
    $endTime = microtime(true);
    
    echo "Time taken: " . number_format($endTime - $startTime, 2) . " seconds\n";
    echo "HTTP Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        echo "Connection SUCCESSFUL!\n";
        echo "Response excerpt: " . substr($response->body(), 0, 200) . "...\n";
    } else {
        echo "Connection FAILED (HTTP " . $response->status() . ").\n";
        echo "Response Body: " . $response->body() . "\n";
    }

} catch (ConnectionException $e) {
    echo "ERROR: ConnectionException\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "This indicates a network issue: DNS failure, connection refused, or timeout.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
}
