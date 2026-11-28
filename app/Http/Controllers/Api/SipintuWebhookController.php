<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class SipintuWebhookController extends Controller
{
    /**
     * Handle incoming user synchronization webhook from SiPintu Gateway.
     * POST /api/v1/sipintu/sync-user
     * POST /api/webhook/sipintu/user-sync
     */
    public function handleUserSync(Request $request): JsonResponse
    {
        // 1. Authenticate Request via Secret / Signature / Client Credentials
        if (! $this->authenticateRequest($request)) {
            Log::warning('SiPintu webhook rejected: unauthorized request', [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: signature or client credentials invalid.',
            ], 401);
        }

        // 2. Validate Payload
        $validator = Validator::make($request->all(), [
            'event' => 'nullable|string',
            'type' => 'nullable|string|in:siswa,guru,student,teacher,user',
            'data' => 'required|array',
            'data.email' => 'nullable|email',
            'data.name' => 'nullable|string',
            'data.nama' => 'nullable|string',
            'data.nis' => 'nullable|string',
            'data.nip' => 'nullable|string',
            'data.password' => 'nullable|string',
            'data.phone' => 'nullable|string',
            'data.no_hp' => 'nullable|string',
            'data.address' => 'nullable|string',
            'data.alamat' => 'nullable|string',
            'data.gender' => 'nullable|string',
            'data.jenis_kelamin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payload = $request->input('data');
        $type = strtolower((string) ($request->input('type') ?? 'user'));
        $event = strtolower((string) ($request->input('event') ?? 'sync'));

        // Resolve attributes safely
        $email = isset($payload['email']) ? trim((string) $payload['email']) : null;
        $name = $payload['name'] ?? $payload['nama'] ?? null;
        $nis = isset($payload['nis']) ? trim((string) $payload['nis']) : null;
        $nip = isset($payload['nip']) ? trim((string) $payload['nip']) : null;
        $rawPassword = isset($payload['password']) ? (string) $payload['password'] : null;
        $phone = $payload['phone'] ?? $payload['no_hp'] ?? null;
        $address = $payload['address'] ?? $payload['alamat'] ?? null;
        $gender = $payload['gender'] ?? $payload['jenis_kelamin'] ?? null;

        // Idempotency: Locate existing user by email, NIS (siswa), or NIP (guru)
        /** @var User|null $user */
        $user = null;

        if ($nis !== null && $nis !== '') {
            $siswa = Siswa::query()->where('nis', $nis)->first();
            if ($siswa && $siswa->user) {
                $user = $siswa->user;
            }
        }

        if (! $user && $nip !== null && $nip !== '') {
            $guru = Guru::query()->where('nip', $nip)->first();
            if ($guru && $guru->user) {
                $user = $guru->user;
            }
        }

        if (! $user && $email !== null && $email !== '') {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            // User not found locally: return idempotent response without error
            // Per contract, do NOT guess or delete foreign data
            return response()->json([
                'success' => true,
                'action' => 'skipped',
                'message' => 'Data pengguna tidak ditemukan di SIMONGAN. Sinkronisasi penuh melalui jadwal/dashboard disarankan.',
            ], 200);
        }

        // Never modify Super Admin or DUDI via webhook
        if ($user->hasRole('Super Admin') || $user->hasRole('DUDI')) {
            return response()->json([
                'success' => true,
                'action' => 'protected',
                'message' => 'Pengguna berstatus Super Admin/DUDI terlindungi dari perubahan eksternal.',
            ], 200);
        }

        try {
            DB::transaction(function () use ($user, $name, $phone, $address, $gender, $rawPassword): void {
                $updateData = [];

                if ($name !== null && trim($name) !== '') {
                    $updateData['name'] = trim($name);
                }

                if ($phone !== null) {
                    $updateData['phone'] = trim((string) $phone);
                }

                if ($address !== null) {
                    $updateData['address'] = trim((string) $address);
                }

                if ($gender !== null) {
                    $updateData['gender'] = trim((string) $gender);
                }

                // Password sync if provided by SiPintu contract
                if ($rawPassword !== null && $rawPassword !== '') {
                    $updateData['password'] = Hash::make($rawPassword);
                }

                if (! empty($updateData)) {
                    $user->update($updateData);
                }

                // Also synchronize profile details on Siswa or Guru record if related
                if ($user->siswa) {
                    $siswaUpdates = [];
                    if ($name !== null && trim($name) !== '') {
                        $siswaUpdates['nama'] = trim($name);
                    }
                    if ($phone !== null) {
                        $siswaUpdates['no_telepon'] = trim((string) $phone);
                    }
                    if ($address !== null) {
                        $siswaUpdates['alamat'] = trim((string) $address);
                    }
                    if ($gender !== null) {
                        $siswaUpdates['jenis_kelamin'] = trim((string) $gender);
                    }
                    if (! empty($siswaUpdates)) {
                        $user->siswa->update($siswaUpdates);
                    }
                }

                if ($user->guru) {
                    $guruUpdates = [];
                    if ($name !== null && trim($name) !== '') {
                        $guruUpdates['nama'] = trim($name);
                    }
                    if ($phone !== null) {
                        $guruUpdates['no_hp'] = trim((string) $phone);
                    }
                    if ($address !== null) {
                        $guruUpdates['alamat'] = trim((string) $address);
                    }
                    if ($gender !== null) {
                        $guruUpdates['jenis_kelamin'] = trim((string) $gender);
                    }
                    if (! empty($guruUpdates)) {
                        $user->guru->update($guruUpdates);
                    }
                }
            });

            Log::info('SiPintu webhook user synchronized successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'action' => 'updated',
                'user_id' => $user->id,
                'message' => 'Data pengguna berhasil disinkronkan secara realtime.',
            ], 200);

        } catch (Throwable $e) {
            Log::error('SiPintu webhook user sync internal error', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error during synchronization.',
            ], 500);
        }
    }

    /**
     * Authenticate request using configured credentials or signature.
     */
    private function authenticateRequest(Request $request): bool
    {
        $configuredSecret = (string) config('services.sipintu.webhook_secret');
        $configuredClientId = (string) config('services.sipintu.client_id');
        $configuredClientSecret = (string) config('services.sipintu.client_secret');

        // Check 1: X-Webhook-Secret header
        $headerSecret = (string) $request->header('X-Webhook-Secret');
        if ($configuredSecret !== '' && hash_equals($configuredSecret, $headerSecret)) {
            return true;
        }

        // Check 2: X-Client-ID & X-Client-Secret header
        $clientId = (string) $request->header('X-Client-ID');
        $clientSecret = (string) $request->header('X-Client-Secret');
        if ($configuredClientId !== '' && $configuredClientSecret !== ''
            && hash_equals($configuredClientId, $clientId)
            && hash_equals($configuredClientSecret, $clientSecret)) {
            return true;
        }

        // Check 3: HMAC signature via X-Hub-Signature or X-Signature
        $signature = $request->header('X-Signature') ?? $request->header('X-Hub-Signature-256');
        if ($signature && $configuredSecret !== '') {
            $expectedSignature = hash_hmac('sha256', (string) $request->getContent(), $configuredSecret);
            if (hash_equals($expectedSignature, str_replace('sha256=', '', (string) $signature))) {
                return true;
            }
        }

        // Check 4: Bearer token matching webhook secret or api token
        $token = $request->bearerToken();
        if ($token && $configuredSecret !== '' && hash_equals($configuredSecret, $token)) {
            return true;
        }

        return false;
    }
}
