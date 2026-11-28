<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Absensi;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use App\Services\SiPintuService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    Role::findOrCreate(UserRole::GURU->value);
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::DUDI->value);

    Cache::forget('sipintu_dashboard_remote_counts');
    Cache::forget('sipintu_live_diagnostics');

    config([
        'services.sipintu.api_url' => 'https://sipintu.test',
        'services.sipintu.client_id' => 'fase2_client_id',
        'services.sipintu.client_secret' => 'fase2_client_secret',
        'services.sipintu.api_token' => '',
        'services.sipintu.timeout' => 5,
        'app.student_email_domain' => 'smkn1bangsri.sch.id',
    ]);
});

it('probe: checkEndpointStatus distinguishes 404, 401, 403, 5xx, HTML 200, empty JSON 200, and populated JSON 200', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::sequence()
            ->push(['message' => 'Not found'], 404)
            ->push(['message' => 'Unauthorized'], 401)
            ->push(['message' => 'Forbidden'], 403)
            ->push('Server Error', 500)
            ->push('<!DOCTYPE html><html>Login</html>', 200, ['Content-Type' => 'text/html'])
            ->push(['data' => []], 200)
            ->push([
                'data' => [['nip' => '1980', 'nama' => 'Pak Budi']],
                'meta' => ['total' => 45],
            ], 200),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);

    $r404 = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $r401 = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $r403 = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $r500 = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $rHtml = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $rEmpty = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);
    $rFull = $repo->checkEndpointStatus(SiPintuRepositoryInterface::ENDPOINT_TEACHERS);

    expect($r404['success'])->toBeFalse()->and($r404['http_status'])->toBe(404)->and($r404['count'])->toBeNull()
        ->and($r401['success'])->toBeFalse()->and($r401['http_status'])->toBe(401)->and($r401['count'])->toBeNull()
        ->and($r403['success'])->toBeFalse()->and($r403['http_status'])->toBe(403)->and($r403['count'])->toBeNull()
        ->and($r500['success'])->toBeFalse()->and($r500['http_status'])->toBe(500)->and($r500['count'])->toBeNull()
        ->and($rHtml['success'])->toBeFalse()->and($rHtml['http_status'])->toBe(200)->and($rHtml['count'])->toBeNull()
        ->and($rEmpty['success'])->toBeTrue()->and($rEmpty['http_status'])->toBe(200)->and($rEmpty['count'])->toBe(0)
        ->and($rFull['success'])->toBeTrue()->and($rFull['http_status'])->toBe(200)->and($rFull['count'])->toBe(45);
});

it('parser: fetchTeachers decodes data list, single object, data.data, data.items, items, bare list, and bare object envelopes', function (): void {
    Http::fake([
        'https://sipintu.test/api/v1/sijuna/teachers*search=list*' => Http::response([
            'data' => [['nip' => 'T1', 'nama' => 'List Guru']],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*search=single*' => Http::response([
            'data' => ['nip' => 'T2', 'nama' => 'Single Wrapper Guru'],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*search=nested_data*' => Http::response([
            'data' => ['data' => [['nip' => 'T3', 'nama' => 'Nested Data Guru']]],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*search=nested_items*' => Http::response([
            'data' => ['items' => [['nip' => 'T4', 'nama' => 'Nested Items Guru']]],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*search=root_items*' => Http::response([
            'items' => [['nip' => 'T5', 'nama' => 'Root Items Guru']],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*search=bare_list*' => Http::response([
            ['nip' => 'T6', 'nama' => 'Bare List Guru'],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*search=bare_obj*' => Http::response([
            'nip' => 'T7',
            'nama' => 'Bare Object Guru',
        ], 200),
    ]);

    /** @var SiPintuRepositoryInterface $repo */
    $repo = app(SiPintuRepositoryInterface::class);

    expect($repo->fetchTeachers(search: 'list')[0]['nip'])->toBe('T1')
        ->and($repo->fetchTeachers(search: 'single')[0]['nip'])->toBe('T2')
        ->and($repo->fetchTeachers(search: 'nested_data')[0]['nip'])->toBe('T3')
        ->and($repo->fetchTeachers(search: 'nested_items')[0]['nip'])->toBe('T4')
        ->and($repo->fetchTeachers(search: 'root_items')[0]['nip'])->toBe('T5')
        ->and($repo->fetchTeachers(search: 'bare_list')[0]['nip'])->toBe('T6')
        ->and($repo->fetchTeachers(search: 'bare_obj')[0]['nip'])->toBe('T7');
});

