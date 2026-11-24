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

    it('12. checkEndpointStatus checks individual endpoint status accurately (200 vs 404)', function () {
        Http::fake([
            'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['status' => 'success'], 200),
            'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'The route api/v1/sijuna/teachers could not be found.'], 404),
        ]);

        $repo = app(SiPintuRepositoryInterface::class);

        $studentStatus = $repo->checkEndpointStatus('/api/v1/sijuna/students');
        $teacherStatus = $repo->checkEndpointStatus('/api/v1/sijuna/teachers');

        expect($studentStatus['success'])->toBeTrue()
            ->and($studentStatus['status'])->toBe(200)
            ->and($teacherStatus['success'])->toBeFalse()
            ->and($teacherStatus['status'])->toBe(404)
            ->and($teacherStatus['message'])->toContain('HTTP 404');
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

    it('16. sync rejects entity=guru and entity=dudi with graceful message while allowing safe student sync', function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        // Submitting guru sync should gracefully return error notice
        $guruResponse = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'guru']);
        $guruResponse->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('error', 'Endpoint API Guru SiPintu (/api/v1/sijuna/teachers) belum tersedia (HTTP 404). Data guru lokal tidak diubah.');

        // Submitting dudi sync should gracefully return error notice
        $dudiResponse = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'dudi']);
        $dudiResponse->assertRedirect(route('admin.sipintu-sync.index'))
            ->assertSessionHas('error', 'Endpoint API Master DUDI SiPintu (/api/v1/sijuna/dudi) belum tersedia (HTTP 404). Data DUDI lokal tidak diubah.');
    });
});
