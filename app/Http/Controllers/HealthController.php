<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * System & integration health check endpoint.
     * Compatible with SiPintu monitoring probes.
     *
     * GET /health
     * GET /api/v1/health
     */
    public function check(): JsonResponse
    {
        $dbOk = true;
        $dbMessage = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $dbOk = false;
            $dbMessage = 'database unreachable';
        }

        $downstreamUrl = config('services.sipintu.downstream_url', config('app.url'));
        $ssoCallbackUrl = config('services.sipintu.sso_callback_url', rtrim((string) $downstreamUrl, '/') . '/auth/callback');

        $status = $dbOk ? 'ok' : 'degraded';
        $httpCode = $dbOk ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'app' => [
                'name' => config('app.name', 'SIMONGAN'),
                'env' => config('app.env', 'production'),
            ],
            'services' => [
                'database' => [
                    'status' => $dbOk ? 'up' : 'down',
                    'message' => $dbMessage,
                ],
                'downstream' => [
                    'url' => $downstreamUrl,
                    'sso_callback' => $ssoCallbackUrl,
                ],
            ],
        ], $httpCode);
    }
}
