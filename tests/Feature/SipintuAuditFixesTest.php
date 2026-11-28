<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SipintuAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }

        config([
            'services.sipintu.api_url' => 'https://sipintu.test',
            'services.sipintu.client_id' => 'test_client_id',
            'services.sipintu.client_secret' => 'test_client_secret',
            'services.sipintu.webhook_secret' => 'separate_webhook_secret',
            'services.sipintu.verify_ssl' => true,
            'services.sipintu.timeout' => 30,
            'services.sipintu.sso_timeout' => 15,
            'services.sipintu.per_page' => 2,
        ]);

        Cache::flush();
    }

    public function test_migrate_student_passwords_defaults_to_dry_run_and_does_not_modify_database(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('custom-secret'),
            'must_change_password' => true,
        ]);
        $user->assignRole(UserRole::SISWA->value);

        $kelas = Kelas::factory()->create();
        Siswa::factory()->create([
            'user_id' => $user->id,
            'class_id' => $kelas->id,
        ]);

        $this->artisan('sipintu:migrate-student-passwords')
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue(Hash::check('custom-secret', (string) $user->password));
        $this->assertTrue((bool) $user->must_change_password);
    }

    public function test_migrate_student_passwords_execute_updates_when_confirmed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('custom-secret'),
            'must_change_password' => true,
        ]);
        $user->assignRole(UserRole::SISWA->value);

        $kelas = Kelas::factory()->create();
        Siswa::factory()->create([
            'user_id' => $user->id,
            'class_id' => $kelas->id,
        ]);

        $this->artisan('sipintu:migrate-student-passwords --execute')
            ->expectsConfirmation('Apakah Anda yakin ingin melanjutkan?', 'yes')
            ->expectsOutputToContain('[EKSEKUSI]')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue(Hash::check('password', (string) $user->password));
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_migrate_student_passwords_is_blocked_in_production_without_force(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('sipintu:migrate-student-passwords --execute')
            ->expectsOutputToContain('DIBLOKIR di production')
            ->assertExitCode(1);
    }

    public function test_config_has_no_localhost_fallback_and_reports_clear_error_when_url_empty(): void
    {
        // Verify services.php does not fall back to localhost:8000 when empty
        $servicesConfig = require base_path('config/services.php');
        if (empty(env('SIPINTU_API_URL')) && empty(env('SIPINTU_BASE_URL'))) {
            $this->assertNull($servicesConfig['sipintu']['api_url']);
        }

        config(['services.sipintu.api_url' => null]);

        /** @var SipintuSyncServiceInterface $syncService */
        $syncService = app(SipintuSyncServiceInterface::class);
        $result = $syncService->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('belum dikonfigurasi', strtolower($result['message']));

        // SSO redirect should also reject cleanly without hitting localhost:8000
        $response = $this->get(route('auth.sipintu.redirect'));
        $response->assertRedirect();
    }

    public function test_sso_timeout_defaults_to_15_seconds(): void
    {
        $servicesConfig = require base_path('config/services.php');
        $this->assertSame(15, $servicesConfig['sipintu']['sso_timeout']);
    }

    public function test_student_sync_handles_pagination_tanggal_lahir_formats_and_is_idempotent_with_absent_optional_fields(): void
    {
        $kelas = Kelas::factory()->create(['nama' => 'XII RPL 1', 'tingkat' => 'XII']);
        SipintuClassroomMapping::query()->create([
            'classroom_id' => 1,
            'kelas_id' => $kelas->id,
        ]);

        // Pre-seed a student whose optional fields exist locally, but SiPintu omits them on sync (38-students phantom update regression)
        $existingUser = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => Siswa::generateEmail('10001'),
        ]);
        $existingUser->assignRole(UserRole::SISWA->value);
        Siswa::factory()->create([
            'user_id' => $existingUser->id,
            'class_id' => $kelas->id,
            'nis' => '10001',
            'nisn' => '0010001',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2008-05-15',
            'no_telepon' => '081234567890',
            'alamat' => 'Jl. Merdeka No. 1',
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $page = (int) ($query['page'] ?? 1);

            if ($page === 2) {
                return Http::response([
                    'data' => [
                        [
                            'nis' => '10003',
                            'nama' => 'Andi Wijaya',
                            'jenis_kelamin' => 'L',
                            'tanggal_lahir' => '20080910', // Ymd format
                            'classroom_id' => 1,
                            'classroom' => ['id' => 1, 'name' => 'XII RPL 1', 'level' => 'XII'],
                        ],
                    ],
                    'meta' => ['current_page' => 2, 'last_page' => 2, 'total' => 3],
                ], 200);
            }

            return Http::response([
                'data' => [
                    [
                        'nis' => '10001',
                        'nama' => 'Budi Santoso',
                        'jenis_kelamin' => 'L',
                        'tanggal_lahir' => '15-05-2008', // d-m-Y format
                        // no_telepon, alamat, nisn intentionally omitted!
                        'classroom_id' => 1,
                        'classroom' => ['id' => 1, 'name' => 'XII RPL 1', 'level' => 'XII'],
                    ],
                    [
                        'nis' => '10002',
                        'nisn' => '0010002',
                        'nama' => 'Siti Aminah',
                        'jenis_kelamin' => 'P',
                        'tanggal_lahir' => '2008/08/20', // Y/m/d format
                        'no_telepon' => '089876543210',
                        'classroom_id' => 1,
                        'classroom' => ['id' => 1, 'name' => 'XII RPL 1', 'level' => 'XII'],
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 2, 'total' => 3],
            ], 200);
        });

        /** @var SiPintuServiceInterface $service */
        $service = app(SiPintuServiceInterface::class);

        // First sync: 10001 unchanged (local optional fields preserved!), 10002 & 10003 created
        $first = $service->syncStudents();
        $this->assertSame(2, $first['created']);
        $this->assertSame(0, $first['updated']);
        $this->assertSame(1, $first['unchanged']);

        $siti = Siswa::where('nis', '10002')->firstOrFail();
        $andi = Siswa::where('nis', '10003')->firstOrFail();
        $this->assertSame('2008-08-20', $siti->tanggal_lahir?->format('Y-m-d'));
        $this->assertSame('2008-09-10', $andi->tanggal_lahir?->format('Y-m-d'));

        $budi = Siswa::where('nis', '10001')->firstOrFail();
        $this->assertSame('081234567890', $budi->no_telepon);
        $this->assertSame('Jl. Merdeka No. 1', $budi->alamat);

        // Second sync with identical payload: MUST produce 0 created and 0 updated
        $second = $service->syncStudents();
        $this->assertSame(0, $second['created']);
        $this->assertSame(0, $second['updated']);
        $this->assertSame(3, $second['unchanged']);
    }

    public function test_teacher_sync_stores_and_is_idempotent_on_second_run(): void
    {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
                'data' => [
                    [
                        'nip' => '198501012010011001',
                        'nama' => 'Pak Ahmad, S.Kom',
                        'email' => 'ahmad@smkn1bangsri.sch.id',
                        'no_hp' => '081111222333',
                        'jenis_kelamin' => 'L',
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ], 200),
        ]);

        /** @var SiPintuServiceInterface $service */
        $service = app(SiPintuServiceInterface::class);

        $first = $service->syncTeachers();
        $this->assertSame(1, $first['created']);
        $this->assertDatabaseHas('guru', ['nip' => '198501012010011001', 'nama' => 'Pak Ahmad, S.Kom']);

        $second = $service->syncTeachers();
        $this->assertSame(0, $second['created']);
        $this->assertSame(0, $second['updated']);
        $this->assertSame(1, $second['unchanged']);
    }

    public function test_dudi_sync_respects_create_missing_dudi_flag_and_is_idempotent(): void
    {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
                'data' => [
                    [
                        'id' => 901,
                        'nama_perusahaan' => 'PT Maju Jaya',
                        'email' => 'hrd@majujaya.co.id',
                        'penanggung_jawab' => 'Ibu Rina',
                        'no_telepon' => '0291123456',
                        'alamat' => 'Jl. Industri 1',
                        'status_aktif' => true,
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1],
            ], 200),
        ]);

        /** @var SiPintuServiceInterface $service */
        $service = app(SiPintuServiceInterface::class);

        // When createMissing = false (default link-only): new DUDI is skipped
        $linkOnly = $service->syncDudi(allowCreate: false);
        $this->assertSame(0, $linkOnly['created']);
        $this->assertSame(1, $linkOnly['skipped']);
        $this->assertDatabaseMissing('dudi', ['sipintu_id' => 901]);

        // When createMissing = true: new DUDI is created
        $createdRun = $service->syncDudi(allowCreate: true);
        $this->assertSame(1, $createdRun['created']);
        $this->assertDatabaseHas('dudi', ['sipintu_id' => 901, 'nama_perusahaan' => 'PT Maju Jaya']);
        $this->assertDatabaseHas('users', ['email' => 'hrd@majujaya.co.id']);

        // Second sync: 0 changes
        $secondRun = $service->syncDudi(allowCreate: true);
        $this->assertSame(0, $secondRun['created']);
        $this->assertSame(0, $secondRun['updated']);
        $this->assertSame(1, $secondRun['unchanged']);
    }

    public function test_empty_data_and_http_errors_401_404_500_and_timeout_are_handled_gracefully(): void
    {
        /** @var SipintuSyncServiceInterface $syncService */
        $syncService = app(SipintuSyncServiceInterface::class);

        // 1. Empty 200
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => [], 'meta' => ['current_page' => 1, 'last_page' => 1]], 200),
        ]);
        $emptyStats = app(SiPintuServiceInterface::class)->syncStudents();
        $this->assertSame(0, $emptyStats['created']);
        $this->assertSame(0, $emptyStats['updated']);

        // 2. Probe statuses: 401, 404, 500, and ConnectionException timeout
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
            'https://sipintu.test/api/v1/sijuna/students*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'Not Found'], 404),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Server Error'], 500),
        ]);

        Cache::forget('sipintu_live_diagnostics');
        $diag = $syncService->testConnection();
        $this->assertTrue($diag['success']); // Gateway /health is 200
        $this->assertSame('timeout', $diag['endpoints']['students']['error_type']);
        $this->assertSame('not_found', $diag['endpoints']['teachers']['error_type']);
        $this->assertSame(404, $diag['endpoints']['teachers']['http_status']);
        $this->assertSame('server', $diag['endpoints']['dudi']['error_type']);
        $this->assertSame(500, $diag['endpoints']['dudi']['http_status']);
    }

    public function test_health_401_unauthorized_is_reported_cleanly(): void
    {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        Cache::forget('sipintu_live_diagnostics');
        $unauth = app(SipintuSyncServiceInterface::class)->testConnection();

        $this->assertFalse($unauth['success']);
        $this->assertSame(401, $unauth['http_status']);
    }

    public function test_both_webhook_urls_use_unified_handler_and_succeed(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'siswa.webhook@smkn1bangsri.sch.id',
        ]);
        $user->assignRole(UserRole::SISWA->value);
        $kelas = Kelas::factory()->create();
        Siswa::factory()->create([
            'user_id' => $user->id,
            'class_id' => $kelas->id,
            'nis' => '88881',
            'nama' => 'Nama Lama',
        ]);

        foreach (['/api/v1/sipintu/sync-user', '/api/webhook/sipintu/user-sync'] as $idx => $url) {
            $newName = "Nama Baru {$idx}";
            $response = $this->postJson($url, [
                'data' => [
                    'nis' => '88881',
                    'nama' => $newName,
                ],
            ], [
                'X-Webhook-Secret' => 'separate_webhook_secret',
            ]);

            $response->assertStatus(200)->assertJson([
                'success' => true,
                'action' => 'updated',
            ]);
            $this->assertSame($newName, $user->fresh()->name);
        }
    }
}