it('classifies Preview Guru accurately (baru, diperbarui, tidak_berubah, dilewati, duplikat, konflik, tidak_valid, tidak_ditemukan) read-only', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    // 1. Existing unchanged guru
    $uUnchanged = User::factory()->create(['name' => 'Guru Tetap', 'email' => 'tetap@smkn1bangsri.sch.id']);
    $uUnchanged->assignRole(UserRole::GURU->value);
    Guru::factory()->create([
        'user_id' => $uUnchanged->id,
        'nip' => '19800101',
        'nama' => 'Guru Tetap',
        'jenis_kelamin' => 'L',
        'no_hp' => '08111',
        'alamat' => 'Bangsri',
    ]);

    // 2. Existing guru to update
    $uUpdate = User::factory()->create(['name' => 'Guru Lama', 'email' => 'ubah@smkn1bangsri.sch.id']);
    $uUpdate->assignRole(UserRole::GURU->value);
    Guru::factory()->create([
        'user_id' => $uUpdate->id,
        'nip' => '19800102',
        'nama' => 'Guru Lama',
        'no_hp' => '08222',
    ]);

    // 3. Soft-deleted guru (MUST be dilewati, NOT restored, NOT duplicated)
    $uTrashed = User::factory()->create(['name' => 'Guru Nonaktif', 'email' => 'deleted@smkn1bangsri.sch.id']);
    $uTrashed->assignRole(UserRole::GURU->value);
    $guruTrashed = Guru::factory()->create([
        'user_id' => $uTrashed->id,
        'nip' => '19800103',
        'nama' => 'Guru Nonaktif',
    ]);
    $guruTrashed->delete();

    // 4. Email used by Siswa (konflik)
    $uSiswa = User::factory()->create(['email' => 'bentrok@smkn1bangsri.sch.id']);
    $uSiswa->assignRole(UserRole::SISWA->value);

    // 5. Local active guru absent from SiPintu (tidak_ditemukan)
    $uAbsent = User::factory()->create(['email' => 'lokal.saja@smkn1bangsri.sch.id']);
    $uAbsent->assignRole(UserRole::GURU->value);
    Guru::factory()->create([
        'user_id' => $uAbsent->id,
        'nip' => '19809999',
        'nama' => 'Guru Lokal Saja',
    ]);

    Http::fake([
        'https://sipintu.test/api/v1/health' => Http::response(['status' => 'ok'], 200),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response(['data' => []], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Not found'], 404),
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
            'data' => [
                ['nip' => '19800101', 'nama' => 'Guru Tetap', 'email' => 'tetap@smkn1bangsri.sch.id', 'jenis_kelamin' => 'L', 'no_hp' => '08111', 'alamat' => 'Bangsri'],
                ['nip' => '19800102', 'nama' => 'Guru Baru Diupdate', 'email' => 'ubah@smkn1bangsri.sch.id', 'no_hp' => '08999'],
                ['nip' => '19800103', 'nama' => 'Guru Nonaktif Reaktivasi?', 'email' => 'deleted@smkn1bangsri.sch.id'],
                ['nip' => '19800104', 'nama' => 'Guru Baru Fresh', 'email' => ''],
                ['nip' => '19800104', 'nama' => 'Guru Duplikat NIP'],
                ['nip' => '19800105', 'nama' => 'Guru Konflik Siswa', 'email' => 'bentrok@smkn1bangsri.sch.id'],
                ['nip' => '19800106', 'nama' => ''],
                ['nama' => 'Tanpa NIP'],
            ],
        ], 200),
    ]);

    /** @var SipintuSyncServiceInterface $syncService */
    $syncService = app(SipintuSyncServiceInterface::class);
    $preview = $syncService->preview('guru');

    expect($preview['success'])->toBeTrue()
        ->and($preview['scope'])->toBe('guru')
        ->and($preview['teachers']['total_remote'])->toBe(8)
        ->and($preview['teachers']['tidak_berubah'])->toBe(1)
        ->and($preview['teachers']['diperbarui'])->toBe(1)
        ->and($preview['teachers']['dilewati'])->toBe(1)
        ->and($preview['teachers']['baru'])->toBe(1)
        ->and($preview['teachers']['duplikat'])->toBe(1)
        ->and($preview['teachers']['konflik'])->toBe(1)
        ->and($preview['teachers']['tidak_valid'])->toBe(2)
        ->and($preview['teachers']['tidak_ditemukan'])->toBe(1)
        // Strictly read-only checks
        ->and(Guru::query()->where('nip', '19800104')->exists())->toBeFalse()
        ->and(Guru::query()->where('nip', '19800102')->first()->nama)->toBe('Guru Lama')
        ->and($guruTrashed->fresh()->trashed())->toBeTrue();
});

