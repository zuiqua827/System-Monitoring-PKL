<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = rtrim((string) config('services.sipintu.api_url', ''), '/');
$clientId = (string) config('services.sipintu.client_id');
$clientSecret = (string) config('services.sipintu.client_secret');

echo "Base URL: {$baseUrl}\n";
echo "Client ID: {$clientId}\n\n";

$endpoints = [
    '/api/v1/sijuna/students',
    '/api/v1/sijuna/teachers',
    '/api/v1/sijuna/guru',
    '/api/v1/teachers',
    '/api/v1/guru',
];

foreach ($endpoints as $ep) {
    echo "=== GET {$ep} ===\n";
    try {
        $response = Http::withHeaders([
            'X-Client-ID' => $clientId,
            'X-Client-Secret' => $clientSecret,
            'Accept' => 'application/json',
        ])->timeout(30)->withoutVerifying()->get($baseUrl.$ep);

        echo "Status: {$response->status()}\n";
        $body = $response->json();
        if (is_array($body)) {
            if (isset($body['data']) && is_array($body['data'])) {
                echo "data count: ".count($body['data'])."\n";
                if (count($body['data']) > 0) {
                    echo "keys: ".implode(', ', array_keys($body['data'][0]))."\n";
                    echo "first: ".json_encode($body['data'][0])."\n";
                }
            } else {
                echo "body: ".substr(json_encode($body), 0, 500)."\n";
            }
        } else {
            echo "body: ".substr((string)$response->body(), 0, 500)."\n";
        }
    } catch (\Throwable $e) {
        echo "ERROR: ".$e->getMessage()."\n";
    }
    echo "\n";
}
