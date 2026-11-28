<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\SipintuClassroomMapping;
use App\Models\SiPintuSyncLog;
use App\Models\User;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::GURU->value);
    Role::findOrCreate(UserRole::DUDI->value);

    Cache::forget('sipintu_dashboard_remote_counts');
    Cache::forget('sipintu_live_diagnostics');

    config([
        'services.sipintu.api_url' => 'https://sipintu.test',
        'services.sipintu.client_id' => 'test_client_id',
        'services.sipintu.client_secret' => 'test_client_secret',
        'services.sipintu.api_token' => '',
        'services.sipintu.timeout' => 5,
    ]);
});

it('scenario 1: does not swallow 404 in fetchSyncPayload, logs endpoint_unavailable, caches null (not 0), and never displays fake HTTP 200 on UI', function (): void {
    $admin = User::factory()->create(['name' => 'Super Admin 404 Audit']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $kelas = Kelas::factory()->create(['nama' => 'XII RPL 1']);
    SipintuClassroomMapping::query()->create(['classroom_id' => 11, 'kelas_id' => $kelas->id]);

    Http::fake([
        'https://sipintu.test/api/v1/health' => Http::response(['status' => 'online'], 200),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [
                ['nis' => 'S-REAL-01', 'nama' => 'Siswa Real', 'classroom_id' => 11],
            ],
            'meta' => ['total' => 1, 'current_page' => 1, 'last_page' => 1],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['message' => 'Route not found'], 404),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Route not found'], 404),
    ]);

    /** @var SiPintuServiceInterface $siPintuService */
    $siPintuService = app(SiPintuServiceInterface::class);
    $payload = $siPintuService->fetchSyncPayload('all');

    // Must preserve 404 marker instead of swallowing as []
    expect($payload['students'])->toBeArray()->toHaveCount(1)
        ->and($payload['teachers'])->toBeNull()
        ->and($payload['dudis'])->toBeNull()
        ->and($payload['endpoint_unavailable']['students'])->toBeFalse()
        ->and($payload['endpoint_unavailable']['teachers'])->toBeTrue()
        ->and($payload['endpoint_unavailable']['dudis'])->toBeTrue();

    /** @var SipintuSyncServiceInterface $syncService */
    $syncService = app(SipintuSyncServiceInterface::class);
    $result = $syncService->runSync($admin, 'all');

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('partial')
        ->and($result['stats']['teachers']['endpoint_unavailable'])->toBe(1)
        ->and($result['stats']['dudis']['endpoint_unavailable'])->toBe(1);

    // sipintu_sync_logs must store endpoint_unavailable issues accurately
    /** @var SiPintuSyncLog|null $log */
    $log = SiPintuSyncLog::query()->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->error_details)->toBeArray();

    $issueEntities = collect($log->error_details)
        ->where('type', 'endpoint_unavailable')
        ->pluck('entity')
        ->all();
    expect($issueEntities)->toContain('teachers', 'dudis');

    // Cache must store null for unavailable teachers and dudis, NEVER 0
    $cachedCounts = Cache::get('sipintu_dashboard_remote_counts');
    expect($cachedCounts)->toBeArray()
        ->and($cachedCounts['students'])->toBe(1)
        ->and($cachedCounts['teachers'])->toBeNull()
        ->and($cachedCounts['dudis'])->toBeNull();

    // Auto live diagnostics runs on cold cache GET index and shows genuine 404, NOT fake HTTP 200
    $response = $this->actingAs($admin)->get(route('admin.sipintu-sync.index'));
    $response->assertOk()
        ->assertSee('Tersedia (HTTP 200)')
        ->assertSee('Belum Tersedia (HTTP 404)')
        ->assertDontSee('0 (Endpoint Belum Ada)')
        ->assertDontSee('0 (Menunggu API)');

    // Second GET index uses warm 5-min cache and issues ZERO additional HTTP calls
    $sentBeforeSecondGet = count(Http::recorded());
    expect($sentBeforeSecondGet)->toBe(10); // 3 from fetchSyncPayload + 3 from runSync + 4 from cold GET auto-diagnostics
    $this->actingAs($admin)->get(route('admin.sipintu-sync.index'))->assertOk();
    expect(count(Http::recorded()))->toBe($sentBeforeSecondGet);

    // Manual Test Connection forces cache refresh
    $this->actingAs($admin)->post(route('admin.sipintu-sync.test-connection'))
        ->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('success');
    expect(count(Http::recorded()))->toBeGreaterThan($sentBeforeSecondGet);
});