it('syncs teachers by NIP then email fallback, generates NIP@student_email_domain when empty, preserves soft-deleted & local passwords, and is 100% idempotent', function (): void {
    $admin = User::factory()->create(['name' => 'Admin Sync Guru']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    // Pre-existing Guru created by admin WITHOUT nip (email fallback match!) with custom password
    $customHash = Hash::make('MyTeacherCustomPass!99');
    $uEmailMatch = User::factory()->create([
        'name' => 'Pak Ahmad Lama',
        'email' => 'ahmad.guru@smkn1bangsri.sch.id',
        'password' => $customHash,
    ]);
    $uEmailMatch->assignRole(UserRole::GURU->value);
    $guruEmailMatch = Guru::factory()->create([
        'user_id' => $uEmailMatch->id,
        'nip' => null,
        'nama' => 'Pak Ahmad Lama',
    ]);

    // Soft-deleted Guru must stay soft-deleted (no duplicate, no auto-restore)
    $uSoftDel = User::factory()->create(['email' => 'softdel@smkn1bangsri.sch.id']);
    $uSoftDel->assignRole(UserRole::GURU->value);
    $guruSoftDel = Guru::factory()->create([
        'user_id' => $uSoftDel->id,
        'nip' => '19770011',
        'nama' => 'Pak Dihapus',
    ]);
    $guruSoftDel->delete();
    $uSoftDel->delete();

    // Super Admin collision email must NEVER be overwritten or lose Super Admin role
    $saUser = User::factory()->create(['email' => 'kepala.sa@smkn1bangsri.sch.id']);
    $saUser->assignRole(UserRole::SUPER_ADMIN->value);

    // Other modules that MUST NOT be touched by entity=guru
    $siswa = Siswa::factory()->create(['nis' => 'SAFE-S-01']);
    $dudi = Dudi::factory()->create(['nama_perusahaan' => 'PT Aman PKL']);
    $periode = PeriodePKL::factory()->create(['status' => 'aktif']);
    $penempatan = PenempatanPKL::factory()->create([
        'periode_pkl_id' => $periode->id,
        'siswa_id' => $siswa->id,
        'guru_id' => $guruEmailMatch->id,
        'dudi_id' => $dudi->id,
        'status' => 'aktif',
    ]);
    $absensi = Absensi::factory()->create(['penempatan_pkl_id' => $penempatan->id]);

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
            'data' => [
                // 1. New guru with empty email -> fallback 19851212@smkn1bangsri.sch.id
                [
                    'nip' => '19851212',
                    'nama' => 'Bu Siti Aminah',
                    'email' => '',
                    'jenis_kelamin' => 'P',
                    'no_hp' => '08129999000',
                    'alamat' => 'Jepara Kota',
                ],
                // 2. Match via email fallback (local nip=null) -> fills NIP & preserves local password
                [
                    'nip' => '19820303',
                    'nama' => 'Pak Ahmad Terbarukan',
                    'email' => 'ahmad.guru@smkn1bangsri.sch.id',
                    'jenis_kelamin' => 'Laki-laki',
                    'no_hp' => '08123450000',
                    'alamat' => 'Bangsri',
                ],
                // 3. Soft-deleted guru -> skipped without restoring or duplicating
                [
                    'nip' => '19770011',
                    'nama' => 'Pak Dihapus',
                    'email' => 'softdel@smkn1bangsri.sch.id',
                ],
                // 4. Super Admin email conflict -> conflict without overwriting role
                [
                    'nip' => '19990000',
                    'nama' => 'Pembajak SA',
                    'email' => 'kepala.sa@smkn1bangsri.sch.id',
                ],
            ],
        ], 200),
    ]);

    // Execute via Controller with entity=guru
    $response = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'guru']);
    $response->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('warning');

    // Only /teachers called; never /students or /dudi
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/api/v1/sijuna/teachers'));
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/api/v1/sijuna/students'));
    Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/api/v1/sijuna/dudi'));

    // Assert created teacher got fallback NIP@student_email_domain
    $newGuru = Guru::query()->where('nip', '19851212')->first();
    expect($newGuru)->not->toBeNull()
        ->and($newGuru->user)->not->toBeNull()
        ->and($newGuru->user->email)->toBe('19851212@smkn1bangsri.sch.id')
        ->and($newGuru->user->hasRole(UserRole::GURU->value))->toBeTrue();

    // Assert email fallback linked NIP & preserved custom password
    $guruEmailMatch->refresh();
    $uEmailMatch->refresh();
    expect($guruEmailMatch->nip)->toBe('19820303')
        ->and($guruEmailMatch->nama)->toBe('Pak Ahmad Terbarukan')
        ->and(Hash::check('MyTeacherCustomPass!99', $uEmailMatch->password))->toBeTrue();

    // Assert soft-deleted NOT auto-restored & zero duplicate
    expect(Guru::withTrashed()->where('nip', '19770011')->count())->toBe(1)
        ->and($guruSoftDel->fresh()->trashed())->toBeTrue()
        ->and($uSoftDel->fresh()->trashed())->toBeTrue();

    // Assert Super Admin completely untouched
    expect($saUser->fresh()->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue()
        ->and(Guru::query()->where('nip', '19990000')->exists())->toBeFalse();

    // Assert Siswa, DUDI, PenempatanPKL, Absensi untouched
    expect($siswa->fresh())->not->toBeNull()
        ->and($dudi->fresh())->not->toBeNull()
        ->and($penempatan->fresh()->status)->toBe('aktif')
        ->and($absensi->fresh())->not->toBeNull();

    // Assert Audit Log in sipintu_sync_logs
    /** @var SiPintuSyncLog|null $log1 */
    $log1 = SiPintuSyncLog::query()->latest('id')->first();
    expect($log1)->not->toBeNull()
        ->and($log1->user_id)->toBe($admin->id)
        ->and($log1->entity_scope)->toBe('guru')
        ->and($log1->teacher_added)->toBe(1)
        ->and($log1->teacher_updated)->toBe(1)
        ->and($log1->teacher_skipped)->toBe(2) // 1 soft-deleted + 1 conflict
        ->and($log1->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($log1->error_details)->toBeArray();

    // Second run with IDENTICAL payload must produce 0 created & 0 updated (100% idempotent)
    /** @var SipintuSyncServiceInterface $syncService */
    $syncService = app(SipintuSyncServiceInterface::class);
    $run2 = $syncService->runSync($admin, 'guru');

    expect($run2['stats']['teachers']['created'])->toBe(0)
        ->and($run2['stats']['teachers']['updated'])->toBe(0)
        ->and($run2['stats']['teachers']['unchanged'])->toBe(2)
        ->and(Guru::query()->where('nip', '19851212')->count())->toBe(1)
        ->and(Guru::query()->where('nip', '19820303')->count())->toBe(1);
});

