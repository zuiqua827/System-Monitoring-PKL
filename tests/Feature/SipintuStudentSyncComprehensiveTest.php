<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Exceptions\SiPintuApiException;
use App\Models\Jurusan;
use App\Models\Kelas;
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
use Illuminate\Support\Facades\Hash;
use Mockery;
use Spatie\Permission\Models\Role;

function compSyncService(object $repository): SiPintuService
{
    return new SiPintuService(
        $repository,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );
}

function compAdmin(): User
{
    /** @var User $admin */
    $admin = User::factory()->create(['name' => 'Super Admin Tester']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    return $admin;
}

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::GURU->value);
});

// =========================================================================
// CRITERION 1: Siswa valid + kelas terpetakan tersimpan ke DB & terhubung
// =========================================================================
it('criterion 1: syncs valid student with mapped class and connects to local kelas', function (): void {
    $jurusan = Jurusan::create(['nama' => 'Pengembangan Perangkat Lunak dan Gim', 'kode' => 'PPLG']);
    $kelas = Kelas::factory()->create(['nama' => 'XII PPLG 1', 'tingkat' => 12, 'jurusan_id' => $jurusan->id]);

    SipintuClassroomMapping::create([
        'classroom_id' => 14,
        'kelas_id' => $kelas->id,
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([[
        'nis' => '10001',
        'nisn' => '0012345678',
        'nama' => 'Ahmad Fajar',
        'classroom_id' => 14,
        'classroom' => ['id' => 14, 'name' => 'XII PPLG 1'],
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2008-05-10',
        'no_telepon' => '081234567890',
        'alamat' => 'Bangsri, Jepara',
        'status' => 1,
        'graduated' => false,
    ]]);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(compAdmin());

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('success')
        ->and($result['stats']['students']['created'])->toBe(1)
        ->and($result['stats']['students']['needs_mapping'])->toBe(0);

    $siswa = Siswa::query()->where('nis', '10001')->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->nama)->toBe('Ahmad Fajar')
        ->and($siswa->class_id)->toBe($kelas->id)
        ->and($siswa->kelas->nama)->toBe('XII PPLG 1')
        ->and($siswa->user)->not->toBeNull()
        ->and($siswa->user->email)->toBe('10001@'.Siswa::emailDomain())
        ->and($siswa->user->hasRole(UserRole::SISWA->value))->toBeTrue();
});

// =========================================================================
// CRITERION 2: Siswa aktif tanpa pemetaan kelas dilewati dengan needs_mapping
// =========================================================================
it('criterion 2: skips active student without classroom mapping with needs_mapping status', function (): void {
    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([[
        'nis' => '10002',
        'nama' => 'Budi Belum Terpetakan',
        'classroom_id' => 999,
        'classroom' => ['id' => 999, 'name' => 'Kelas Baru Unmapped'],
        'status' => 1,
        'graduated' => false,
    ]]);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(compAdmin());

    expect($result['stats']['students']['needs_mapping'])->toBe(1)
        ->and($result['stats']['students']['skipped'])->toBe(1)
        ->and($result['stats']['students']['created'])->toBe(0)
        ->and(Siswa::query()->where('nis', '10002')->first())->toBeNull();
});

// =========================================================================
// CRITERION 3: Siswa tanpa kelas / alumni tersimpan dengan class_id = null
// =========================================================================
it('criterion 3: syncs alumni student without class and saves with class_id null', function (): void {
    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'ALUMNI-2025',
        'nama' => 'Citra Alumni',
        'classroom_id' => null,
        'classroom' => null,
        'status' => 2,
        'graduated' => true,
    ]]);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(compAdmin());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(1)
        ->and($result['stats']['students']['needs_mapping'])->toBe(0);

    $alumni = Siswa::query()->where('nis', 'ALUMNI-2025')->first();
    expect($alumni)->not->toBeNull()
        ->and($alumni->nama)->toBe('Citra Alumni')
        ->and($alumni->class_id)->toBeNull()
        ->and($alumni->trashed())->toBeFalse();
});

