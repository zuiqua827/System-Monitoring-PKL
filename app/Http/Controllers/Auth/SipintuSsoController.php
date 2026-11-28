<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\RoleRedirectHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SipintuSsoController extends Controller
{
    public function __construct(
        private readonly UserAuthenticationServiceInterface $authenticationService,
    ) {}

    /**
     * Redirect user to SiPintu SSO Authorization Endpoint (SP-initiated).
     * GET /auth/sipintu
     */
    public function redirect(Request $request): RedirectResponse
    {
        $gatewayUrl = rtrim((string) (config('services.sipintu.api_url') ?: config('services.sipintu.base_url', 'https://sipintu.smkn1bangsri.sch.id')), '/');
        $clientId = (string) config('services.sipintu.client_id');
        $callbackUrl = (string) (config('services.sipintu.sso_callback_url') ?: config('services.sipintu.redirect_uri', route('oauth.callback')));

        $state = bin2hex(random_bytes(16));
        $request->session()->put('sso_state', $state);

        $authorizeUrl = $gatewayUrl . '/oauth/authorize?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return redirect()->away($authorizeUrl);
    }

    /**
     * Handle callback from SiPintu SSO.
     * GET /auth/callback /oauth/callback
     */
    public function callback(Request $request): RedirectResponse
    {
        // 1. Handle OAuth errors returned from the authorization server
        $error = $request->query('error');
        $errorDescription = $request->query('error_description');

        if (! empty($error)) {
            Log::warning('SiPintu SSO callback returned error', [
                'error' => (string) $error,
            ]);

            $errorMessage = match ((string) $error) {
                'access_denied' => 'Login SSO SiPintu dibatalkan atau ditolak oleh pengguna.',
                'invalid_request' => 'Permintaan otorisasi SSO tidak valid.',
                'invalid_grant' => 'Kode otorisasi SSO tidak valid atau sudah kedaluwarsa.',
                default => 'Login SSO SiPintu gagal: ' . ($errorDescription ? (string) $errorDescription : (string) $error),
            };

            return redirect()->route('login')
                ->with('error', $errorMessage)
                ->withErrors(['login' => $errorMessage]);
        }

        // 2. Validate authorization code
        $code = $request->query('code');
        if (empty($code) || ! is_string($code) || trim($code) === '') {
            $missingCodeMsg = 'Kode otorisasi SSO tidak ditemukan.';

            return redirect()->route('login')
                ->with('error', $missingCodeMsg)
                ->withErrors(['login' => $missingCodeMsg]);
        }
        $code = trim($code);

        // 3. Validate state parameter if state was initiated in session (SP-initiated flow)
        if ($request->session()->has('sso_state')) {
            $expectedState = (string) $request->session()->pull('sso_state');
            $incomingState = (string) $request->query('state');

            if (! hash_equals($expectedState, $incomingState)) {
                Log::warning('SiPintu SSO callback state mismatch detected (potential CSRF)');

                $stateMismatchMsg = 'Sesi login SSO tidak valid (state mismatch). Silakan coba lagi.';

                return redirect()->route('login')
                    ->with('error', $stateMismatchMsg)
                    ->withErrors(['login' => $stateMismatchMsg]);
            }
        }

        // 4. Prevent authorization code replay attacks (defense-in-depth single-use verification)
        $codeHash = hash('sha256', $code);
        $consumedKey = 'sso_code_consumed:' . $codeHash;

        if (Cache::has($consumedKey)) {
            Log::warning('SiPintu SSO authorization code replay detected (already consumed)');

            $replayMsg = 'Kode otorisasi SSO sudah pernah digunakan.';

            return redirect()->route('login')
                ->with('error', $replayMsg)
                ->withErrors(['login' => $replayMsg]);
        }

        $gatewayUrl = rtrim((string) (config('services.sipintu.api_url') ?: config('services.sipintu.base_url', 'https://sipintu.smkn1bangsri.sch.id')), '/');
        $clientId = (string) config('services.sipintu.client_id');
        $clientSecret = (string) config('services.sipintu.client_secret');
        $configuredCallback = config('services.sipintu.sso_callback_url') ?: config('services.sipintu.redirect_uri');
        $callbackUrl = ! empty($configuredCallback) ? (string) $configuredCallback : route('oauth.callback');
        $verifySsl = (bool) config('services.sipintu.verify_ssl', true);
        $timeout = (int) config('services.sipintu.timeout', 30);

        try {
            // 5. Exchange authorization code for access token
            $tokenClient = Http::asForm()->timeout($timeout);
            if (! $verifySsl) {
                $tokenClient = $tokenClient->withoutVerifying();
            }

            $tokenResponse = $tokenClient->post($gatewayUrl . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $callbackUrl,
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('SiPintu SSO token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'error' => $tokenResponse->json('error'),
                ]);

                $tokenExchangeMsg = 'Gagal memverifikasi token SSO dengan server SiPintu (HTTP ' . $tokenResponse->status() . ').';

                return redirect()->route('login')
                    ->with('error', $tokenExchangeMsg)
                    ->withErrors(['login' => $tokenExchangeMsg]);
            }

            $tokenData = $tokenResponse->json();
            $accessToken = is_array($tokenData) ? ($tokenData['access_token'] ?? null) : null;

            if (empty($accessToken) || ! is_string($accessToken)) {
                $invalidTokenMsg = 'Access token SiPintu tidak valid.';

                return redirect()->route('login')
                    ->with('error', $invalidTokenMsg)
                    ->withErrors(['login' => $invalidTokenMsg]);
            }

            // 6. Fetch User Profile using access token
            $userClient = Http::withToken($accessToken)->acceptJson()->timeout($timeout);
            if (! $verifySsl) {
                $userClient = $userClient->withoutVerifying();
            }

            $userResponse = $userClient->get($gatewayUrl . '/api/v1/user');
            if (! $userResponse->successful()) {
                Log::error('SiPintu SSO fetch user profile failed', [
                    'status' => $userResponse->status(),
                ]);

                $fetchUserMsg = 'Gagal mengambil data profil pengguna dari SiPintu.';

                return redirect()->route('login')
                    ->with('error', $fetchUserMsg)
                    ->withErrors(['login' => $fetchUserMsg]);
            }

            $userData = $userResponse->json();
            $userPayload = is_array($userData) ? ($userData['data'] ?? $userData) : [];

            $email = ! empty($userPayload['email']) ? trim((string) $userPayload['email']) : null;
            $nis = ! empty($userPayload['nis']) ? trim((string) $userPayload['nis']) : null;
            $nip = ! empty($userPayload['nip']) ? trim((string) $userPayload['nip']) : null;
            $username = ! empty($userPayload['username']) ? trim((string) $userPayload['username']) : null;

            // 7. Match local user strictly by available schema identifiers (email, siswa.nis, guru.nip)
            /** @var User|null $user */
            $user = null;

            // Priority 1: email in users table
            if ($email !== null && $email !== '') {
                $user = User::query()->where('email', $email)->first();
            }

            // Priority 2: nis in siswa table
            if (! $user && $nis !== null && $nis !== '') {
                $user = User::query()
                    ->whereHas('siswa', fn ($q) => $q->where('nis', $nis))
                    ->first();
            }

            // Priority 3: nip in guru table
            if (! $user && $nip !== null && $nip !== '') {
                $user = User::query()
                    ->whereHas('guru', fn ($q) => $q->where('nip', $nip))
                    ->first();
            }

            // Fallback for username attribute if sent as NIS, NIP, or email
            if (! $user && $username !== null && $username !== '') {
                $user = User::query()
                    ->whereHas('siswa', fn ($q) => $q->where('nis', $username))
                    ->first();

                if (! $user) {
                    $user = User::query()
                        ->whereHas('guru', fn ($q) => $q->where('nip', $username))
                        ->first();
                }

                if (! $user && filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    $user = User::query()->where('email', $username)->first();
                }
            }

            // 8. If user is not found locally, reject login (DO NOT auto-create user or PKL placement)
            if (! $user) {
                Log::warning('SiPintu SSO user authenticated at gateway but not found locally', [
                    'has_email' => ! empty($email),
                    'has_nis' => ! empty($nis),
                    'has_nip' => ! empty($nip),
                    'has_username' => ! empty($username),
                ]);

                $userNotFoundMsg = 'Akun SiPintu belum terdaftar pada aplikasi.';

                return redirect()->route('login')
                    ->with('error', $userNotFoundMsg)
                    ->withErrors(['login' => $userNotFoundMsg]);
            }

            // 9. Reject Super Admin accounts from logging in via SSO (must use local admin credentials)
            if ($user->hasRole('Super Admin')) {
                Log::warning('SiPintu SSO rejected for Super Admin account', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                $adminBlockedMsg = 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.';

                return redirect()->route('login')
                    ->with('error', $adminBlockedMsg)
                    ->withErrors(['login' => $adminBlockedMsg]);
            }

            // 10. Log in the local user with remember me enabled
            Auth::login($user, true);
            $request->session()->regenerate();
            // Flag session as SSO authenticated so ForceChangePassword does not intercept
            $request->session()->put('auth_via_sso', true);

            $this->authenticationService->recordLoginMetadata(
                user: $user,
                ipAddress: $request->ip(),
            );

            // 11. Mark authorization code as consumed (defense-in-depth replay prevention)
            Cache::put($consumedKey, true, 600);

            $dashboardUrl = RoleRedirectHelper::getDashboardUrl($user);

            return redirect()->intended($dashboardUrl);

        } catch (Throwable $e) {
            Log::error('SiPintu SSO unexpected error in callback', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $systemErrorMsg = 'Terjadi kesalahan sistem saat memproses login SSO.';

            return redirect()->route('login')
                ->with('error', $systemErrorMsg)
                ->withErrors(['login' => $systemErrorMsg]);
        }
    }
}
