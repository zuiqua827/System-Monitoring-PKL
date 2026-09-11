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
    it('redirects with error when query has error parameter', function () {
        $response = $this->get('/auth/callback?error=access_denied&error_description=User+cancelled');

        $response->assertRedirect('/login')
            ->assertSessionHas('error');
    });

    it('redirects with error when authorization code is missing', function () {
        $response = $this->get('/auth/callback');

        $response->assertRedirect('/login')
            ->assertSessionHas('error');
    });

    it('successfully logs in user via /auth/callback when code and user are valid', function () {
        $user = User::factory()->create([
            'email' => 'sso_test_user@smkn1bangsri.sch.id',
            'name' => 'SSO Test User',
            'password' => Hash::make('password'),
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_access_token_12345',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 999,
                    'name' => 'SSO Test User',
                    'email' => 'sso_test_user@smkn1bangsri.sch.id',
                ],
            ], 200),
        ]);

        $response = $this->get('/auth/callback?code=valid_test_code_123');

        $this->assertAuthenticatedAs($user);
    });

    it('redirects with error when user is not found locally', function () {
        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 12345,
                    'name' => 'Unknown Person',
                    'email' => 'nonexistent_user@example.com',
                ],
            ], 200),
        ]);

        $response = $this->get('/auth/callback?code=unknown_code');

        $response->assertRedirect('/login')
            ->assertSessionHas('error');
        $this->assertGuest();
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
