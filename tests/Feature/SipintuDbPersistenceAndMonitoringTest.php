<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\SiPintuApiException;
use App\Models\Absensi;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\SipintuClassroomMapping;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use App\Services\SiPintuService;
use App\Services\SipintuSyncService;
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
});

function dbPersistenceSyncService(SiPintuRepositoryInterface $repository): SipintuSyncService
{
    $siPintuService = new SiPintuService(
        $repository,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );

    return new SipintuSyncService(
        $siPintuService,
        app(SipintuSyncLogRepositoryInterface::class),
    );
}

it('creates User, profile, and Spatie role atomically and leaves zero orphan User records when persistence fails', function (): void {
    $kelas = Kelas::factory()->create(['nama' => 'XI TKJ 1']);
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 301,
        'kelas_id' => $kelas->id,
    ]);

    $failingSiswaRepo = Mockery::mock(SiswaRepositoryInterface::class);
    $failingSiswaRepo->shouldReceive('findByNis')->andReturn(null);
    $failingSiswaRepo->shouldReceive('create')->once()->andThrow(new \RuntimeException('Simulated DB constraint failure on siswa insert'));

    $sipintuRepo = Mockery::mock(SiPintuRepositoryInterface::class);
    $sipintuRepo->shouldReceive('fetchStudents')->once()->andReturn([
        [
            'nis' => 'ORPHAN-001',
            'nisn' => '0099887766',
            'nama' => 'Siswa Rollback Test',
            'email' => 'rollback.siswa@smkn1bangsri.sch.id',
            'classroom_id' => 301,
        ],
    ]);

    $service = new SiPintuService(
        $sipintuRepo,
        $failingSiswaRepo,
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );

    $stats = $service->syncStudents();

    expect($stats['created'])->toBe(0)
        ->and($stats['failed'])->toBe(1)
        ->and($stats['errors'])->toBe(1)
        ->and(User::query()->where('email', 'rollback.siswa@smkn1bangsri.sch.id')->exists())->toBeFalse()
        ->and(Siswa::query()->where('nis', 'ORPHAN-001')->exists())->toBeFalse();

    // Happy path creates valid User + profile + Spatie role for Siswa, Guru, and DUDI
    $happyRepo = Mockery::mock(SiPintuRepositoryInterface::class);
    $happyRepo->shouldReceive('fetchStudents')->once()->andReturn([
        [
            'nis' => 'ATOMIC-S-01',
            'nisn' => '0011223344',
            'nama' => 'Siswa Atomik',
            'email' => 'atomik.siswa@smkn1bangsri.sch.id',
            'classroom_id' => 301,
        ],
    ]);
    $happyRepo->shouldReceive('fetchTeachers')->once()->andReturn([
        [
            'nip' => 'ATOMIC-G-01',
            'nama' => 'Guru Atomik',
            'email' => 'atomik.guru@smkn1bangsri.sch.id',
        ],
    ]);
    $happyRepo->shouldReceive('fetchDudi')->once()->andReturn([
        [
            'id' => 'ATOMIC-D-01',
            'nama_perusahaan' => 'PT Atomik Nusantara',
            'email_perusahaan' => 'atomik.dudi@company.test',
            'status_aktif' => 1,
        ],
    ]);

    $happyService = new SiPintuService(
        $happyRepo,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );

    $studentStats = $happyService->syncStudents();
    $teacherStats = $happyService->syncTeachers();
    $dudiStats = $happyService->syncDudi(allowCreate: true);

    $siswa = Siswa::query()->where('nis', 'ATOMIC-S-01')->first();
    $guru = Guru::query()->where('nip', 'ATOMIC-G-01')->first();
    $dudi = Dudi::query()->where('sipintu_id', 'ATOMIC-D-01')->first();

    expect($studentStats['created'])->toBe(1)
        ->and($teacherStats['created'])->toBe(1)
        ->and($dudiStats['created'])->toBe(1)
        ->and($siswa)->not->toBeNull()
        ->and($siswa->user)->not->toBeNull()
        ->and($siswa->user->hasRole(UserRole::SISWA->value))->toBeTrue()
        ->and($guru)->not->toBeNull()
        ->and($guru->user)->not->toBeNull()
        ->and($guru->user->hasRole(UserRole::GURU->value))->toBeTrue()
        ->and($dudi)->not->toBeNull()
        ->and($dudi->user)->not->toBeNull()
        ->and($dudi->user->hasRole(UserRole::DUDI->value))->toBeTrue();
});