it('scenario 2a: handles HTTP 200 with empty data [] cleanly and unlocks guru & dudi buttons dynamically', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $dudiUser = User::factory()->create(['email' => 'keep@dudi.test']);
    $dudiUser->assignRole(UserRole::DUDI->value);
    $localDudi = Dudi::factory()->create(['user_id' => $dudiUser->id, 'sipintu_id' => 'KEEP-01']);

    Http::fake([
        'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => []], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['data' => []], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['data' => []], 200),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);
    $dudiCheck = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_DUDI);
    expect($dudiCheck['success'])->toBeTrue()
        ->and($dudiCheck['status'])->toBe(200)
        ->and($dudiCheck['http_status'])->toBe(200)
        ->and($dudiCheck['count'])->toBe(0);

    /** @var SipintuSyncServiceInterface $syncService */
    $syncService = app(SipintuSyncServiceInterface::class);
    $res = $syncService->runSync($admin, 'all');

    expect($res['success'])->toBeTrue()
        ->and($res['status'])->toBe('success')
        ->and($res['stats']['students']['received'])->toBe(0)
        ->and($res['stats']['teachers']['received'])->toBe(0)
        ->and($res['stats']['dudis']['received'])->toBe(0)
        ->and(Dudi::query()->whereKey($localDudi->id)->exists())->toBeTrue();

    $cachedCounts = Cache::get('sipintu_dashboard_remote_counts');
    expect($cachedCounts['students'])->toBe(0)
        ->and($cachedCounts['teachers'])->toBe(0)
        ->and($cachedCounts['dudis'])->toBe(0);

    // Unlocks Preview Guru/DUDI & Sinkronkan Guru/DUDI dynamically in UI when endpoints return HTTP 200
    $index = $this->actingAs($admin)->get(route('admin.sipintu-sync.index'));
    $index->assertOk()
        ->assertSee('href="'.route('admin.sipintu-sync.preview', ['entity' => 'guru']).'"', false)
        ->assertSee('startSync(\'guru\')', false)
        ->assertSee('Sinkronkan DUDI');
});

it('scenario 2b: rejects fake HTTP 200 responses with HTML or invalid payload structure in checkEndpointStatus', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response(['status' => 'success'], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response('<html><body>200 HTML Error</body></html>', 200, ['Content-Type' => 'text/html']),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);
    $badTeacherCheck = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $badDudiCheck = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_DUDI);

    expect($badTeacherCheck['success'])->toBeFalse()
        ->and($badTeacherCheck['http_status'])->toBe(200)
        ->and($badTeacherCheck['count'])->toBeNull()
        ->and($badDudiCheck['success'])->toBeFalse()
        ->and($badDudiCheck['http_status'])->toBe(200)
        ->and($badDudiCheck['count'])->toBeNull();
});

it('scenario 3a: parses DUDI single object inside data envelope without flattening via array_values()', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'data' => [
                'id' => 'DUDI-SINGLE-01',
                'nama_perusahaan' => 'PT Tunggal Jaya',
                'email_perusahaan' => 'hrd@tunggal.id',
                'alamat' => 'Jl. Jepara No. 1',
            ],
        ], 200),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);
    $singleFromData = $repo->fetchDudi();
    $singleStatus = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_DUDI);

    expect($singleFromData)->toHaveCount(1)
        ->and($singleFromData[0]['id'])->toBe('DUDI-SINGLE-01')
        ->and($singleFromData[0]['nama_perusahaan'])->toBe('PT Tunggal Jaya')
        ->and($singleStatus['success'])->toBeTrue()
        ->and($singleStatus['http_status'])->toBe(200)
        ->and($singleStatus['count'])->toBe(1);
});