// =========================================================================
// CRITERION 4: Siswa yang dilewati tersimpan tanpa duplikasi setelah mapping dibuat
// =========================================================================
it('criterion 4: saves previously skipped student after mapping is created without duplicates', function (): void {
    $jurusan = Jurusan::create(['nama' => 'Akuntansi', 'kode' => 'AKL']);
    $kelas = Kelas::factory()->create(['nama' => 'XI AKL 1', 'tingkat' => 11, 'jurusan_id' => $jurusan->id]);

    $studentData = [[
        'nis' => '10004',
        'nama' => 'Doni Tertunda',
        'classroom_id' => 45,
        'classroom' => ['id' => 45, 'name' => 'XI AKL 1 SiPintu'],
        'status' => 1,
        'graduated' => false,
    ]];

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn($studentData);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $admin = compAdmin();

    // 1. First sync without mapping: skipped
    $res1 = $syncService->runSync($admin);
    expect($res1['stats']['students']['needs_mapping'])->toBe(1)
        ->and($res1['stats']['students']['created'])->toBe(0)
        ->and(Siswa::query()->where('nis', '10004')->count())->toBe(0);

    // 2. Admin creates mapping
    SipintuClassroomMapping::create([
        'classroom_id' => 45,
        'kelas_id' => $kelas->id,
    ]);

    // 3. Second sync with mapping: created successfully
    $res2 = $syncService->runSync($admin);
    expect($res2['stats']['students']['created'])->toBe(1)
        ->and($res2['stats']['students']['needs_mapping'])->toBe(0)
        ->and(Siswa::query()->where('nis', '10004')->count())->toBe(1);

    $siswa = Siswa::query()->where('nis', '10004')->first();
    expect($siswa->class_id)->toBe($kelas->id);

    // 4. Third sync: unchanged, zero duplicate!
    $res3 = $syncService->runSync($admin);
    expect($res3['stats']['students']['created'])->toBe(0)
        ->and($res3['stats']['students']['unchanged'])->toBe(1)
        ->and(Siswa::query()->where('nis', '10004')->count())->toBe(1)
        ->and(User::query()->where('email', '10004@'.Siswa::emailDomain())->count())->toBe(1);
});