it('is idempotent on repeated syncs and never overwrites local passwords or PKL operational data', function (): void {
    $admin = User::factory()->create(['name' => 'Super Admin Test']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $kelasLama = Kelas::factory()->create(['nama' => 'XI RPL 1']);
    $kelasBaru = Kelas::factory()->create(['nama' => 'XII RPL 1']);
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 402,
        'kelas_id' => $kelasBaru->id,
    ]);

    $siswaUser = User::factory()->create([
        'name' => 'Nama Siswa Lama',
        'email' => 'siswa.op@smkn1bangsri.sch.id',
        'password' => Hash::make('CustomSiswaPass!123'),
    ]);
    $siswaUser->assignRole(UserRole::SISWA->value);

    $siswa = Siswa::factory()->create([
        'user_id' => $siswaUser->id,
        'class_id' => $kelasLama->id,
        'nis' => 'S-OP-01',
        'nisn' => '1122334455',
        'nama' => 'Nama Siswa Lama',
    ]);

    $guruUser = User::factory()->create([
        'name' => 'Guru Pembimbing Lama',
        'email' => 'guru.op@smkn1bangsri.sch.id',
        'password' => Hash::make('CustomGuruPass!123'),
    ]);
    $guruUser->assignRole(UserRole::GURU->value);

    $guru = Guru::factory()->create([
        'user_id' => $guruUser->id,
        'nip' => 'G-OP-01',
        'nama' => 'Guru Pembimbing Lama',
    ]);

    $dudiUser = User::factory()->create([
        'name' => 'PT Mitra Lokal',
        'email' => 'dudi.op@mitra.test',
        'password' => Hash::make('CustomDudiPass!123'),
        'must_change_password' => false,
    ]);
    $dudiUser->assignRole(UserRole::DUDI->value);

    $dudi = Dudi::factory()->create([
        'user_id' => $dudiUser->id,
        'sipintu_id' => 'DUDI-OP-01',
        'nama_perusahaan' => 'PT Mitra Lokal',
        'email_perusahaan' => 'dudi.op@mitra.test',
        'alamat' => 'Alamat Lama',
        'latitude' => -6.5888900,
        'longitude' => 110.6684000,
        'jam_masuk' => '07:30:00',
        'jam_pulang' => '16:00:00',
        'toleransi_keterlambatan' => 15,
        'hari_operasional' => ['senin', 'selasa', 'rabu', 'kamis'],
        'sso_enabled' => true,
        'status_aktif' => true,
    ]);

    $periode = PeriodePKL::factory()->create(['status' => 'aktif']);
    $penempatan = PenempatanPKL::factory()->create([
        'periode_pkl_id' => $periode->id,
        'siswa_id' => $siswa->id,
        'guru_id' => $guru->id,
        'dudi_id' => $dudi->id,
        'status' => 'aktif',
        'nomor_surat' => 'PKL/999/XI/2026',
    ]);
    $absensi = Absensi::factory()->create([
        'penempatan_pkl_id' => $penempatan->id,
        'tanggal' => '2026-11-20',
        'status' => 'hadir',
        'jam_masuk' => '07:15:00',
        'lokasi_valid' => true,
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        [
            'nis' => 'S-OP-01',
            'nisn' => '1122334455',
            'nama' => 'Nama Siswa Akademik Baru',
            'email' => 'siswa.op@smkn1bangsri.sch.id',
            'classroom_id' => 402,
        ],
    ]);
    $repo->shouldReceive('fetchTeachers')->andReturn([
        [
            'nip' => 'G-OP-01',
            'nama' => 'Guru Pembimbing Terbaru',
            'email' => 'guru.op@smkn1bangsri.sch.id',
        ],
    ]);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-OP-01',
            'nama_perusahaan' => 'PT Mitra Nusantara Tbk',
            'email_perusahaan' => 'dudi.op@mitra.test',
            'alamat' => 'Jl. Raya Bangsri Km 5',
            'latitude' => 0.0,
            'longitude' => 0.0,
            'status_aktif' => 1,
        ],
    ]);

    $syncService = dbPersistenceSyncService($repo);

    // First sync updates identity/academic fields only
    $first = $syncService->runSync($admin);
    // Second sync with identical payload must be 100% idempotent
    $second = $syncService->runSync($admin);

    expect($first['success'])->toBeTrue()
        ->and($first['stats']['students']['updated'])->toBe(1)
        ->and($first['stats']['teachers']['updated'])->toBe(1)
        ->and($first['stats']['dudis']['updated'])->toBe(1)
        ->and($second['success'])->toBeTrue()
        ->and($second['stats']['students']['created'])->toBe(0)
        ->and($second['stats']['students']['updated'])->toBe(0)
        ->and($second['stats']['students']['unchanged'])->toBe(1)
        ->and($second['stats']['teachers']['created'])->toBe(0)
        ->and($second['stats']['teachers']['updated'])->toBe(0)
        ->and($second['stats']['teachers']['unchanged'])->toBe(1)
        ->and(Siswa::query()->where('nis', 'S-OP-01')->count())->toBe(1)
        ->and(Guru::query()->where('nip', 'G-OP-01')->count())->toBe(1)
        ->and(Dudi::query()->where('sipintu_id', 'DUDI-OP-01')->count())->toBe(1);

    $siswa->refresh();
    $siswaUser->refresh();
    $guru->refresh();
    $guruUser->refresh();
    $dudi->refresh();
    $dudiUser->refresh();
    $penempatan->refresh();
    $absensi->refresh();

    // Academic / identity fields updated cleanly
    expect($siswa->nama)->toBe('Nama Siswa Akademik Baru')
        ->and($siswa->class_id)->toBe($kelasBaru->id)
        ->and($guru->nama)->toBe('Guru Pembimbing Terbaru')
        ->and($dudi->nama_perusahaan)->toBe('PT Mitra Nusantara Tbk')
        ->and($dudi->alamat)->toBe('Jl. Raya Bangsri Km 5');

    // Local passwords & PKL operational data must NEVER be overwritten
    expect(Hash::check('CustomSiswaPass!123', $siswaUser->password))->toBeTrue()
        ->and(Hash::check('CustomGuruPass!123', $guruUser->password))->toBeTrue()
        ->and(Hash::check('CustomDudiPass!123', $dudiUser->password))->toBeTrue()
        ->and($dudiUser->must_change_password)->toBeFalse()
        ->and(round((float) $dudi->latitude, 5))->toBe(-6.58889)
        ->and(round((float) $dudi->longitude, 4))->toBe(110.6684)
        ->and((string) $dudi->jam_masuk)->toContain('07:30')
        ->and((string) $dudi->jam_pulang)->toContain('16:00')
        ->and($dudi->toleransi_keterlambatan)->toBe(15)
        ->and($dudi->hari_operasional)->toBe(['senin', 'selasa', 'rabu', 'kamis'])
        ->and($dudi->sso_enabled)->toBeTrue()
        ->and($penempatan->status)->toBe('aktif')
        ->and($penempatan->guru_id)->toBe($guru->id)
        ->and($penempatan->dudi_id)->toBe($dudi->id)
        ->and($penempatan->nomor_surat)->toBe('PKL/999/XI/2026')
        ->and($absensi->status)->toBe('hadir')
        ->and($absensi->lokasi_valid)->toBeTrue();
});

