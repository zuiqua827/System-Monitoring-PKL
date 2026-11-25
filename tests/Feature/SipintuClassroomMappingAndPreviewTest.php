<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
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
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::GURU->value);
});

function mappingAdmin(): User
{
    /** @var User $admin */
    $admin = User::factory()->create(['name' => 'Super Admin Mapping']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    return $admin;
}

it('prevents 1-to-many classroom mapping conflicts and rejects soft-deleted local kelas', function (): void {
    $jurusan = Jurusan::create(['kode' => 'PPLG', 'nama' => 'PPLG']);
    $kelasA = Kelas::factory()->create(['nama' => 'XII PPLG 1', 'tingkat' => 12, 'jurusan_id' => $jurusan->id]);
    $kelasDeleted = Kelas::factory()->create(['nama' => 'XII PPLG 2', 'tingkat' => 12, 'jurusan_id' => $jurusan->id]);
    $kelasDeleted->delete();

    /** @var SipintuClassroomMappingServiceInterface $mappingService */
    $mappingService = app(SipintuClassroomMappingServiceInterface::class);

    // 1. Valid save works
    $mappingService->saveMapping(50, $kelasA->id, mappingAdmin()->id);
    expect(SipintuClassroomMapping::query()->where('classroom_id', 50)->first()?->kelas_id)->toBe($kelasA->id);

    // 2. Trying to map a different classroom_id (51) to the SAME kelasA must throw conflict exception
    expect(fn () => $mappingService->saveMapping(51, $kelasA->id, mappingAdmin()->id))
        ->toThrow(InvalidArgumentException::class, 'Konflik pemetaan');

    // 3. Trying to map to a soft-deleted kelas must throw invalid exception
    expect(fn () => $mappingService->saveMapping(51, $kelasDeleted->id, mappingAdmin()->id))
        ->toThrow(InvalidArgumentException::class);
});

it('detects invalid mappings in dashboard health check and renders warning badge safely without crashing', function (): void {
    $jurusan = Jurusan::create(['kode' => 'PM', 'nama' => 'Pemasaran']);
    $kelas = Kelas::factory()->create(['nama' => 'X PM 2', 'tingkat' => 10, 'jurusan_id' => $jurusan->id]);

    $mapping = SipintuClassroomMapping::create([
        'classroom_id' => 6,
        'kelas_id' => $kelas->id,
    ]);

    // Soft-delete the target local kelas -> mapping becomes invalid
    $kelas->delete();

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        [
            'nis' => '5768',
            'nama' => 'Siswa X PM 2',
            'classroom_id' => 6,
            'classroom' => ['id' => 6, 'name' => 'X PM 2'],
            'jk' => 2,
        ],
    ]);
    app()->instance(SiPintuRepositoryInterface::class, $repo);

    $admin = mappingAdmin();
    $res = $this->actingAs($admin)->get(route('admin.sipintu-classroom-mapping.index'));

    $res->assertOk()
        ->assertSee('Tidak Valid')
        ->assertSee('X PM 2');

    // Also resolveKelasId must return null for trashed mapped class
    $service = new SiPintuService(
        $repo,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );
    $preview = $service->previewStudents();
    expect($preview['perlu_pemetaan'])->toBe(1)
        ->and($preview['baru'])->toBe(0);
});