it('scenario 3b: parses DUDI nested data.data list and paginated wrapper accurately', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/dudi*page=2*' => Http::response([
            'data' => [
                'current_page' => 2,
                'last_page' => 2,
                'total' => 3,
                'data' => [
                    ['id' => 'DUDI-ND-03', 'nama_perusahaan' => 'PT Nested Data 3'],
                ],
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'data' => [
                'current_page' => 1,
                'last_page' => 2,
                'total' => 3,
                'data' => [
                    ['id' => 'DUDI-ND-01', 'nama_perusahaan' => 'PT Nested Data 1'],
                    ['id' => 'DUDI-ND-02', 'nama_perusahaan' => 'PT Nested Data 2'],
                ],
            ],
        ], 200),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);
    $nestedDataList = $repo->fetchDudi();
    $nestedStatus = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_DUDI);

    expect($nestedDataList)->toHaveCount(3)
        ->and($nestedDataList[0]['nama_perusahaan'])->toBe('PT Nested Data 1')
        ->and($nestedDataList[2]['nama_perusahaan'])->toBe('PT Nested Data 3')
        ->and($nestedStatus['success'])->toBeTrue()
        ->and($nestedStatus['count'])->toBe(3);
});

it('scenario 3c: parses DUDI data.items and bare single object envelopes cleanly', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/dudi*search=items*' => Http::response([
            'data' => [
                'items' => [
                    ['id' => 'DUDI-IT-01', 'nama_perusahaan' => 'CV Items Raya'],
                ],
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'external_id' => 'EXT-BARE-01',
            'nama_perusahaan' => 'PT Bare Object',
        ], 200),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);
    $nestedItemsList = $repo->fetchDudi('items');
    $bareSingle = $repo->fetchDudi();

    expect($nestedItemsList)->toHaveCount(1)
        ->and($nestedItemsList[0]['id'])->toBe('DUDI-IT-01')
        ->and($bareSingle)->toHaveCount(1)
        ->and($bareSingle[0]['external_id'])->toBe('EXT-BARE-01')
        ->and($bareSingle[0]['nama_perusahaan'])->toBe('PT Bare Object');
});

it('scenario 4: strictly isolates runSync scopes so siswa does not fetch or alter guru/dudi, and guru/dudi run independently via controller', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $kelas = Kelas::factory()->create(['nama' => 'XI TKJ 2']);
    SipintuClassroomMapping::query()->create(['classroom_id' => 22, 'kelas_id' => $kelas->id]);

    Cache::put('sipintu_dashboard_remote_counts', [
        'students' => null,
        'teachers' => 19,
        'dudis' => 7,
    ], now()->addHour());

    $existingGuruUser = User::factory()->create(['email' => 'guru.keep@smkn1bangsri.sch.id']);
    $existingGuruUser->assignRole(UserRole::GURU->value);
    $existingGuru = Guru::factory()->create([
        'user_id' => $existingGuruUser->id,
        'nip' => '19900101001',
        'nama' => 'Guru Asli Lokal',
    ]);

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [['nis' => 'ISO-S-01', 'nama' => 'Siswa Terisolasi', 'classroom_id' => 22]],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
            'data' => [['nip' => '19900101001', 'nama' => 'Guru Berubah Seharusnya Terisolasi', 'email' => 'guru.keep@smkn1bangsri.sch.id']],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'data' => [['id' => 'DUDI-ISO-1', 'nama_perusahaan' => 'PT Terisolasi']],
        ], 200),
    ]);

    /** @var SipintuSyncServiceInterface $syncService */
    $syncService = app(SipintuSyncServiceInterface::class);
    $siswaOnly = $syncService->runSync($admin, 'siswa');

    expect($siswaOnly['success'])->toBeTrue()
        ->and($siswaOnly['stats']['students']['created'])->toBe(1)
        ->and($siswaOnly['stats']['teachers']['received'])->toBe(0)
        ->and($siswaOnly['stats']['dudis']['received'])->toBe(0)
        ->and($existingGuru->fresh()->nama)->toBe('Guru Asli Lokal');

    // Teachers & dudis endpoints MUST NOT have been called during runSync('siswa')
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/v1/sijuna/students'));
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/api/v1/sijuna/teachers'));
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/api/v1/sijuna/dudi'));

    // Cache counts for teachers & dudis remain preserved (not zeroed!)
    $countsAfterSiswa = Cache::get('sipintu_dashboard_remote_counts');
    expect($countsAfterSiswa['students'])->toBe(1)
        ->and($countsAfterSiswa['teachers'])->toBe(19)
        ->and($countsAfterSiswa['dudis'])->toBe(7);

    // Controller allows preview?entity=guru & post sync entity=guru dynamically when HTTP 200
    $this->actingAs($admin)->get(route('admin.sipintu-sync.preview', ['entity' => 'guru']))
        ->assertOk()
        ->assertSee('Cakupan: GURU');

    $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'guru'])
        ->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('success');

    expect($existingGuru->fresh()->nama)->toBe('Guru Berubah Seharusnya Terisolasi');

    // Controller allows preview?entity=dudi dynamically
    $this->actingAs($admin)->get(route('admin.sipintu-sync.preview', ['entity' => 'dudi']))
        ->assertOk()
        ->assertSee('Cakupan: DUDI')
        ->assertDontSee('Master Data DUDI Belum Dibuka di SiPintu');
});