it('automatically syncs previously skipped students once their classroom mapping becomes available', function (): void {
    $admin = User::factory()->create(['name' => 'Admin Mapping Test']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $kelas1 = Kelas::factory()->create(['nama' => 'X AKL 1']);
    $kelas2 = Kelas::factory()->create(['nama' => 'X AKL 2']);

    SipintuClassroomMapping::query()->create([
        'classroom_id' => 901,
        'kelas_id' => $kelas1->id,
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        [
            'nis' => 'S-DELAY-01',
            'nisn' => '001001001',
            'nama' => 'Siswa Terpetakan',
            'classroom_id' => 901,
        ],
        [
            'nis' => 'S-DELAY-02',
            'nisn' => '002002002',
            'nama' => 'Siswa Menunggu Mapping',
            'classroom_id' => 902,
        ],
    ]);
    $repo->shouldReceive('fetchTeachers')->andThrow(
        SiPintuApiException::apiError('Endpoint API SiPintu (teachers) tidak ditemukan (HTTP 404).')
    );
    $repo->shouldReceive('fetchDudi')->andThrow(
        SiPintuApiException::apiError('Endpoint API SiPintu (dudi) tidak ditemukan (HTTP 404).')
    );

    $syncService = dbPersistenceSyncService($repo);

    // Run 1: S-DELAY-02 lacks mapping -> created=1, needs_mapping=1, log status='partial'
    $run1 = $syncService->runSync($admin, 'siswa');

    $log1 = SiPintuSyncLog::query()->latest('id')->first();
    expect($run1['status'])->toBe('partial')
        ->and($run1['stats']['students']['created'])->toBe(1)
        ->and($run1['stats']['students']['needs_mapping'])->toBe(1)
        ->and(Siswa::query()->where('nis', 'S-DELAY-01')->exists())->toBeTrue()
        ->and(Siswa::query()->where('nis', 'S-DELAY-02')->exists())->toBeFalse()
        ->and($log1)->not->toBeNull()
        ->and($log1->status)->toBe('partial')
        ->and($log1->entity_scope)->toBe('siswa')
        ->and($log1->remote_total)->toBe(2)
        ->and($log1->added)->toBe(1)
        ->and($log1->needs_mapping)->toBe(1);

    // Admin adds the missing mapping for classroom 902
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 902,
        'kelas_id' => $kelas2->id,
    ]);

    // Run 2: S-DELAY-02 now syncs cleanly and attaches to kelas2; S-DELAY-01 stays unchanged
    $run2 = $syncService->runSync($admin, 'siswa');

    $log2 = SiPintuSyncLog::query()->latest('id')->first();
    $syncedDelayed = Siswa::query()->where('nis', 'S-DELAY-02')->first();

    expect($run2['status'])->toBe('success')
        ->and($run2['stats']['students']['created'])->toBe(1)
        ->and($run2['stats']['students']['unchanged'])->toBe(1)
        ->and($run2['stats']['students']['needs_mapping'])->toBe(0)
        ->and($syncedDelayed)->not->toBeNull()
        ->and($syncedDelayed->class_id)->toBe($kelas2->id)
        ->and(Siswa::query()->count())->toBe(2)
        ->and($log2)->not->toBeNull()
        ->and($log2->status)->toBe('success')
        ->and($log2->added)->toBe(1)
        ->and($log2->unchanged)->toBe(1)
        ->and($log2->needs_mapping)->toBe(0);
});