it('isolates DB failure per record transaction: failing 1 teacher creates 0 orphan users while saving other valid teachers', function (): void {
    $realGuruRepo = app(GuruRepositoryInterface::class);
    $failingGuruRepo = Mockery::mock(GuruRepositoryInterface::class);
    $failingGuruRepo->shouldReceive('findByNip')->andReturn(null);
    $failingGuruRepo->shouldReceive('create')->andReturnUsing(function (array $attributes) use ($realGuruRepo) {
        if (($attributes['nip'] ?? '') === 'FAIL-G-02') {
            throw new \RuntimeException('Simulated constraint error on teacher #2');
        }

        return $realGuruRepo->create($attributes);
    });

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchTeachers')->once()->andReturn([
        ['nip' => 'OK-G-01', 'nama' => 'Guru Pertama Sukses', 'email' => 'ok1@smkn1bangsri.sch.id'],
        ['nip' => 'FAIL-G-02', 'nama' => 'Guru Kedua Gagal', 'email' => 'fail2@smkn1bangsri.sch.id'],
        ['nip' => 'OK-G-03', 'nama' => 'Guru Ketiga Sukses', 'email' => 'ok3@smkn1bangsri.sch.id'],
    ]);

    $service = new SiPintuService(
        $repo,
        app(SiswaRepositoryInterface::class),
        $failingGuruRepo,
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );

    $stats = $service->syncTeachers();

    expect($stats['created'])->toBe(2)
        ->and($stats['failed'])->toBe(1)
        ->and($stats['errors'])->toBe(1)
        ->and(Guru::query()->where('nip', 'OK-G-01')->exists())->toBeTrue()
        ->and(Guru::query()->where('nip', 'OK-G-03')->exists())->toBeTrue()
        ->and(Guru::query()->where('nip', 'FAIL-G-02')->exists())->toBeFalse()
        // Zero orphan User for failed teacher #2!
        ->and(User::query()->where('email', 'fail2@smkn1bangsri.sch.id')->exists())->toBeFalse();
});

