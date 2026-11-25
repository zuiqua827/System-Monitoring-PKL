<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Helpers\RoleRedirectHelper;
use App\Http\Controllers\Controller;
use App\Models\Dudi;
use App\Models\User;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controller untuk menangani Single Sign-On (SSO) SiPintu SMKN 1 Bangsri.
 *
 * Alur Kerja OAuth2 Authorization Code:
 * 1. redirect():
 *    - Mengarahkan pengguna ke server otorisasi SiPintu (/oauth/authorize).
 *    - Membawa parameter: client_id, redirect_uri, response_type=code, dan state (CSRF guard).
 * 2. callback():
 *    - Memvalidasi error OAuth, parameter code, state (CSRF), dan replay code.
 *    - Menukarkan kode otorisasi dengan Access Token dan mengambil profil /api/v1/user.
 *    - Mencocokkan akun lokal secara ketat per peran (DUDI via sipintu_user_id + sso_enabled,
 *      Siswa via NIS/email, Guru via NIP/email) tanpa menimpa password atau role lokal.
 *    - Menolak Super Admin serta DUDI tanpa profil aktif / SSO aktif.
 */
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
        $gatewayUrl = $this->gatewayUrl();
        $clientId = trim((string) config('services.sipintu.client_id'));
        $callbackUrl = $this->callbackUrl();

        if ($gatewayUrl === '' || $clientId === '') {
            Log::warning('SiPintu SSO redirect aborted: incomplete client configuration');

            return $this->rejectToLogin(
                $request,
                'Konfigurasi SSO SiPintu belum lengkap. Silakan hubungi administrator.',
            );
        }

        $state = bin2hex(random_bytes(16));
        $request->session()->put('sso_state', $state);

        $authorizeUrl = $gatewayUrl.'/oauth/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'state' => $state,
        ]);

        return redirect()->away($authorizeUrl);
    }

    /**
     * Handle callback from SiPintu SSO.
     * GET /auth/callback | GET /oauth/callback
     */
    public function callback(Request $request): RedirectResponse
    {
        $oauthError = $request->query('error');
        if ($oauthError !== null && $oauthError !== '') {
            return $this->handleOAuthError(
                $request,
                is_string($oauthError) ? $oauthError : 'unknown_error',
                is_string($request->query('error_description')) ? $request->query('error_description') : null,
            );
        }

        $code = $request->query('code');
        if (! is_string($code) || trim($code) === '') {
            return $this->rejectToLogin($request, 'Kode otorisasi SSO tidak ditemukan.');
        }
        $code = trim($code);

        if ($request->session()->has('sso_state')) {
            $expectedState = (string) $request->session()->pull('sso_state');
            $incomingStateRaw = $request->query('state');
            $incomingState = is_string($incomingStateRaw) ? $incomingStateRaw : '';

            if (! hash_equals($expectedState, $incomingState)) {
                Log::warning('SiPintu SSO callback state mismatch detected (potential CSRF)');

                return $this->rejectToLogin(
                    $request,
                    'Sesi login SSO tidak valid (state mismatch). Silakan coba lagi.',
                );
            }
        }

        $consumedKey = 'sso_code_consumed:'.hash('sha256', $code);
        if (Cache::has($consumedKey)) {
            Log::warning('SiPintu SSO authorization code replay detected (already consumed)');

            return $this->rejectToLogin($request, 'Kode otorisasi SSO sudah pernah digunakan.');
        }

        $gatewayUrl = $this->gatewayUrl();
        $clientId = trim((string) config('services.sipintu.client_id'));
        $clientSecret = trim((string) config('services.sipintu.client_secret'));
        $callbackUrl = $this->callbackUrl();

        if ($gatewayUrl === '' || $clientId === '' || $clientSecret === '') {
            Log::error('SiPintu SSO callback aborted: missing gateway or client configuration');

            return $this->rejectToLogin(
                $request,
                'Konfigurasi SSO SiPintu belum lengkap. Silakan hubungi administrator.',
            );
        }

        try {
            $tokenResult = $this->exchangeAuthorizationCode(
                gatewayUrl: $gatewayUrl,
                clientId: $clientId,
                clientSecret: $clientSecret,
                code: $code,
                callbackUrl: $callbackUrl,
            );

            if ($tokenResult['error'] !== null || $tokenResult['access_token'] === null) {
                return $this->rejectToLogin(
                    $request,
                    $tokenResult['error'] ?? 'Access token SiPintu tidak valid.',
                );
            }

            $profileResult = $this->fetchRemoteUserProfile($gatewayUrl, $tokenResult['access_token']);
            if ($profileResult['error'] !== null || $profileResult['payload'] === null) {
                return $this->rejectToLogin(
                    $request,
                    $profileResult['error'] ?? 'Gagal mengambil data profil pengguna dari SiPintu.',
                );
            }

            $userPayload = $profileResult['payload'];
            $sipintuUserId = $this->extractString($userPayload['id'] ?? null);

            $resolution = $this->resolveLocalUser($userPayload, $sipintuUserId);
            if ($resolution['rejection'] !== null) {
                return $this->rejectToLogin($request, $resolution['rejection']);
            }

            $user = $resolution['user'];
            if ($user === null) {
                Log::warning('SiPintu SSO user authenticated at gateway but not found locally', [
                    'has_email' => $this->extractString($userPayload['email'] ?? null) !== null,
                    'has_nis' => $this->extractString($userPayload['nis'] ?? null) !== null,
                    'has_nip' => $this->extractString($userPayload['nip'] ?? null) !== null,
                    'has_username' => $this->extractString($userPayload['username'] ?? null) !== null,
                    'has_sipintu_user_id' => $sipintuUserId !== null,
                ]);

                return $this->rejectToLogin($request, 'Akun SiPintu belum terdaftar pada aplikasi.');
            }

            $user->loadMissing(['roles', 'dudi', 'siswa', 'guru']);

            $postValidationError = $this->validateMatchedUser($user, $sipintuUserId);
            if ($postValidationError !== null) {
                return $this->rejectToLogin($request, $postValidationError);
            }

            // Regenerate session to prevent session fixation and bypass local force-change-password for SSO
            Auth::login($user, true);
            $request->session()->regenerate();
            $request->session()->put('auth_via_sso', true);

            $this->authenticationService->recordLoginMetadata(
                user: $user,
                ipAddress: $request->ip(),
            );

            Cache::put($consumedKey, true, 600);

            $dashboardUrl = RoleRedirectHelper::getDashboardUrl($user);

            return redirect()->intended($dashboardUrl);
        } catch (ConnectionException $e) {
            Log::warning('SiPintu SSO connection/timeout error in callback', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->rejectToLogin(
                $request,
                'Tidak dapat terhubung ke server SiPintu atau permintaan melebihi batas waktu. Silakan coba lagi.',
            );
        } catch (Throwable $e) {
            Log::error('SiPintu SSO unexpected error in callback', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->rejectToLogin(
                $request,
                'Terjadi kesalahan sistem saat memproses login SSO.',
            );
        }
    }

    /**
     * Handle OAuth authorization errors returned from SiPintu.
     */
    private function handleOAuthError(Request $request, string $error, ?string $description): RedirectResponse
    {
        Log::warning('SiPintu SSO callback returned error', [
            'error' => $error,
        ]);

        $errorMessage = match ($error) {
            'access_denied' => 'Login SSO SiPintu dibatalkan atau ditolak oleh pengguna.',
            'invalid_request' => 'Permintaan otorisasi SSO tidak valid.',
            'invalid_grant' => 'Kode otorisasi SSO tidak valid atau sudah kedaluwarsa.',
            default => 'Login SSO SiPintu gagal: '.($description !== null && $description !== '' ? $description : $error),
        };

        return $this->rejectToLogin($request, $errorMessage);
    }

    /**
     * Exchange authorization code for OAuth access token.
     *
     * @return array{access_token: string|null, error: string|null}
     */
    private function exchangeAuthorizationCode(
        string $gatewayUrl,
        string $clientId,
        string $clientSecret,
        string $code,
        string $callbackUrl,
    ): array {
        $verifySsl = (bool) config('services.sipintu.verify_ssl', true);
        $timeout = (int) config('services.sipintu.timeout', 30);

        $tokenClient = Http::asForm()->timeout($timeout);
        if (! $verifySsl) {
            $tokenClient = $tokenClient->withoutVerifying();
        }

        $tokenResponse = $tokenClient->post($gatewayUrl.'/oauth/token', [
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

            return [
                'access_token' => null,
                'error' => 'Gagal memverifikasi token SSO dengan server SiPintu (HTTP '.$tokenResponse->status().').',
            ];
        }

        $tokenData = $tokenResponse->json();
        $accessToken = is_array($tokenData) ? ($tokenData['access_token'] ?? null) : null;

        if (! is_string($accessToken) || trim($accessToken) === '') {
            return [
                'access_token' => null,
                'error' => 'Access token SiPintu tidak valid.',
            ];
        }

        return [
            'access_token' => $accessToken,
            'error' => null,
        ];
    }

    /**
     * Fetch authenticated user profile from SiPintu API.
     *
     * @return array{payload: array<string, mixed>|null, error: string|null}
     */
    private function fetchRemoteUserProfile(string $gatewayUrl, string $accessToken): array
    {
        $verifySsl = (bool) config('services.sipintu.verify_ssl', true);
        $timeout = (int) config('services.sipintu.timeout', 30);

        $userClient = Http::withToken($accessToken)->acceptJson()->timeout($timeout);
        if (! $verifySsl) {
            $userClient = $userClient->withoutVerifying();
        }

        $userResponse = $userClient->get($gatewayUrl.'/api/v1/user');
        if (! $userResponse->successful()) {
            Log::error('SiPintu SSO fetch user profile failed', [
                'status' => $userResponse->status(),
            ]);

            return [
                'payload' => null,
                'error' => 'Gagal mengambil data profil pengguna dari SiPintu.',
            ];
        }

        $userData = $userResponse->json();
        $rawPayload = is_array($userData) ? ($userData['data'] ?? $userData) : [];
        if (! is_array($rawPayload)) {
            return [
                'payload' => null,
                'error' => 'Format profil pengguna SiPintu tidak valid.',
            ];
        }

        /** @var array<string, mixed> $payload */
        $payload = $rawPayload;

        return [
            'payload' => $payload,
            'error' => null,
        ];
    }

    /**
     * Resolve local user strictly by verified SiPintu DUDI mapping, NIS, NIP, or isolated email/username.
     *
     * @param  array<string, mixed>  $userPayload
     * @return array{user: User|null, rejection: string|null}
     */
    private function resolveLocalUser(array $userPayload, ?string $sipintuUserId): array
    {
        $email = $this->extractString($userPayload['email'] ?? null);
        $nis = $this->extractString($userPayload['nis'] ?? null);
        $nip = $this->extractString($userPayload['nip'] ?? null);
        $username = $this->extractString($userPayload['username'] ?? null);

        // Priority 1: DUDI resolution strictly via mapped sipintu_user_id (Tahap A)
        if ($sipintuUserId !== null) {
            /** @var Dudi|null $mappedDudi */
            $mappedDudi = Dudi::query()
                ->with('user.roles')
                ->where('sipintu_user_id', $sipintuUserId)
                ->first();

            if ($mappedDudi !== null) {
                $dudiUser = $mappedDudi->user;
                if ($dudiUser === null || ! $dudiUser->hasRole(UserRole::DUDI->value)) {
                    Log::warning('SiPintu SSO rejected: mapped DUDI has invalid or missing local DUDI role', [
                        'sipintu_user_id' => $sipintuUserId,
                        'dudi_id' => $mappedDudi->id,
                    ]);

                    return [
                        'user' => null,
                        'rejection' => 'Akun lokal yang terhubung tidak memiliki peran DUDI yang valid. Silakan hubungi Super Admin.',
                    ];
                }

                $dudiError = $this->validateDudiProfileAndActivation($mappedDudi, $sipintuUserId);
                if ($dudiError !== null) {
                    return [
                        'user' => null,
                        'rejection' => $dudiError,
                    ];
                }

                return [
                    'user' => $dudiUser,
                    'rejection' => null,
                ];
            }
        }

        // Prevent a remote SiPintu DUDI user from falling back to Siswa/Guru/Admin accounts
        if ($this->isRemoteDudiPayload($userPayload)) {
            if ($email !== null) {
                /** @var User|null $localDudiUser */
                $localDudiUser = User::query()
                    ->with(['roles', 'dudi'])
                    ->where('email', $email)
                    ->whereHas('roles', fn ($q) => $q->where('name', UserRole::DUDI->value))
                    ->first();

                if ($localDudiUser !== null) {
                    return [
                        'user' => null,
                        'rejection' => $this->unmappedDudiMessage($localDudiUser, $email, $sipintuUserId),
                    ];
                }

                /** @var Dudi|null $localDudiRecord */
                $localDudiRecord = Dudi::query()
                    ->where('email_perusahaan', $email)
                    ->orWhere('sipintu_email', $email)
                    ->first();

                if ($localDudiRecord !== null) {
                    return [
                        'user' => null,
                        'rejection' => ! $localDudiRecord->status_aktif
                            ? 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.'
                            : 'Akun SIPINTU Anda belum terhubung dengan data DUDI SIMONGAN atau belum diaktifkan oleh Super Admin. Silakan hubungi Super Admin untuk verifikasi akun.',
                    ];
                }
            }

            Log::warning('SiPintu SSO rejected: unregistered DUDI account', [
                'has_sipintu_user_id' => $sipintuUserId !== null,
            ]);

            return [
                'user' => null,
                'rejection' => 'Akun DUDI belum didaftarkan oleh pihak sekolah atau belum dihubungkan untuk SSO SiPintu.',
            ];
        }

        // Priority 2: nis in siswa table
        if ($nis !== null) {
            /** @var User|null $siswaUser */
            $siswaUser = User::query()
                ->with(['roles', 'dudi', 'siswa'])
                ->whereHas('siswa', fn ($q) => $q->where('nis', $nis))
                ->first();

            if ($siswaUser !== null) {
                return ['user' => $siswaUser, 'rejection' => null];
            }
        }

        // Priority 3: nip in guru table
        if ($nip !== null) {
            /** @var User|null $guruUser */
            $guruUser = User::query()
                ->with(['roles', 'dudi', 'guru'])
                ->whereHas('guru', fn ($q) => $q->where('nip', $nip))
                ->first();

            if ($guruUser !== null) {
                return ['user' => $guruUser, 'rejection' => null];
            }
        }

        // Priority 4: email in users table (with strict DUDI isolation)
        if ($email !== null) {
            /** @var User|null $matchedEmailUser */
            $matchedEmailUser = User::query()
                ->with(['roles', 'dudi'])
                ->where('email', $email)
                ->first();

            if ($matchedEmailUser !== null) {
                if ($matchedEmailUser->hasRole(UserRole::DUDI->value)) {
                    return [
                        'user' => null,
                        'rejection' => $this->unmappedDudiMessage($matchedEmailUser, $email, $sipintuUserId),
                    ];
                }

                return ['user' => $matchedEmailUser, 'rejection' => null];
            }
        }

        // Priority 5: fallback username attribute (NIS, NIP, or non-DUDI email)
        if ($username !== null) {
            /** @var User|null $byNis */
            $byNis = User::query()
                ->with(['roles', 'dudi', 'siswa'])
                ->whereHas('siswa', fn ($q) => $q->where('nis', $username))
                ->first();
            if ($byNis !== null) {
                return ['user' => $byNis, 'rejection' => null];
            }

            /** @var User|null $byNip */
            $byNip = User::query()
                ->with(['roles', 'dudi', 'guru'])
                ->whereHas('guru', fn ($q) => $q->where('nip', $username))
                ->first();
            if ($byNip !== null) {
                return ['user' => $byNip, 'rejection' => null];
            }

            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                /** @var User|null $matchedUsernameUser */
                $matchedUsernameUser = User::query()
                    ->with(['roles', 'dudi'])
                    ->where('email', $username)
                    ->first();

                if ($matchedUsernameUser !== null) {
                    if ($matchedUsernameUser->hasRole(UserRole::DUDI->value)) {
                        return [
                            'user' => null,
                            'rejection' => $this->unmappedDudiMessage($matchedUsernameUser, $username, $sipintuUserId),
                        ];
                    }

                    return ['user' => $matchedUsernameUser, 'rejection' => null];
                }
            }
        }

        return ['user' => null, 'rejection' => null];
    }

    /**
     * Validate post-match invariants across Super Admin protection and DUDI profile consistency.
     */
    private function validateMatchedUser(User $user, ?string $sipintuUserId): ?string
    {
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            Log::warning('SiPintu SSO rejected for Super Admin account', [
                'user_id' => $user->id,
            ]);

            return 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.';
        }

        if ($user->hasRole(UserRole::DUDI->value)) {
            $dudi = $user->dudi;
            if ($dudi === null) {
                Log::warning('SiPintu SSO rejected: DUDI user lacks local dudi profile', [
                    'user_id' => $user->id,
                ]);

                return 'Data profil DUDI tidak ditemukan untuk akun ini.';
            }

            return $this->validateDudiProfileAndActivation($dudi, $sipintuUserId);
        }

        return null;
    }

    /**
     * Enforce DUDI profile status_aktif (parity with PklLoginRequest) and verified SSO activation.
     */
    private function validateDudiProfileAndActivation(Dudi $dudi, ?string $sipintuUserId): ?string
    {
        if (! $dudi->status_aktif) {
            Log::warning('SiPintu SSO rejected: mapped DUDI is inactive', [
                'dudi_id' => $dudi->id,
            ]);

            return 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.';
        }

        $mappedId = trim((string) ($dudi->sipintu_user_id ?? ''));
        if ($mappedId === '' || $sipintuUserId === null || ! hash_equals($mappedId, $sipintuUserId)) {
            Log::warning('SiPintu SSO rejected: DUDI sipintu_user_id verification failed', [
                'dudi_id' => $dudi->id,
            ]);

            return 'Akun SIPINTU Anda belum terhubung dengan data DUDI SIMONGAN atau belum diaktifkan oleh Super Admin. Silakan hubungi Super Admin untuk verifikasi akun.';
        }

        if (! $dudi->sso_enabled) {
            Log::warning('SiPintu SSO rejected: mapped DUDI SSO is not enabled by Super Admin', [
                'dudi_id' => $dudi->id,
            ]);

            return 'Akses login SSO SiPintu untuk akun DUDI ini belum diaktifkan oleh Super Admin. Silakan hubungi Super Admin untuk verifikasi dan aktivasi.';
        }

        return null;
    }

    private function unmappedDudiMessage(User $user, string $email, ?string $sipintuUserId): string
    {
        Log::warning('SiPintu SSO rejected: DUDI email matched without verified sipintu_user_id mapping', [
            'user_id' => $user->id,
            'has_sipintu_user_id' => $sipintuUserId !== null,
            'email_domain' => str_contains($email, '@') ? substr($email, (int) strpos($email, '@') + 1) : null,
        ]);

        if ($user->dudi === null) {
            return 'Data profil DUDI tidak ditemukan untuk akun ini.';
        }

        if (! $user->dudi->status_aktif) {
            return 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.';
        }

        return 'Akun SIPINTU Anda belum terhubung dengan data DUDI SIMONGAN atau belum diaktifkan oleh Super Admin. Silakan hubungi Super Admin untuk verifikasi akun.';
    }

    /**
     * Detect if remote SiPintu profile carries a DUDI role hint (case-insensitive).
     *
     * @param  array<string, mixed>  $userPayload
     */
    private function isRemoteDudiPayload(array $userPayload): bool
    {
        $role = $userPayload['role'] ?? null;
        if (is_string($role) && strtolower(trim($role)) === 'dudi') {
            return true;
        }

        $roles = $userPayload['roles'] ?? null;
        if (is_array($roles)) {
            foreach ($roles as $entry) {
                if (is_string($entry) && strtolower(trim($entry)) === 'dudi') {
                    return true;
                }
                if (is_array($entry)) {
                    $name = $entry['name'] ?? $entry['slug'] ?? null;
                    if (is_string($name) && strtolower(trim($name)) === 'dudi') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Safely reject authentication back to /login with clean session state.
     */
    private function rejectToLogin(Request $request, string $message): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')
            ->with('error', $message)
            ->withErrors(['login' => $message]);
    }

    private function gatewayUrl(): string
    {
        $configured = config('services.sipintu.api_url') ?: config('services.sipintu.base_url', 'https://sipintu.smkn1bangsri.sch.id');

        return rtrim(is_string($configured) ? $configured : '', '/');
    }

    private function callbackUrl(): string
    {
        $configured = config('services.sipintu.sso_callback_url') ?: config('services.sipintu.redirect_uri');

        return is_string($configured) && trim($configured) !== ''
            ? trim($configured)
            : route('oauth.callback');
    }

    private function extractString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