it('supports strict auto-map exact matches endpoint while rejecting mismatches and showing immediate preview update', function (): void {
    $pplg = Jurusan::create(['kode' => 'PPLG', 'nama' => 'PPLG']);
    $akl = Jurusan::create(['kode' => 'AKL', 'nama' => 'AKL']);

    // Valid exact class
    $kelasValid = Kelas::factory()->create(['nama' => 'XII PPLG 1', 'tingkat' => 12, 'jurusan_id' => $pplg->id]);
    // Wrong tingkat/jurusan mismatch
    Kelas::factory()->create(['nama' => 'X PM 2', 'tingkat' => 12, 'jurusan_id' => $akl->id]);

    $remoteStudents = [
        ['nis' => '5001', 'nama' => 'Cocok Eksak', 'classroom_id' => 50, 'classroom' => ['id' => 50, 'name' => 'XII PPLG 1'], 'jk' => 1],
        ['nis' => '5002', 'nama' => 'Mismatch Jurusan', 'classroom_id' => 6, 'classroom' => ['id' => 6, 'name' => 'X PM 2'], 'jk' => 2],
        ['nis' => '5003', 'nama' => 'Tanpa Nama Kelas', 'classroom_id' => 99, 'jk' => 1],
    ];

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn($remoteStudents);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);
    app()->instance(SiPintuRepositoryInterface::class, $repo);

    $admin = mappingAdmin();

    // Auto-map must map ONLY classroom_id 50 (exact match) and skip 6 & 99
    $resp = $this->actingAs($admin)->post(route('admin.sipintu-classroom-mapping.auto-map'));
    $resp->assertRedirect(route('admin.sipintu-classroom-mapping.index'))
        ->assertSessionHas('success');

    expect(SipintuClassroomMapping::query()->where('classroom_id', 50)->first()?->kelas_id)->toBe($kelasValid->id)
        ->and(SipintuClassroomMapping::query()->where('classroom_id', 6)->exists())->toBeFalse()
        ->and(SipintuClassroomMapping::query()->where('classroom_id', 99)->exists())->toBeFalse();

    // Subsequent preview immediately recognizes mapped classroom 50 while 6 & 99 remain needs_mapping
    $siPintuService = new SiPintuService(
        $repo,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );
    $preview = $siPintuService->previewStudents();
    expect($preview['baru'])->toBe(1)
        ->and($preview['perlu_pemetaan'])->toBe(2)
        ->and($preview['total_remote'])->toBe(3);
});

it('keeps preview 100% read-only and classifies all categories consistently with actual sync (duplikat, konflik, tidak_valid, gagal)', function (): void {
    $jurusan = Jurusan::create(['kode' => 'PM', 'nama' => 'Pemasaran']);
    $kelas = Kelas::factory()->create(['nama' => 'X PM 2', 'tingkat' => 10, 'jurusan_id' => $jurusan->id]);
    SipintuClassroomMapping::create(['classroom_id' => 6, 'kelas_id' => $kelas->id]);

    // Existing unchanged student
    $u1 = User::factory()->create(['name' => 'Siswa Tetap', 'email' => '1001@'.Siswa::emailDomain()]);
    $u1->assignRole(UserRole::SISWA->value);
    Siswa::create(['user_id' => $u1->id, 'class_id' => $kelas->id, 'nis' => '1001', 'nama' => 'Siswa Tetap', 'jenis_kelamin' => 'L']);

    // Existing student with name change -> update
    $u2 = User::factory()->create(['name' => 'Nama Lama', 'email' => '1002@'.Siswa::emailDomain()]);
    $u2->assignRole(UserRole::SISWA->value);
    Siswa::create(['user_id' => $u2->id, 'class_id' => $kelas->id, 'nis' => '1002', 'nama' => 'Nama Lama', 'jenis_kelamin' => 'P']);

    // Conflict: email already used by non-siswa account (e.g. Super Admin)
    $conflictAdmin = User::factory()->create(['email' => '1003@'.Siswa::emailDomain()]);
    $conflictAdmin->assignRole(UserRole::SUPER_ADMIN->value);

    // Local student absent from remote -> tidak_ditemukan
    $uAbsent = User::factory()->create(['email' => '9999@'.Siswa::emailDomain()]);
    $uAbsent->assignRole(UserRole::SISWA->value);
    Siswa::create(['user_id' => $uAbsent->id, 'class_id' => $kelas->id, 'nis' => '9999', 'nama' => 'Lokal Tidak Di Remote']);

    $payload = [
        // 1. unchanged
        ['nis' => '1001', 'nama' => 'Siswa Tetap', 'classroom_id' => 6, 'jk' => 1],
        // 2. updated
        ['nis' => '1002', 'nama' => 'Nama Baru', 'classroom_id' => 6, 'jk' => 2],
        // 3. conflict (email collision with admin)
        ['nis' => '1003', 'nama' => 'Calon Konflik', 'classroom_id' => 6, 'jk' => 1],
        // 4. created (valid new student with jk=2 => P)
        ['nis' => '1004', 'nama' => 'Siswa Baru Valid', 'classroom_id' => 6, 'classroom' => ['id' => 6, 'name' => 'X PM 2'], 'jk' => 2],
        // 5. duplicate NIS in response
        ['nis' => '1004', 'nama' => 'Siswa Baru Duplikat', 'classroom_id' => 6, 'jk' => 2],
        // 6. needs_mapping
        ['nis' => '1005', 'nama' => 'Siswa Tanpa Mapping', 'classroom_id' => 777, 'jk' => 1],
        // 7. invalid (missing nama)
        ['nis' => '1006', 'nama' => '', 'classroom_id' => 6, 'jk' => 1],
        // 8. invalid (missing NIS)
        ['nama' => 'Tanpa NIS', 'classroom_id' => 6],
    ];

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn($payload);
    $repo->shouldReceive('fetchTeachers')->andReturn([]);

    $service = new SiPintuService(
        $repo,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );

    // Run Preview (must remain strictly read-only)
    $preview = $service->previewStudents();

    expect($preview['total_remote'])->toBe(8)
        ->and($preview['baru'])->toBe(1)
        ->and($preview['diperbarui'])->toBe(1)
        ->and($preview['tidak_berubah'])->toBe(1)
        ->and($preview['konflik'])->toBe(1)
        ->and($preview['duplikat'])->toBe(1)
        ->and($preview['perlu_pemetaan'])->toBe(1)
        ->and($preview['tidak_valid'])->toBe(2)
        ->and($preview['gagal'])->toBe(0)
        ->and($preview['tidak_ditemukan'])->toBe(1)
        // Strictly read-only DB assertions
        ->and(Siswa::query()->where('nis', '1004')->exists())->toBeFalse()
        ->and(Siswa::query()->where('nis', '1002')->first()->nama)->toBe('Nama Lama');

    // Run actual sync -> exact parity with Preview!
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $result = $syncService->runSync(mappingAdmin());
    $stats = $result['stats']['students'];

    expect($stats['created'])->toBe(1)
        ->and($stats['updated'])->toBe(1)
        ->and($stats['unchanged'])->toBe(1)
        ->and($stats['conflicts'])->toBe(1)
        ->and($stats['duplicates'])->toBe(1)
        ->and($stats['needs_mapping'])->toBe(1)
        ->and($stats['invalid'])->toBe(2);

    // Check jk=2 mapped cleanly to 'P'
    $newStudent = Siswa::query()->where('nis', '1004')->first();
    expect($newStudent)->not->toBeNull()
        ->and($newStudent->jenis_kelamin)->toBe('P');
});