it('scenario 5: DUDI sync follows link-only policy and never overwrites local passwords, coordinates, working hours, or PKL placements', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $dudiUser = User::factory()->create([
        'name' => 'PT Mitra Aman',
        'email' => 'aman@mitra.test',
        'password' => Hash::make('MySecretLocalPass!2026'),
    ]);
    $dudiUser->assignRole(UserRole::DUDI->value);

    $dudi = Dudi::factory()->create([
        'user_id' => $dudiUser->id,
        'sipintu_id' => null,
        'sipintu_user_id' => null,
        'nama_perusahaan' => 'PT Mitra Aman',
        'email_perusahaan' => 'aman@mitra.test',
        'latitude' => -6.5881234,
        'longitude' => 110.6689876,
        'jam_masuk' => '07:15:00',
        'jam_pulang' => '15:45:00',
        'toleransi_keterlambatan' => 20,
        'hari_operasional' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
        'sso_enabled' => true,
        'status_aktif' => true,
    ]);

    $periode = PeriodePKL::factory()->create(['status' => 'aktif']);
    $penempatan = PenempatanPKL::factory()->create([
        'periode_pkl_id' => $periode->id,
        'dudi_id' => $dudi->id,
        'status' => 'aktif',
    ]);

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'data' => [
                [
                    'id' => 'DUDI-LINK-100',
                    'user_id' => 'sipintu_u_100',
                    'nama_perusahaan' => 'PT Mitra Aman Tbk',
                    'email_perusahaan' => 'aman@mitra.test',
                    'alamat' => 'Jl. Baru Terverifikasi',
                    'password' => 'HackedPasswordFromRemote!',
                    'latitude' => 99.0,
                    'longitude' => 99.0,
                    'jam_masuk' => '11:00:00',
                    'jam_pulang' => '12:00:00',
                ],
                [
                    'id' => 'DUDI-UNKNOWN-999',
                    'nama_perusahaan' => 'PT Luar Belum Didaftarkan Admin',
                    'email_perusahaan' => 'luar@unknown.test',
                ],
            ],
        ], 200),
    ]);

    // Default sync (link-only via controller without create_missing_dudi flag)
    $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'dudi'])
        ->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('success');

    $dudi->refresh();
    $dudiUser->refresh();
    $penempatan->refresh();

    // Admin DUDI is linked & updated, unregistered DUDI was skipped (link-only), operational fields preserved
    expect(Dudi::query()->where('sipintu_id', 'DUDI-UNKNOWN-999')->exists())->toBeFalse()
        ->and($dudi->sipintu_id)->toBe('DUDI-LINK-100')
        ->and($dudi->sipintu_user_id)->toBe('sipintu_u_100')
        ->and($dudi->nama_perusahaan)->toBe('PT Mitra Aman Tbk')
        ->and($dudi->alamat)->toBe('Jl. Baru Terverifikasi')
        ->and(Hash::check('MySecretLocalPass!2026', $dudiUser->password))->toBeTrue()
        ->and(round((float) $dudi->latitude, 5))->toBe(-6.58812)
        ->and(round((float) $dudi->longitude, 5))->toBe(110.66899)
        ->and((string) $dudi->jam_masuk)->toContain('07:15')
        ->and((string) $dudi->jam_pulang)->toContain('15:45')
        ->and($dudi->toleransi_keterlambatan)->toBe(20)
        ->and($dudi->sso_enabled)->toBeTrue()
        ->and($penempatan->status)->toBe('aktif');

    // Explicit opt-in create_missing_dudi=1 creates the missing DUDI when admin confirms
    $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), [
        'entity' => 'dudi',
        'create_missing_dudi' => '1',
    ])->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('success');

    expect(Dudi::query()->where('sipintu_id', 'DUDI-UNKNOWN-999')->exists())->toBeTrue();
});
