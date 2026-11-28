<?php

declare(strict_types=1);

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

describe('Health Check Endpoint', function () {
    it('returns 200 ok for /health with status and downstream info', function () {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'app' => ['name', 'env'],
                'services' => [
                    'database' => ['status', 'message'],
                    'downstream' => ['url', 'sso_callback'],
                ],
            ])
            ->assertJson([
                'status' => 'ok',
                'services' => [
                    'database' => [
                        'status' => 'up',
                    ],
                ],
            ]);
    });

    it('returns 200 ok for /api/v1/health', function () {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    });
});

describe('SiPintu SSO Callback Endpoint', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleSeeder::class);
    });

    it('safely rejects GET /oauth/callback when authorization code is missing', function () {
        $response = $this->get('/oauth/callback');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Kode otorisasi SSO tidak ditemukan.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('safely rejects GET /auth/callback when authorization code is missing', function () {
        $response = $this->get('/auth/callback');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Kode otorisasi SSO tidak ditemukan.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('redirects with error when query has error parameter like access_denied', function () {
        $response = $this->get('/oauth/callback?error=access_denied&error_description=User+cancelled');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Login SSO SiPintu dibatalkan atau ditolak oleh pengguna.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('redirects with error when query has error parameter like invalid_request', function () {
        $response = $this->get('/oauth/callback?error=invalid_request');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Permintaan otorisasi SSO tidak valid.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('exchanges authorization code via POST /oauth/token with expected parameters', function () {
        $user = User::factory()->create([
            'email' => 'token_exchange_test@smkn1bangsri.sch.id',
            'name' => 'Exchange User',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_abc123',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 101,
                    'name' => 'Exchange User',
                    'email' => 'token_exchange_test@smkn1bangsri.sch.id',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=valid_exchange_code_001');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/oauth/token')
                && $request->method() === 'POST'
                && $request['grant_type'] === 'authorization_code'
                && $request['code'] === 'valid_exchange_code_001';
        });

        $this->assertAuthenticatedAs($user);
    });

    it('handles token exchange failure gracefully without redirect loop', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Authorization code expired',
            ], 400),
        ]);

        $response = $this->get('/oauth/callback?code=expired_code_123');

        $response->assertRedirect('/login')
            ->assertSessionHas('error')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('handles user profile endpoint failure gracefully', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_success',
            ], 200),
            '*/api/v1/user' => Http::response([
                'message' => 'Unauthorized or internal error',
            ], 500),
        ]);

        $response = $this->get('/oauth/callback?code=valid_token_but_user_error');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Gagal mengambil data profil pengguna dari SiPintu.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('successfully logs in local user matched by email with remember me enabled', function () {
        $user = User::factory()->create([
            'email' => 'sso_email_user@smkn1bangsri.sch.id',
            'name' => 'SSO Email User',
            'password' => Hash::make('password'),
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_email',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 201,
                    'name' => 'SSO Email User',
                    'email' => 'sso_email_user@smkn1bangsri.sch.id',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_email_match_001');

        $this->assertAuthenticatedAs($user);
    });

    it('successfully logs in local user matched by Siswa NIS', function () {
        $user = User::factory()->create([
            'email' => 'siswa_nis_user@smkn1bangsri.sch.id',
            'name' => 'Siswa NIS User',
        ]);
        Siswa::factory()->create([
            'user_id' => $user->id,
            'nis' => '99887766',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_nis',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 301,
                    'nis' => '99887766',
                    'name' => 'Siswa NIS User',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_nis_match_002');

        $this->assertAuthenticatedAs($user);
    });

    it('successfully logs in local user matched by Guru NIP', function () {
        $user = User::factory()->create([
            'email' => 'guru_nip_user@smkn1bangsri.sch.id',
            'name' => 'Guru NIP User',
        ]);
        Guru::factory()->create([
            'user_id' => $user->id,
            'nip' => '198001012005011001',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_nip',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 401,
                    'nip' => '198001012005011001',
                    'name' => 'Guru NIP User',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_nip_match_003');

        $this->assertAuthenticatedAs($user);
    });

    it('rejects SSO and does NOT create account if user is not found locally', function () {
        $userCountBefore = User::query()->count();

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_unknown',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 99999,
                    'name' => 'Ghost User',
                    'email' => 'nonexistent_ghost_user@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=unknown_user_code_999');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Akun SiPintu belum terdaftar pada aplikasi.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
        expect(User::query()->count())->toBe($userCountBefore);
    });

    it('rejects callback if sso_state in session does not match request state', function () {
        $response = $this->withSession(['sso_state' => 'correct_secret_state'])
            ->get('/oauth/callback?code=some_code&state=wrong_state');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Sesi login SSO tidak valid (state mismatch). Silakan coba lagi.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('prevents authorization code replay attacks', function () {
        $user = User::factory()->create([
            'email' => 'replay_test@smkn1bangsri.sch.id',
            'name' => 'Replay User',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_replay',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'email' => 'replay_test@smkn1bangsri.sch.id',
                ],
            ], 200),
        ]);

        // First attempt with the code succeeds
        $res1 = $this->get('/oauth/callback?code=replayed_code_unique_123');
        $this->assertAuthenticatedAs($user);

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Second attempt with the identical code should be rejected immediately as replay
        $res2 = $this->get('/oauth/callback?code=replayed_code_unique_123');
        $res2->assertRedirect('/login')
            ->assertSessionHas('error', 'Kode otorisasi SSO sudah pernah digunakan.');
        $this->assertGuest();
    });

    it('allows retry if the first token exchange attempt fails', function () {
        $user = User::factory()->create([
            'email' => 'retry_test@smkn1bangsri.sch.id',
            'name' => 'Retry User',
        ]);

        // 1st request: token exchange fails (e.g. temporary server error)
        Http::fakeSequence()
            ->push(['error' => 'server_error'], 500)
            ->push(['access_token' => 'mock_token_retry_ok'], 200)
            ->push(['data' => ['email' => 'retry_test@smkn1bangsri.sch.id']], 200);

        $res1 = $this->get('/oauth/callback?code=retryable_code_456');
        $res1->assertRedirect('/login');
        $this->assertGuest();

        // 2nd request: legitimate retry with the same code should be processed, not rejected as replay
        $res2 = $this->get('/oauth/callback?code=retryable_code_456');
        $this->assertAuthenticatedAs($user);
    });

    it('rejects Super Admin accounts from logging in via SSO', function () {
        $admin = User::factory()->create([
            'email' => 'superadmin_sso@smkn1bangsri.sch.id',
            'name' => 'Super Admin Person',
        ]);
        $admin->assignRole('Super Admin');

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_admin_token',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'email' => 'superadmin_sso@smkn1bangsri.sch.id',
                    'name' => 'Super Admin Person',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=super_admin_code_789');

        $response->assertRedirect('/login')
            ->assertSessionHas('error', 'Akun Administrator tidak dapat masuk melalui SSO SiPintu. Silakan gunakan login admin SIMONGAN.')
            ->assertSessionHasErrors(['login']);
        $this->assertGuest();
    });

    it('does not force SSO user to change password even if must_change_password is true', function () {
        $user = User::factory()->create([
            'email' => 'sso_siswa_firstlogin@smkn1bangsri.sch.id',
            'name' => 'First Login Siswa',
            'must_change_password' => true,
        ]);
        $user->assignRole('Siswa');
        Siswa::factory()->create([
            'user_id' => $user->id,
            'nama' => 'First Login Siswa',
            'nis' => '77889900',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_firstlogin_token',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'email' => 'sso_siswa_firstlogin@smkn1bangsri.sch.id',
                    'name' => 'First Login Siswa',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=firstlogin_code_123');

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/siswa/dashboard');

        // Accessing a page should NOT redirect to /force-change-password because session has auth_via_sso
        $dashResponse = $this->get('/siswa/dashboard');
        $dashResponse->assertStatus(200);
    });

    it('does not leak client_secret in session or redirect response', function () {
        $clientSecret = config('services.sipintu.client_secret');

        $response = $this->get('/oauth/callback?code=missing_secret_test');

        if (! empty($clientSecret)) {
            $response->assertDontSee($clientSecret);
            expect(session()->get('error'))->not->toContain($clientSecret);
        }
    });

    it('ensures /login does not redirect automatically to SiPintu', function () {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('https://sipintu.smkn1bangsri.sch.id');
    });
});

describe('SiPintu Realtime Webhook User Synchronization', function () {
    beforeEach(function () {
        config(['services.sipintu.webhook_secret' => 'test_webhook_secret_key']);
        \Spatie\Permission\Models\Role::findOrCreate(\App\Enums\UserRole::SUPER_ADMIN->value);
        \Spatie\Permission\Models\Role::findOrCreate(\App\Enums\UserRole::DUDI->value);
    });

    it('rejects unauthenticated webhook requests with 401', function () {
        $response = $this->postJson('/api/v1/sipintu/sync-user', [
            'data' => ['name' => 'Unauthorized'],
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    });

    it('accepts webhook authenticated via X-Webhook-Secret', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'webhook_test@smkn1bangsri.sch.id',
        ]);

        $response = $this->withHeaders([
            'X-Webhook-Secret' => 'test_webhook_secret_key',
        ])->postJson('/api/v1/sipintu/sync-user', [
            'event' => 'user.updated',
            'data' => [
                'email' => 'webhook_test@smkn1bangsri.sch.id',
                'name' => 'Updated Name from SiPintu',
                'phone' => '081234567890',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'updated',
            ]);

        $user->refresh();
        expect($user->name)->toBe('Updated Name from SiPintu')
            ->and($user->phone)->toBe('081234567890');
    });

    it('is idempotent when same payload is delivered multiple times', function () {
        $user = User::factory()->create([
            'email' => 'idempotent@smkn1bangsri.sch.id',
            'name' => 'Initial Name',
        ]);

        $payload = [
            'data' => [
                'email' => 'idempotent@smkn1bangsri.sch.id',
                'name' => 'Idempotent Name',
            ],
        ];

        // First delivery
        $res1 = $this->withHeaders(['X-Webhook-Secret' => 'test_webhook_secret_key'])
            ->postJson('/api/v1/sipintu/sync-user', $payload);
        $res1->assertStatus(200);

        // Second delivery (duplicate)
        $res2 = $this->withHeaders(['X-Webhook-Secret' => 'test_webhook_secret_key'])
            ->postJson('/api/v1/sipintu/sync-user', $payload);
        $res2->assertStatus(200)
            ->assertJson(['success' => true, 'action' => 'updated']);

        expect(User::query()->where('email', 'idempotent@smkn1bangsri.sch.id')->count())->toBe(1);
    });

    it('protects Super Admin and DUDI from external modification', function () {
        $admin = User::factory()->create([
            'email' => 'admin_protect@smkn1bangsri.sch.id',
            'name' => 'Super Admin Safe',
        ]);
        $admin->assignRole('Super Admin');

        $response = $this->withHeaders(['X-Webhook-Secret' => 'test_webhook_secret_key'])
            ->postJson('/api/v1/sipintu/sync-user', [
                'data' => [
                    'email' => 'admin_protect@smkn1bangsri.sch.id',
                    'name' => 'Hacked Name',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['action' => 'protected']);

        $admin->refresh();
        expect($admin->name)->toBe('Super Admin Safe');
    });

    it('safely skips if user does not exist locally without corrupting data', function () {
        $response = $this->withHeaders(['X-Webhook-Secret' => 'test_webhook_secret_key'])
            ->postJson('/api/v1/sipintu/sync-user', [
                'data' => [
                    'email' => 'ghost_person@smkn1bangsri.sch.id',
                    'name' => 'Ghost Person',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['action' => 'skipped']);
    });
});