it('auto-creates missing Siswa role safely during live sync without affecting read-only preview', function (): void {
    // Delete 'Siswa' role to reproduce missing role DB state
    Role::query()->where('name', UserRole::SISWA->value)->delete();

    $jurusan = Jurusan::create(['kode' => 'TO', 'nama' => 'Teknik Otomotif']);
    $kelas = Kelas::factory()->create(['nama' => 'X TO 1', 'tingkat' => 10, 'jurusan_id' => $jurusan->id]);
    SipintuClassroomMapping::create(['classroom_id' => 10, 'kelas_id' => $kelas->id]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchStudents')->andReturn([
        ['nis' => '5700', 'nama' => 'Siswa TO', 'classroom_id' => 10, 'jk' => 1],
    ]);

    $service = new SiPintuService(
        $repo,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );

    // Preview does NOT write the missing role
    $preview = $service->previewStudents();
    expect($preview['baru'])->toBe(1)
        ->and(Role::query()->where('name', UserRole::SISWA->value)->exists())->toBeFalse();

    // Sync creates the missing role safely and saves student without RoleDoesNotExist errors
    $stats = $service->syncStudents();
    expect($stats['created'])->toBe(1)
        ->and($stats['errors'])->toBe(0)
        ->and(Role::query()->where('name', UserRole::SISWA->value)->exists())->toBeTrue();

    $saved = Siswa::query()->where('nis', '5700')->first();
    expect($saved)->not->toBeNull()
        ->and($saved->jenis_kelamin)->toBe('L')
        ->and($saved->user->hasRole(UserRole::SISWA->value))->toBeTrue();
});