// =========================================================================
// CRITERION 5: Idempotensi sinkronisasi berulang dan pembaruan data
// =========================================================================
it('criterion 5: idempotent on repeated sync, updating changes without duplicating records', function (): void {
    $kelas = Kelas::factory()->create();
    SipintuClassroomMapping::create(['classroom_id' => 10, 'kelas_id' => $kelas->id]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn(
        // Run 1: Original
        [[
            'nis' => '10005',
            'nama' => 'Eka Pratama',
            'classroom_id' => 10,
            'no_telepon' => '0811111111',
            'status' => 1,
        ]],
        // Run 2: Name & Phone updated
        [[
            'nis' => '10005',
            'nama' => 'Eka Pratama S.T.',
            'classroom_id' => 10,
            'no_telepon' => '0822222222',
            'status' => 1,
        ]],
    );
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $admin = compAdmin();

    $res1 = $syncService->runSync($admin);
    expect($res1['stats']['students']['created'])->toBe(1);

    $res2 = $syncService->runSync($admin);
    expect($res2['stats']['students']['updated'])->toBe(1)
        ->and($res2['stats']['students']['created'])->toBe(0)
        ->and(Siswa::query()->where('nis', '10005')->count())->toBe(1);

    $siswa = Siswa::query()->where('nis', '10005')->first();
    expect($siswa->nama)->toBe('Eka Pratama S.T.')
        ->and($siswa->no_telepon)->toBe('0822222222');
});

// =========================================================================
// CRITERION 6: Password siswa lokal tidak tertimpa saat sinkronisasi ulang
// =========================================================================
it('criterion 6: protects local student password from being overwritten during sync updates', function (): void {
    $kelas = Kelas::factory()->create();
    SipintuClassroomMapping::create(['classroom_id' => 10, 'kelas_id' => $kelas->id]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([[
        'nis' => '10006',
        'nama' => 'Fani Wijaya',
        'classroom_id' => 10,
        'status' => 1,
    ]]);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $admin = compAdmin();

    // 1. Initial sync
    $syncService->runSync($admin);

    $siswa = Siswa::query()->where('nis', '10006')->first();
    $user = $siswa->user;

    // Student changes password locally
    $customPasswordHash = Hash::make('rahasia-siswa-lokal-123');
    $user->forceFill(['password' => $customPasswordHash])->save();

    // 2. Re-sync with updated phone
    $repo2 = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo2->shouldReceive('fetchStudents')->andReturn([[
        'nis' => '10006',
        'nama' => 'Fani Wijaya',
        'classroom_id' => 10,
        'no_telepon' => '0877777777',
        'status' => 1,
    ]]);
    $repo2->shouldReceive('fetchTeachers')->andReturn([]);

    $service2 = compSyncService($repo2);
    $syncService2 = new SipintuSyncService($service2, app(SipintuSyncLogRepositoryInterface::class));
    $syncService2->runSync($admin);

    // Assert password remains unchanged
    $user->refresh();
    expect(Hash::check('rahasia-siswa-lokal-123', $user->password))->toBeTrue();
});

// =========================================================================
// CRITERION 7: Statistik sinkronisasi transparan di semua kategori
// =========================================================================
it('criterion 7: provides fully transparent statistics breakdown for all sync categories', function (): void {
    $kelas = Kelas::factory()->create();
    SipintuClassroomMapping::create(['classroom_id' => 10, 'kelas_id' => $kelas->id]);

    // Create a conflict user with another role
    $conflictUser = User::factory()->create(['email' => '10009@'.Siswa::emailDomain()]);
    $conflictUser->assignRole(UserRole::GURU->value);

    // Existing student to be updated
    $existingUser = User::factory()->create(['email' => '10008@'.Siswa::emailDomain()]);
    $existingUser->assignRole(UserRole::SISWA->value);
    Siswa::create([
        'user_id' => $existingUser->id,
        'nis' => '10008',
        'nama' => 'Nama Lama',
        'class_id' => $kelas->id,
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        // 1. Baru (mapped)
        ['nis' => '10007', 'nama' => 'Siswa Baru', 'classroom_id' => 10, 'status' => 1],
        // 2. Update (mapped)
        ['nis' => '10008', 'nama' => 'Nama Baru Diperbarui', 'classroom_id' => 10, 'status' => 1],
        // 3. Dilewati karena mapping (unmapped)
        ['nis' => '10010', 'nama' => 'Siswa Unmapped', 'classroom_id' => 999, 'status' => 1],
        // 4. Dilewati karena konflik email
        ['nis' => '10009', 'nama' => 'Siswa Konflik', 'classroom_id' => 10, 'status' => 1],
    ]);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(compAdmin());

    expect($result['stats']['students']['created'])->toBe(1)
        ->and($result['stats']['students']['updated'])->toBe(1)
        ->and($result['stats']['students']['skipped'])->toBe(2)
        ->and($result['stats']['students']['needs_mapping'])->toBe(1)
        ->and($result['stats']['students']['conflicts'])->toBe(1)
        ->and($result['stats']['students']['errors'])->toBe(0)
        ->and($result['message'])->toContain('Siswa: 1 baru, 1 diperbarui, 0 tidak berubah, 2 dilewati (1 perlu pemetaan, 1 konflik), 0 error.');
});

// =========================================================================
// CRITERION 8: Tidak melaporkan success hijau saat 0 tersimpan padahal data ada
// =========================================================================
it('criterion 8: flags sync as partial warning when all received students are unmapped with 0 saved', function (): void {
    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        ['nis' => 'UNMAP-01', 'nama' => 'Siswa Satu', 'classroom_id' => 888, 'status' => 1],
        ['nis' => 'UNMAP-02', 'nama' => 'Siswa Dua', 'classroom_id' => 888, 'status' => 1],
    ]);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $admin = compAdmin();

    $result = $syncService->runSync($admin);

    // Verify partial status and warning flag
    expect($result['status'])->toBe('partial')
        ->and($result['warning'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(0)
        ->and($result['stats']['students']['needs_mapping'])->toBe(2)
        ->and($result['message'])->toContain('Perhatian: Siswa SiPintu belum tersimpan ke database SIMONGAN karena memerlukan pemetaan kelas');

    // Verify database log record has status = 'partial' (NOT 'success')
    $lastLog = SiPintuSyncLog::query()->latest('id')->first();
    expect($lastLog)->not->toBeNull()
        ->and($lastLog->status)->toBe('partial')
        ->and($lastLog->message)->toContain('Perhatian: Siswa SiPintu belum tersimpan');

    // Test controller redirects with 'warning' flash instead of 'success'
    app()->instance(SiPintuRepositoryInterface::class, $repo);
    $response = $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'));
    $response->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('warning')
        ->assertSessionMissing('success');
});

// =========================================================================
// CRITERION 9: API endpoint siswa error ditangani secara graceful
// =========================================================================
it('criterion 9: handles API connection failure gracefully with exception and log without crashing', function (): void {
    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andThrow(SiPintuApiException::apiError('Gateway timeout 504'));

    $service = compSyncService($repo);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $admin = compAdmin();

    $result = $syncService->runSync($admin);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Gateway timeout 504');

    $failedLog = SiPintuSyncLog::query()->latest('id')->first();
    expect($failedLog)->not->toBeNull()
        ->and($failedLog->status)->toBe('failed');
});

// =========================================================================
// CRITERION 10: Classroom mapping UI dan logic berjalan lancar
// =========================================================================
it('criterion 10: classroom mapping service extracts id and name, and controller stores and applies mapping', function (): void {
    $admin = compAdmin();
    $jurusan = Jurusan::create(['nama' => 'Teknik Komputer dan Jaringan', 'kode' => 'TKJ']);
    $kelas = Kelas::factory()->create(['nama' => 'X TKJ 1', 'tingkat' => 10, 'jurusan_id' => $jurusan->id]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        ['nis' => 'MAP-01', 'nama' => 'Siswa TKJ', 'classroom_id' => 77, 'classroom' => ['id' => 77, 'name' => 'X TKJ 1']],
    ]);
    app()->instance(SiPintuRepositoryInterface::class, $repo);

    // 1. Test index page displays classroom_id and extracted name
    $responseIndex = $this->actingAs($admin)->get(route('admin.sipintu-classroom-mapping.index'));
    $responseIndex->assertOk()
        ->assertSee('ID: 77')
        ->assertSee('X TKJ 1');

    // 2. Test storing mapping via controller
    $responseStore = $this->actingAs($admin)->post(route('admin.sipintu-classroom-mapping.store'), [
        'classroom_id' => 77,
        'kelas_id' => $kelas->id,
    ]);
    $responseStore->assertRedirect(route('admin.sipintu-classroom-mapping.index'))
        ->assertSessionHas('success');

    expect(SipintuClassroomMapping::query()->where('classroom_id', 77)->first()->kelas_id)->toBe($kelas->id);

    // 3. Test applyMappings updating existing local student with null class_id
    $studentUser = User::factory()->create(['email' => 'map-01@'.Siswa::emailDomain()]);
    $studentUser->assignRole(UserRole::SISWA->value);
    $siswa = Siswa::create([
        'user_id' => $studentUser->id,
        'nis' => 'MAP-01',
        'nama' => 'Siswa TKJ',
        'class_id' => null,
    ]);

    $responseApply = $this->actingAs($admin)->post(route('admin.sipintu-classroom-mapping.apply'));
    $responseApply->assertRedirect(route('admin.sipintu-classroom-mapping.index'))
        ->assertSessionHas('success');

    $siswa->refresh();
    expect($siswa->class_id)->toBe($kelas->id);
});
