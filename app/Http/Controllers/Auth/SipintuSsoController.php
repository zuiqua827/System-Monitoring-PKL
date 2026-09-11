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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SipintuSsoController extends Controller
{
    public function __construct(
        private readonly UserAuthenticationServiceInterface $authenticationService,
    ) {}

    /**
     * Redirect user to SiPintu SSO Authorization Endpoint.
     * GET /auth/sipintu /auth/login/sipintu
     */
    public function redirect(Request $request): RedirectResponse
    {
        $gatewayUrl = rtrim((string) config('services.sipintu.api_url', 'https://sipintu.smkn1bangsri.sch.id'), '/');
        $clientId = config('services.sipintu.client_id');
        $callbackUrl = config('services.sipintu.sso_callback_url', route('auth.callback'));

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
        // 1. Validate incoming code & state
        $code = $request->query('code');
        $error = $request->query('error');
        $errorDescription = $request->query('error_description');

        if ($error) {
            Log::warning('SiPintu SSO callback error from gateway', [
                'error' => $error,
                'description' => $errorDescription,
            ]);
            return redirect()->route('login')->with('error', 'Login SSO SiPintu dibatalkan atau ditolak: ' . ($errorDescription ?? $error));
        }

        if (empty($code)) {
            return redirect()->route('login')->with('error', 'Kode otorisasi SSO tidak ditemukan.');
        }

        $gatewayUrl = rtrim((string) config('services.sipintu.api_url', 'https://sipintu.smkn1bangsri.sch.id'), '/');
        $clientId = (string) config('services.sipintu.client_id');
        $clientSecret = (string) config('services.sipintu.client_secret');
        $callbackUrl = (string) config('services.sipintu.sso_callback_url', route('auth.callback'));
        $verifySsl = (bool) config('services.sipintu.verify_ssl', true);

        try {
            // 2. Exchange authorization code for access token
            $tokenClient = Http::asForm()->timeout(30);
            if (!$verifySsl) {
                $tokenClient = $tokenClient->withoutVerifying();
            }

            $tokenResponse = $tokenClient->post($gatewayUrl . '/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $callbackUrl,
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('SiPintu SSO token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);
                return redirect()->route('login')->with('error', 'Gagal memverifikasi token SSO dengan server SiPintu (HTTP ' . $tokenResponse->status() . ').');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                return redirect()->route('login')->with('error', 'Access token SiPintu tidak valid.');
            }

            // 3. Fetch User Profile using access token
            $userClient = Http::withToken($accessToken)->acceptJson()->timeout(30);
            if (!$verifySsl) {
                $userClient = $userClient->withoutVerifying();
            }

            $userResponse = $userClient->get($gatewayUrl . '/api/v1/user');
            if (!$userResponse->successful()) {
                Log::error('SiPintu SSO fetch user failed', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);
                return redirect()->route('login')->with('error', 'Gagal mengambil data profil pengguna dari SiPintu.');
            }

            $userData = $userResponse->json();
            $userPayload = $userData['data'] ?? $userData;

            $email = $userPayload['email'] ?? null;
            $username = $userPayload['username'] ?? $userPayload['nis'] ?? null;

            // Find local user by email or username/nis
            $user = null;
            if (!empty($email)) {
                $user = User::query()->where('email', $email)->first();
            }

            if (!$user && !empty($username)) {
                // Check if user is linked to Siswa with this NIS
                $user = User::query()
                    ->whereHas('siswa', fn ($q) => $q->where('nis', $username))
                    ->first();
            }

            if (!$user) {
                Log::warning('SiPintu SSO user authenticated at gateway but not found locally', [
                    'email' => $email,
                    'username' => $username,
                ]);
                return redirect()->route('login')->with(
                    'error',
                    'Akun Anda terdaftar di SiPintu, tetapi belum ditautkan ke akun Sistem Monitoring PKL (SIMONGAN). Hubungi Admin.'
                );
            }

            // 4. Log in the local user
            Auth::login($user);
            $request->session()->regenerate();

            $this->authenticationService->recordLoginMetadata(
                user: $user,
                ipAddress: $request->ip(),
            );

            $dashboardUrl = RoleRedirectHelper::getDashboardUrl($user);

            return redirect()->intended($dashboardUrl);

        } catch (Throwable $e) {
            Log::error('SiPintu SSO unexpected error in callback', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Terjadi kesalahan sistem saat memproses login SSO.');
        }
    }
}
