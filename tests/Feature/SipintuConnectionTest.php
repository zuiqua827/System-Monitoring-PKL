<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\SiPintuApiException;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Services\SiPintuService;
use App\Services\SipintuSyncService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

describe('SiPintu Connection & Diagnosis Test Suite', function () {
    beforeEach(function () {
        config([
            'services.sipintu.api_url' => 'https://sipintu.test',
            'services.sipintu.client_id' => 'test_client_id_123',
            'services.sipintu.client_secret' => 'test_client_secret_xyz',
            'services.sipintu.timeout' => 5,
        ]);
    });

    it('1. Test Connection succeeds using /api/v1/health without fetching entire student list', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response([
                'status' => 'online',
                'gateway' => 'SiPintu REST API Gateway',
                'version' => '1.0.0',
                'client_connection' => [
                    'registered' => true,
                    'client_id' => 'test_client_id_123',
                    'name' => 'SiMongan',
                    'status' => 'connected',
                ],
                'message' => 'REST API Gateway aktif dan siap melayani request.',
            ], 200),
            // Ensure /students is NOT called
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response([], 500),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeTrue()
            ->and($result['http_status'])->toBe(200)
            ->and($result['message'])->toContain('Koneksi ke SiPintu berhasil');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/health');
        });

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/api/v1/sijuna/students');
        });
    });

    it('2. Test Connection handles cURL error 28 timeout (0 bytes received) gracefully', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 5002 milliseconds with 0 bytes received for https://sipintu.test/api/v1/health');
            },
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['error_type'])->toBe('timeout')
            ->and($result['message'])->toBe('Request ke server SiPintu melebihi batas waktu dan belum menerima response.')
            ->and($result['troubleshooting'])->toContain('0 bytes received');
    });

    it('3. Test Connection diagnoses HTTP 530 Cloudflare Error 1033 Origin Unreachable', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('error code: 1033', 530),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(530)
            ->and($result['message'])->toContain('HTTP 530 / Cloudflare Error 1033')
            ->and($result['detail'])->toContain('Cloudflare Edge dapat dijangkau tetapi origin/tunnel SiPintu tidak memberikan response.');
    });

    it('4. Test Connection diagnoses HTTP 500 Internal Server Error', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('Internal server error', 500),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(500)
            ->and($result['error_type'])->toBe('server')
            ->and($result['message'])->toContain('HTTP 500');
    });

    it('5. Test Connection diagnoses HTTP 502 Bad Gateway', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('Bad Gateway', 502),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(502)
            ->and($result['error_type'])->toBe('server')
            ->and($result['message'])->toContain('HTTP 502');
    });

    it('6. Test Connection diagnoses HTTP 503 Service Unavailable', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('Service Unavailable', 503),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(503)
            ->and($result['error_type'])->toBe('server')
            ->and($result['message'])->toContain('HTTP 503');
    });

    it('7. Test Connection diagnoses HTTP 401 Unauthorized', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('Unauthorized', 401),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(401)
            ->and($result['error_type'])->toBe('authentication')
            ->and($result['message'])->toContain('Autentikasi SiPintu gagal');
    });

    it('8. Test Connection diagnoses HTTP 403 Forbidden', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('Forbidden', 403),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(403)
            ->and($result['error_type'])->toBe('permission')
            ->and($result['message'])->toContain('Akses SiPintu ditolak');
    });

    it('9. Test Connection diagnoses HTTP 404 Endpoint Not Found', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response('Not Found', 404),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $result = $repo->testConnection();

        expect($result['success'])->toBeFalse()
            ->and($result['http_status'])->toBe(404)
            ->and($result['error_type'])->toBe('endpoint')
            ->and($result['message'])->toContain('Endpoint SiPintu tidak ditemukan');
    });

    it('10. fetchStudents throws specific error on Invalid JSON or malformed envelope', function () {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response('<!DOCTYPE html><html><body>Error Page</body></html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);

        expect(fn () => $repo->fetchStudents())
            ->toThrow(SiPintuApiException::class, 'format JSON tidak dikenali');
    });

    it('11. fetchStudents handles empty record list without failing or deleting data', function () {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
                'status' => 'success',
                'data' => [],
            ], 200),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);
        $students = $repo->fetchStudents();

        expect($students)->toBeArray()->toBeEmpty();
    });

    it('12. checkEndpointStatus checks individual endpoint status accurately (valid JSON 200 vs fake HTML/invalid JSON 200 vs 404)', function () {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
                'status' => 'success',
                'data' => [
                    ['nis' => '1001', 'nama' => 'Siswa 1'],
                ],
                'meta' => ['total' => 25],
            ], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'The route api/v1/sijuna/teachers could not be found.'], 404),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response('<!DOCTYPE html><html><body>SPA Fallback</body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);

        $studentStatus = $repo->checkEndpointStatus('/api/v1/sijuna/students');
        $teacherStatus = $repo->checkEndpointStatus('/api/v1/sijuna/teachers');
        $dudiHtmlStatus = $repo->checkEndpointStatus('/api/v1/sijuna/dudi');

        expect($studentStatus['success'])->toBeTrue()
            ->and($studentStatus['status'])->toBe(200)
            ->and($studentStatus['http_status'])->toBe(200)
            ->and($studentStatus['count'])->toBe(25)
            ->and($teacherStatus['success'])->toBeFalse()
            ->and($teacherStatus['status'])->toBe(404)
            ->and($teacherStatus['http_status'])->toBe(404)
            ->and($teacherStatus['count'])->toBeNull()
            ->and($teacherStatus['message'])->toContain('HTTP 404')
            ->and($dudiHtmlStatus['success'])->toBeFalse()
            ->and($dudiHtmlStatus['status'])->toBe(200)
            ->and($dudiHtmlStatus['http_status'])->toBe(200)
            ->and($dudiHtmlStatus['count'])->toBeNull();
    });

    it('13. preview and runSync continue student synchronization when teachers endpoint returns HTTP 404', function () {
        Role::findOrCreate(UserRole::SISWA->value);
        Role::findOrCreate(UserRole::GURU->value);
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);

        $kelas = Kelas::factory()->create();
        SipintuClassroomMapping::query()->create([
            'classroom_id' => 909,
            'kelas_id' => $kelas->id,
        ]);

        $repo = Mockery::mock(SiPintuRepositoryInterface::class);
        $repo->shouldReceive('fetchStudents')->andReturn([[
            'nis' => 'NIS-404-TEST',
            'nama' => 'Siswa Lolos Sync Saat Guru 404',
            'classroom_id' => 909,
            'jk' => 'L',
        ]]);
        $repo->shouldReceive('fetchTeachers')->andThrow(
            SiPintuApiException::apiError('Endpoint API SiPintu (teachers) tidak ditemukan (HTTP 404).')
        );

        $service = new SiPintuService(
            $repo,
            app(\App\Repositories\Interfaces\SiswaRepositoryInterface::class),
            app(\App\Repositories\Interfaces\GuruRepositoryInterface::class),
            app(\App\Repositories\Interfaces\UserRepositoryInterface::class),
            app(\App\Services\Interfaces\SipintuClassroomMappingServiceInterface::class),
        );

        $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

        // Preview should succeed for students despite teachers 404
        $preview = $syncService->preview();
        expect($preview['success'])->toBeTrue()
            ->and($preview['students']['baru'])->toBe(1)
            ->and($preview['teachers']['endpoint_unavailable'])->toBe(1);

        // RunSync should succeed for students without aborting
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        $admin = User::factory()->create(['name' => 'Admin Test 404']);
        $result = $syncService->runSync($admin);

        expect($result['success'])->toBeTrue()
            ->and($result['stats']['students']['created'])->toBe(1)
            ->and(Siswa::query()->where('nis', 'NIS-404-TEST')->count())->toBe(1);
    });

    it('14. Test Connection probes Siswa, Guru, and DUDI independently and diagnoses partial availability', function () {
        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok', 'client' => 'SiMongan'], 200),
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => []], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'The route api/v1/sijuna/teachers could not be found.'], 404),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'The route api/v1/sijuna/dudi could not be found.'], 404),
        ]);

        $syncService = app(SipintuSyncService::class);
        $result = $syncService->testConnection();

        expect($result['success'])->toBeTrue()
            ->and($result['endpoint_warning'])->toBeTrue()
            ->and($result['endpoints']['gateway']['status'])->toBeTrue()
            ->and($result['endpoints']['students']['status'])->toBeTrue()
            ->and($result['endpoints']['teachers']['status'])->toBeFalse()
            ->and($result['endpoints']['dudi']['status'])->toBeFalse()
            ->and($result['detail'])->toContain('Guru (HTTP 404)')
            ->and($result['detail'])->toContain('DUDI (HTTP 404)');
    });

    it('15. Dashboard index view displays accurate local counts and separate status for Siswa, Guru, and DUDI', function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => []], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'Not Found'], 404),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.sipintu-sync.index'));

        $response->assertOk()
            ->assertViewHas('localDudiCount')
            ->assertSee('1. Data Siswa')
            ->assertSee('2. Data Guru')
            ->assertSee('3. Data Master DUDI')
            ->assertSee('/api/v1/sijuna/students')
            ->assertSee('/api/v1/sijuna/teachers')
            ->assertSee('/api/v1/sijuna/dudi')
            ->assertSee('Total DUDI SIPKL')
            ->assertSee('Preview Siswa')
            ->assertSee('Sinkronkan Siswa')
            ->assertSee('Preview Guru')
            ->assertSee('Sinkronkan Guru')
            ->assertSee('Preview DUDI')
            ->assertSee('Sinkronkan DUDI')
            ->assertSee('Lihat Riwayat');
    });

    it('16. sync executes entity=guru and entity=dudi dynamically and handles 404 or 200 responses accurately', function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        Role::findOrCreate(UserRole::GURU->value);
        Role::findOrCreate(UserRole::DUDI->value);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        Http::fake([
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'Not Found'], 404),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Not Found'], 404),
        ]);

        // Submitting guru sync executes dynamic check -> 404 error reported and logged as endpoint_unavailable
        $guruResponse = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'guru']);
        $guruResponse->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('error', 'Endpoint API Guru SiPintu (/api/v1/sijuna/teachers) belum tersedia (HTTP 404). Data guru lokal tidak diubah.');

        $guruLog = SiPintuSyncLog::query()->latest('id')->first();
        expect($guruLog)->not->toBeNull()
            ->and($guruLog->entity_scope)->toBe('guru')
            ->and($guruLog->error_details[0]['type'])->toBe('endpoint_unavailable');

        // Submitting dudi sync executes dynamic check -> 404 error reported and logged as endpoint_unavailable
        $dudiResponse = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'dudi']);
        $dudiResponse->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('error', 'Endpoint API Master DUDI SiPintu (/api/v1/sijuna/dudi) belum tersedia (HTTP 404). Data DUDI lokal tidak diubah.');

        $dudiLog = SiPintuSyncLog::query()->latest('id')->first();
        expect($dudiLog)->not->toBeNull()
            ->and($dudiLog->entity_scope)->toBe('dudi')
            ->and($dudiLog->error_details[0]['type'])->toBe('endpoint_unavailable');
    });

    it('17. POST test-connection handles all HTTP 200 endpoints cleanly via parallel pool with per_page=1 and updates cache', function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        Illuminate\Support\Facades\Cache::forget('sipintu_live_diagnostics');
        Illuminate\Support\Facades\Cache::put('sipintu_live_diagnostics', ['stale' => true], now()->addMinutes(5));

        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
                'data' => [['nis' => '1001', 'nama' => 'Siswa 1']],
                'meta' => ['total' => 1153],
            ], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
                'data' => [['nip' => '198001', 'nama' => 'Guru 1']],
                'meta' => ['total' => 72],
            ], 200),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
                'data' => [['id' => 1, 'nama_perusahaan' => 'PT Mitra']],
                'meta' => ['total' => 45],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sipintu-sync.test-connection'));
        $response->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('success');

        Http::assertSentCount(4);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/v1/sijuna/students') && str_contains($req->url(), 'per_page=1'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/v1/sijuna/teachers') && str_contains($req->url(), 'per_page=1'));
        Http::assertSent(fn ($req) => str_contains($req->url(), '/api/v1/sijuna/dudi') && str_contains($req->url(), 'per_page=1'));

        $cached = Illuminate\Support\Facades\Cache::get('sipintu_live_diagnostics');
        expect($cached)->toBeArray()
            ->and($cached)->not->toHaveKey('stale')
            ->and($cached['success'])->toBeTrue()
            ->and($cached['endpoints']['students']['status'])->toBeTrue()
            ->and($cached['endpoints']['students']['count'])->toBe(1153)
            ->and($cached['endpoints']['teachers']['status'])->toBeTrue()
            ->and($cached['endpoints']['teachers']['count'])->toBe(72)
            ->and($cached['endpoints']['dudi']['status'])->toBeTrue()
            ->and($cached['endpoints']['dudi']['count'])->toBe(45);
    });

    it('18. POST test-connection handles DUDI 404 accurately while keeping other 200 endpoints active', function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => [], 'meta' => ['total' => 1153]], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['data' => [], 'meta' => ['total' => 50]], 200),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Route not found'], 404),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sipintu-sync.test-connection'));
        $response->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('success');

        $cached = Illuminate\Support\Facades\Cache::get('sipintu_live_diagnostics');
        expect($cached['endpoint_warning'])->toBeTrue()
            ->and($cached['endpoints']['students']['status'])->toBeTrue()
            ->and($cached['endpoints']['teachers']['status'])->toBeTrue()
            ->and($cached['endpoints']['dudi']['status'])->toBeFalse()
            ->and($cached['endpoints']['dudi']['http_status'])->toBe(404)
            ->and($cached['endpoints']['dudi']['error_type'])->toBe('not_found')
            ->and($cached['message'])->toContain('DUDI (HTTP 404)');
    });

    it('19. POST test-connection isolates single endpoint timeout without retries or 500 error and never reports timeout as 404', function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $studentAttempts = 0;

        Http::fake([
            'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
            'https://sipintu.test/api/v1/sijuna/students*' => function (Illuminate\Http\Client\Request $request) use (&$studentAttempts) {
                $studentAttempts++;

                return Http::failedConnection('cURL error 28: Operation timed out after 12000 milliseconds with 0 bytes received')($request);
            },
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['data' => [], 'meta' => ['total' => 40]], 200),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['data' => [], 'meta' => ['total' => 25]], 200),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sipintu-sync.test-connection'));
        $response->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('success');

        // Zero retries on timeout: exactly 1 attempt on timed-out /students + 3 fulfilled probes recorded (health, teachers, dudi)
        expect($studentAttempts)->toBe(1);
        Http::assertSentCount(3);

        $cached = Illuminate\Support\Facades\Cache::get('sipintu_live_diagnostics');
        expect($cached['endpoint_warning'])->toBeTrue()
            ->and($cached['endpoints']['students']['status'])->toBeFalse()
            ->and($cached['endpoints']['students']['http_status'])->toBeNull()
            ->and($cached['endpoints']['students']['error_type'])->toBe('timeout')
            ->and($cached['endpoints']['students']['message'])->toContain('Timeout')
            ->and($cached['endpoints']['students']['message'])->not->toContain('404')
            ->and($cached['endpoints']['teachers']['status'])->toBeTrue()
            ->and($cached['endpoints']['teachers']['count'])->toBe(40)
            ->and($cached['endpoints']['dudi']['status'])->toBeTrue()
            ->and($cached['endpoints']['dudi']['count'])->toBe(25)
            ->and($cached['message'])->toContain('Siswa (Timeout)')
            ->and($cached['message'])->not->toContain('404')
            ->and($cached['troubleshooting'])->not->toContain('404');

        $this->actingAs($admin)->get(route('admin.sipintu-sync.index'))
            ->assertOk()
            ->assertSee('Timeout')
            ->assertDontSee('HTTP 404');
    });

    it('20. checkEndpointsStatus distinguishes 200, 404, 401/403, 5xx, and timeout accurately', function () {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => [], 'meta' => ['total' => 10]], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'Unauthorized'], 401),
            'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response('Server Error', 503),
            'https://sipintu.test/api/v1/forbidden*' => Http::response(['message' => 'Forbidden'], 403),
            'https://sipintu.test/api/v1/missing*' => Http::response(['message' => 'Not found'], 404),
            'https://sipintu.test/api/v1/slow*' => Http::failedConnection('cURL error 28: Operation timed out after 5000 milliseconds'),
        ]);

        /** @var SiPintuRepositoryInterface $repo */
        $repo = app(SiPintuRepositoryInterface::class);
        $results = $repo->checkEndpointsStatus([
            '/api/v1/sijuna/students',
            '/api/v1/sijuna/teachers',
            '/api/v1/sijuna/dudi',
            '/api/v1/forbidden',
            '/api/v1/missing',
            '/api/v1/slow',
        ]);

        expect($results['/api/v1/sijuna/students']['error_type'])->toBeNull()
            ->and($results['/api/v1/sijuna/students']['http_status'])->toBe(200)
            ->and($results['/api/v1/sijuna/teachers']['error_type'])->toBe('authentication')
            ->and($results['/api/v1/sijuna/teachers']['http_status'])->toBe(401)
            ->and($results['/api/v1/sijuna/dudi']['error_type'])->toBe('server')
            ->and($results['/api/v1/sijuna/dudi']['http_status'])->toBe(503)
            ->and($results['/api/v1/forbidden']['error_type'])->toBe('permission')
            ->and($results['/api/v1/forbidden']['http_status'])->toBe(403)
            ->and($results['/api/v1/missing']['error_type'])->toBe('not_found')
            ->and($results['/api/v1/missing']['http_status'])->toBe(404)
            ->and($results['/api/v1/slow']['error_type'])->toBe('timeout')
            ->and($results['/api/v1/slow']['http_status'])->toBeNull();
    });
});