it('records structured audit trail logs and renders live remote counts instead of hardcoded figures on dashboard', function (): void {
    $admin = User::factory()->create(['name' => 'Super Admin Monitor']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $kelas = Kelas::factory()->create(['nama' => 'XII TJKT 1']);
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 777,
        'kelas_id' => $kelas->id,
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->once()->andReturn([
        [
            'nis' => 'MON-001',
            'nisn' => '555000111',
            'nama' => 'Siswa Monitoring 1',
            'classroom_id' => 777,
        ],
        [
            'nis' => 'MON-002',
            'nisn' => '555000222',
            'nama' => 'Siswa Perlu Mapping',
            'classroom_id' => 888, // unmapped -> skipped + issue logged
        ],
    ]);
    $repo->shouldReceive('fetchTeachers')->never();
    $repo->shouldReceive('fetchDudi')->never();

    $this->app->instance(SiPintuRepositoryInterface::class, $repo);

    // Run sync via controller endpoint
    $syncResponse = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), [
        'entity' => 'siswa',
    ]);
    $syncResponse->assertRedirect(route('admin.sipintu-sync.index'));

    /** @var SiPintuSyncLog|null $log */
    $log = SiPintuSyncLog::query()->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->started_at)->not->toBeNull()
        ->and($log->finished_at)->not->toBeNull()
        ->and($log->entity_scope)->toBe('siswa')
        ->and($log->remote_total)->toBe(2)
        ->and($log->added)->toBe(1)
        ->and($log->skipped)->toBe(1)
        ->and($log->needs_mapping)->toBe(1)
        ->and($log->summary)->toBeArray()
        ->and($log->summary['students']['created'])->toBe(1)
        ->and($log->summary['students']['needs_mapping'])->toBe(1)
        ->and($log->error_details)->toBeArray()
        ->and(count($log->error_details))->toBeGreaterThanOrEqual(1)
        ->and($log->error_details[0]['type'])->toBe('needs_mapping');

    // Warm 5-minute diagnostics cache so GET dashboard performs zero outbound HTTP calls
    // and displays real cached remote counts (NOT 1.153 Siswa!)
    Cache::put('sipintu_live_diagnostics', [
        'success' => true,
        'status' => true,
        'connection' => true,
        'http_status' => 200,
        'message' => 'Koneksi ke Gateway SiPintu berhasil.',
        'error_type' => null,
        'detail' => null,
        'troubleshooting' => null,
        'endpoint_warning' => true,
        'endpoints' => [
            'gateway' => ['name' => 'Gateway', 'status' => true, 'http_status' => 200, 'message' => 'Online'],
            'students' => ['name' => 'Siswa', 'status' => true, 'http_status' => 200, 'message' => 'Endpoint aktif', 'count' => 2],
            'teachers' => ['name' => 'Guru', 'status' => false, 'http_status' => 404, 'message' => 'Belum tersedia (HTTP 404)', 'count' => null],
            'dudi' => ['name' => 'DUDI', 'status' => false, 'http_status' => 404, 'message' => 'Belum tersedia (HTTP 404)', 'count' => null],
        ],
    ], now()->addMinutes(5));

    Http::fake(function () {
        throw new \RuntimeException('Warm-cache GET index must never issue outbound HTTP calls');
    });

    $dashboardResponse = $this->actingAs($admin)->get(route('admin.sipintu-sync.index'));

    $dashboardResponse->assertStatus(200)
        ->assertDontSee('1.153 Siswa')
        ->assertSee('2 Siswa')
        ->assertSee('Perlu Pemetaan Kelas')
        ->assertSee('needs_mapping')
        ->assertSee('Admin &amp; Cakupan', false)
        ->assertSee('Super Admin Monitor');

    // Failed API run must also write structured failure audit trail
    $failingRepo = Mockery::mock(SiPintuRepositoryInterface::class);
    $failingRepo->shouldReceive('fetchStudents')->once()->andThrow(
        SiPintuApiException::apiError('Connection timed out on /api/v1/sijuna/students')
    );

    $failedSyncService = dbPersistenceSyncService($failingRepo);
    $failedResult = $failedSyncService->runSync($admin, 'all');

    /** @var SiPintuSyncLog|null $failedLog */
    $failedLog = SiPintuSyncLog::query()->latest('id')->first();
    expect($failedResult['success'])->toBeFalse()
        ->and($failedLog)->not->toBeNull()
        ->and($failedLog->status)->toBe('failed')
        ->and($failedLog->started_at)->not->toBeNull()
        ->and($failedLog->finished_at)->not->toBeNull()
        ->and($failedLog->error_details)->toBeArray()
        ->and($failedLog->error_details[0]['type'])->toBe('exception')
        ->and($failedLog->error_details[0]['detail'])->toContain('Connection timed out');
});
