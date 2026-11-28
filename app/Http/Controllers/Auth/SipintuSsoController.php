<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Helpers\RoleRedirectHelper;
use App\Http\Controllers\Controller;
use App\Models\Dudi;
use App\Models\Guru;
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

            $postValidationError = $this->validateMatchedUser(
                $user,
                $sipintuUserId,
                ! empty($resolution['verified_dudi_email_role']),
            );
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
        $timeout = (int) config('services.sipintu.sso_timeout', 15);

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
        $timeout = (int) config('services.sipintu.sso_timeout', 15);

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
        $rawPayload = is_array($userData) ? ($userData['data'] ?? $userData) : null;
        if (! is_array($userData) || ! is_array($rawPayload) || $rawPayload === []) {
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
     * Resolve local user strictly by verified SiPintu DUDI mapping (sipintu_user_id or email+role),
     * NIS, NIP, or isolated email/username.
     *
     * @param  array<string, mixed>  $userPayload
     * @return array{user: User|null, rejection: string|null, verified_dudi_email_role: bool}
     */
    private function resolveLocalUser(array $userPayload, ?string $sipintuUserId): array
    {
        $email = $this->extractString($userPayload['email'] ?? null);
        $normalizedEmail = $email !== null ? strtolower($email) : null;
        $nis = $this->extractString($userPayload['nis'] ?? null);
        $nip = $this->extractString($userPayload['nip'] ?? null);
        $username = $this->extractString($userPayload['username'] ?? null);

        if ($this->isRemoteSuperAdminPayload($userPayload)) {
            return [
                'user' => null,
                'rejection' => 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.',
                'verified_dudi_email_role' => false,
            ];
        }

        // Priority 1: DUDI resolution via mapped sipintu_user_id
        if ($sipintuUserId !== null) {
            /** @var Dudi|null $mappedDudi */
            $mappedDudi = Dudi::query()
                ->withTrashed()
                ->with('user.roles')
                ->where('sipintu_user_id', $sipintuUserId)
                ->first();

            if ($mappedDudi !== null) {
                if ($mappedDudi->trashed()) {
                    return [
                        'user' => null,
                        'rejection' => 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.',
                        'verified_dudi_email_role' => false,
                    ];
                }

                $dudiUser = $mappedDudi->user;
                if ($dudiUser === null) {
                    Log::warning('SiPintu SSO rejected: mapped DUDI has no local User account', [
                        'sipintu_user_id' => $sipintuUserId,
                        'dudi_id' => $mappedDudi->id,
                    ]);

                    return [
                        'user' => null,
                        'rejection' => 'DUDI belum memiliki akun lokal SIMONGAN. Silakan hubungi administrator sekolah.',
                        'verified_dudi_email_role' => false,
                    ];
                }

                if ($dudiUser->hasRole(UserRole::SUPER_ADMIN->value)) {
                    return [
                        'user' => null,
                        'rejection' => 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.',
                        'verified_dudi_email_role' => false,
                    ];
                }

                if (! $dudiUser->hasRole(UserRole::DUDI->value)) {
                    Log::warning('SiPintu SSO rejected: mapped DUDI has invalid local DUDI role', [
                        'sipintu_user_id' => $sipintuUserId,
                        'dudi_id' => $mappedDudi->id,
                    ]);

                    return [
                        'user' => null,
                        'rejection' => 'Akun lokal yang terhubung tidak memiliki peran DUDI yang valid. Silakan hubungi Super Admin.',
                        'verified_dudi_email_role' => false,
                    ];
                }

                $dudiError = $this->validateDudiProfileAndActivation($mappedDudi, $sipintuUserId);
                if ($dudiError !== null) {
                    return [
                        'user' => null,
                        'rejection' => $dudiError,
                        'verified_dudi_email_role' => false,
                    ];
                }

                return [
                    'user' => $dudiUser,
                    'rejection' => null,
                    'verified_dudi_email_role' => false,
                ];
            }
        }

        // Priority 2: Remote DUDI role matched via email & role (only when approved/enabled locally)
        if ($this->isRemoteDudiPayload($userPayload)) {
            if ($normalizedEmail !== null) {
                /** @var User|null $localDudiUser */
                $localDudiUser = User::query()
                    ->with(['roles', 'dudi'])
                    ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                    ->whereHas('roles', fn ($q) => $q->where('name', UserRole::DUDI->value))
                    ->first();

                if ($localDudiUser !== null) {
                    if ($localDudiUser->hasRole(UserRole::SUPER_ADMIN->value)) {
                        return [
                            'user' => null,
                            'rejection' => 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    $linkedDudi = Dudi::query()->withTrashed()->where('user_id', $localDudiUser->id)->first();
                    if ($linkedDudi === null) {
                        return [
                            'user' => null,
                            'rejection' => 'Data profil DUDI tidak ditemukan untuk akun ini.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    if ($linkedDudi->trashed() || ! $linkedDudi->status_aktif) {
                        return [
                            'user' => null,
                            'rejection' => 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    $mappedId = trim((string) ($linkedDudi->sipintu_user_id ?? ''));
                    if (($mappedId !== '' && ($sipintuUserId === null || ! hash_equals($mappedId, $sipintuUserId))) || ! $linkedDudi->sso_enabled) {
                        return [
                            'user' => null,
                            'rejection' => $this->unmappedDudiMessage($localDudiUser, $normalizedEmail, $sipintuUserId),
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    return [
                        'user' => $localDudiUser,
                        'rejection' => null,
                        'verified_dudi_email_role' => true,
                    ];
                }

                /** @var Dudi|null $localDudiRecord */
                $localDudiRecord = Dudi::query()
                    ->withTrashed()
                    ->with('user.roles')
                    ->where(function ($q) use ($normalizedEmail): void {
                        $q->whereRaw('LOWER(email_perusahaan) = ?', [$normalizedEmail])
                            ->orWhereRaw('LOWER(sipintu_email) = ?', [$normalizedEmail]);
                    })
                    ->first();

                if ($localDudiRecord !== null) {
                    if ($localDudiRecord->trashed() || ! $localDudiRecord->status_aktif) {
                        return [
                            'user' => null,
                            'rejection' => 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    $recordUser = $localDudiRecord->user;
                    if ($recordUser === null) {
                        return [
                            'user' => null,
                            'rejection' => 'DUDI belum memiliki akun lokal SIMONGAN. Silakan hubungi administrator sekolah.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    if ($recordUser->hasRole(UserRole::SUPER_ADMIN->value)) {
                        return [
                            'user' => null,
                            'rejection' => 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    if (! $recordUser->hasRole(UserRole::DUDI->value)) {
                        return [
                            'user' => null,
                            'rejection' => 'Akun lokal yang terhubung tidak memiliki peran DUDI yang valid. Silakan hubungi Super Admin.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    $mappedId = trim((string) ($localDudiRecord->sipintu_user_id ?? ''));
                    if (($mappedId !== '' && ($sipintuUserId === null || ! hash_equals($mappedId, $sipintuUserId))) || ! $localDudiRecord->sso_enabled) {
                        return [
                            'user' => null,
                            'rejection' => 'Akun SIPINTU Anda belum terhubung dengan data DUDI SIMONGAN atau belum diaktifkan oleh Super Admin. Silakan hubungi Super Admin untuk verifikasi akun.',
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    return [
                        'user' => $recordUser,
                        'rejection' => null,
                        'verified_dudi_email_role' => true,
                    ];
                }
            }

            Log::warning('SiPintu SSO rejected: unregistered DUDI account', [
                'has_sipintu_user_id' => $sipintuUserId !== null,
            ]);

            return [
                'user' => null,
                'rejection' => 'Akun DUDI belum didaftarkan oleh pihak sekolah atau belum dihubungkan untuk SSO SiPintu.',
                'verified_dudi_email_role' => false,
            ];
        }

        // Priority 3: nis in siswa table
        if ($nis !== null) {
            /** @var User|null $siswaUser */
            $siswaUser = User::query()
                ->with(['roles', 'dudi', 'siswa'])
                ->whereHas('siswa', fn ($q) => $q->where('nis', $nis))
                ->first();

            if ($siswaUser !== null) {
                return ['user' => $siswaUser, 'rejection' => null, 'verified_dudi_email_role' => false];
            }
        }

        // Priority 4: nip in guru table (preserving soft-delete lock)
        if ($nip !== null) {
            if (Guru::query()->onlyTrashed()->where('nip', $nip)->exists()) {
                return [
                    'user' => null,
                    'rejection' => 'Akun Guru Anda sedang dinonaktifkan. Silakan hubungi administrator.',
                    'verified_dudi_email_role' => false,
                ];
            }

            /** @var User|null $guruUser */
            $guruUser = User::query()
                ->with(['roles', 'dudi', 'guru'])
                ->whereHas('guru', fn ($q) => $q->where('nip', $nip))
                ->first();

            if ($guruUser !== null) {
                return ['user' => $guruUser, 'rejection' => null, 'verified_dudi_email_role' => false];
            }
        }

        // Priority 5: email in users table (case-insensitive, with strict DUDI isolation)
        if ($normalizedEmail !== null) {
            /** @var User|null $matchedEmailUser */
            $matchedEmailUser = User::query()
                ->with(['roles', 'dudi', 'guru', 'siswa'])
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->first();

            if ($matchedEmailUser !== null) {
                if ($matchedEmailUser->hasRole(UserRole::DUDI->value)) {
                    return [
                        'user' => null,
                        'rejection' => $this->unmappedDudiMessage($matchedEmailUser, $normalizedEmail, $sipintuUserId),
                        'verified_dudi_email_role' => false,
                    ];
                }

                return ['user' => $matchedEmailUser, 'rejection' => null, 'verified_dudi_email_role' => false];
            }
        }

        // Priority 6: fallback username attribute (NIS, NIP, or non-DUDI email)
        if ($username !== null) {
            /** @var User|null $byNis */
            $byNis = User::query()
                ->with(['roles', 'dudi', 'siswa'])
                ->whereHas('siswa', fn ($q) => $q->where('nis', $username))
                ->first();
            if ($byNis !== null) {
                return ['user' => $byNis, 'rejection' => null, 'verified_dudi_email_role' => false];
            }

            /** @var User|null $byNip */
            $byNip = User::query()
                ->with(['roles', 'dudi', 'guru'])
                ->whereHas('guru', fn ($q) => $q->where('nip', $username))
                ->first();
            if ($byNip !== null) {
                return ['user' => $byNip, 'rejection' => null, 'verified_dudi_email_role' => false];
            }

            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                /** @var User|null $matchedUsernameUser */
                $matchedUsernameUser = User::query()
                    ->with(['roles', 'dudi', 'guru', 'siswa'])
                    ->whereRaw('LOWER(email) = ?', [strtolower($username)])
                    ->first();

                if ($matchedUsernameUser !== null) {
                    if ($matchedUsernameUser->hasRole(UserRole::DUDI->value)) {
                        return [
                            'user' => null,
                            'rejection' => $this->unmappedDudiMessage($matchedUsernameUser, $username, $sipintuUserId),
                            'verified_dudi_email_role' => false,
                        ];
                    }

                    return ['user' => $matchedUsernameUser, 'rejection' => null, 'verified_dudi_email_role' => false];
                }
            }
        }

        return ['user' => null, 'rejection' => null, 'verified_dudi_email_role' => false];
    }

    /**
     * Validate post-match invariants across Super Admin protection, Guru soft-delete protection, and DUDI profile consistency.
     */
    private function validateMatchedUser(User $user, ?string $sipintuUserId, bool $verifiedDudiEmailRole = false): ?string
    {
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            Log::warning('SiPintu SSO rejected for Super Admin account', [
                'user_id' => $user->id,
            ]);

            return 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.';
        }

        if ($user->hasRole(UserRole::GURU->value) && $user->guru === null) {
            if (Guru::query()->onlyTrashed()->where('user_id', $user->id)->exists()) {
                return 'Akun Guru Anda sedang dinonaktifkan. Silakan hubungi administrator.';
            }
        }

        if ($user->hasRole(UserRole::DUDI->value)) {
            $dudi = $user->dudi;
            if ($dudi === null) {
                if (Dudi::query()->onlyTrashed()->where('user_id', $user->id)->exists()) {
                    return 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.';
                }

                Log::warning('SiPintu SSO rejected: DUDI user lacks local dudi profile', [
                    'user_id' => $user->id,
                ]);

                return 'Data profil DUDI tidak ditemukan untuk akun ini.';
            }

            return $this->validateDudiProfileAndActivation($dudi, $sipintuUserId, $verifiedDudiEmailRole);
        }

        return null;
    }

    /**
     * Enforce DUDI profile status_aktif (parity with PklLoginRequest) and verified SSO activation.
     */
    private function validateDudiProfileAndActivation(Dudi $dudi, ?string $sipintuUserId, bool $verifiedDudiEmailRole = false): ?string
    {
        if ($dudi->trashed() || ! $dudi->status_aktif) {
            Log::warning('SiPintu SSO rejected: mapped DUDI is inactive', [
                'dudi_id' => $dudi->id,
            ]);

            return 'Akun DUDI Anda sedang dinonaktifkan. Silakan hubungi pihak sekolah.';
        }

        $mappedId = trim((string) ($dudi->sipintu_user_id ?? ''));
        if ($mappedId !== '') {
            if ($sipintuUserId === null || ! hash_equals($mappedId, $sipintuUserId)) {
                Log::warning('SiPintu SSO rejected: DUDI sipintu_user_id verification failed', [
                    'dudi_id' => $dudi->id,
                ]);

                return 'Akun SIPINTU Anda belum terhubung dengan data DUDI SIMONGAN atau belum diaktifkan oleh Super Admin. Silakan hubungi Super Admin untuk verifikasi akun.';
            }
        } elseif (! $verifiedDudiEmailRole) {
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
     * Detect if remote SiPintu profile carries a Super Admin / Admin role hint.
     *
     * @param  array<string, mixed>  $userPayload
     */
    private function isRemoteSuperAdminPayload(array $userPayload): bool
    {
        $adminHints = ['super_admin', 'super admin', 'superadmin'];

        $role = $userPayload['role'] ?? null;
        if (is_string($role) && in_array(strtolower(trim($role)), $adminHints, true)) {
            return true;
        }

        $roles = $userPayload['roles'] ?? null;
        if (is_array($roles)) {
            foreach ($roles as $entry) {
                if (is_string($entry) && in_array(strtolower(trim($entry)), $adminHints, true)) {
                    return true;
                }
                if (is_array($entry)) {
                    $name = $entry['name'] ?? $entry['slug'] ?? null;
                    if (is_string($name) && in_array(strtolower(trim($name)), $adminHints, true)) {
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
        $configured = config('services.sipintu.api_url') ?: config('services.sipintu.base_url');
        $url = rtrim(is_string($configured) ? trim($configured) : '', '/');

        if ($url === '') {
            return '';
        }

        if (app()->isProduction()) {
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return '';
            }
        }

        return $url;
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