it('handles SiPintu timeout and server failure gracefully without 500 errors', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    // Outage / ConnectionException timeout during index, preview, and sync -> NEVER HTTP 500
    Http::fake(function () {
        throw new ConnectionException('cURL error 28: Operation timed out after 5001 milliseconds');
    });

    $this->actingAs($admin)->get(route('admin.sipintu-sync.index'))
        ->assertOk()
        ->assertSee('Diagnosis Gangguan Koneksi SiPintu');

    $this->actingAs($admin)->get(route('admin.sipintu-sync.preview', ['entity' => 'guru']))
        ->assertOk()
        ->assertSee('Hasil Preview (Dry Run)');

    $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'guru'])
        ->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('error');

    expect(SiPintuSyncLog::query()->where('entity_scope', 'guru')->where('status', 'failed')->exists())->toBeTrue();
});

it('updates Guru UI card dynamically with count, buttons, and preview categories when teachers endpoint returns HTTP 200', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    Cache::forget('sipintu_live_diagnostics');
    Cache::forget('sipintu_dashboard_remote_counts');

    Http::fake([
        'https://sipintu.test/api/v1/health*' => Http::response(['status' => 'ok'], 200),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'data' => [['nis' => 'UI-S-01', 'nama' => 'Siswa UI Satu']],
            'meta' => ['total' => 10, 'current_page' => 1, 'last_page' => 1],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/teachers*' => Http::response([
            'data' => [['nip' => 'UI-G-01', 'nama' => 'Guru UI Satu']],
            'meta' => ['total' => 3, 'current_page' => 1, 'last_page' => 1],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response(['message' => 'Not found'], 404),
    ]);

    $uiIndex = $this->actingAs($admin)->get(route('admin.sipintu-sync.index'));
    $uiIndex->assertOk()
        ->assertSee('2. Data Guru')
        ->assertSee('Tersedia (HTTP 200)')
        ->assertSee('3 Guru')
        ->assertSee('href="'.route('admin.sipintu-sync.preview', ['entity' => 'guru']).'"', false)
        ->assertSee('startSync(\'guru\')', false);

    $uiPreviewGuru = $this->actingAs($admin)->get(route('admin.sipintu-sync.preview', ['entity' => 'guru']));
    $uiPreviewGuru->assertOk()
        ->assertSee('Cakupan: GURU')
        ->assertSee('Dilewati');
});
