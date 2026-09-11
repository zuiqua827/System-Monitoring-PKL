<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\SiPintuApiException;
use App\Models\Guru;
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
use Spatie\Permission\Models\Role;

function sipintuServiceWithRepository(object $repository): SiPintuService
{
    return new SiPintuService(
        $repository,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );
}

function sipintuAdmin(): User
{
    return User::factory()->create(['name' => 'Admin SiPintu']);
}

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::GURU->value);
});

it('keeps preview read-only and syncs each NIS and NIP only once', function (): void {
    $kelas = Kelas::factory()->create();
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 101,
        'kelas_id' => $kelas->id,
    ]);

    $students = [[
        'nis' => 'S-001',
        'nisn' => 'NISN-001',
        'nama' => 'Siswa SiPintu',
        'classroom_id' => 101,
    ]];
    $teachers = [[
        'nip' => 'G-001',
        'nama' => 'Guru SiPintu',
    ]];

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn($students);
    $repository->shouldReceive('fetchTeachers')->andReturn($teachers);

    $service = sipintuServiceWithRepository($repository);
    $admin = sipintuAdmin();

    $previewStudents = $service->previewStudents();
    $previewTeachers = $service->previewTeachers();

    expect($previewStudents['baru'])->toBe(1)
        ->and($previewTeachers['baru'])->toBe(1)
        ->and(Siswa::query()->count())->toBe(0)
        ->and(Guru::query()->count())->toBe(0)
        ->and(User::query()->count())->toBe(1);

    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $first = $syncService->runSync($admin);
    $second = $syncService->runSync($admin);

    expect($first['success'])->toBeTrue()
        ->and($first['stats']['students']['created'])->toBe(1)
        ->and($first['stats']['teachers']['created'])->toBe(1)
        ->and($second['success'])->toBeTrue()
        ->and($second['stats']['students']['created'])->toBe(0)
        ->and($second['stats']['students']['unchanged'])->toBe(1)
        ->and($second['stats']['teachers']['created'])->toBe(0)
        ->and($second['stats']['teachers']['unchanged'])->toBe(1)
        ->and(Siswa::query()->where('nis', 'S-001')->count())->toBe(1)
        ->and(Guru::query()->where('nip', 'G-001')->count())->toBe(1)
        ->and(User::query()->count())->toBe(3)
        ->and(SiPintuSyncLog::query()->where('status', 'success')->count())->toBe(2);
});

it('skips students without classroom mappings without failing the sync', function (): void {
    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-UNMAPPED',
        'nama' => 'Siswa Tanpa Mapping',
        'classroom_id' => 999,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = sipintuServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(sipintuAdmin());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['needs_mapping'])->toBe(1)
        ->and($result['stats']['students']['skipped'])->toBe(1)
        ->and(Siswa::query()->count())->toBe(0)
        ->and(User::query()->count())->toBe(1);
});

it('records an API failure without writing synchronized records', function (): void {
    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andThrow(SiPintuApiException::connectionError());

    $service = sipintuServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(sipintuAdmin());

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Gagal terhubung')
        ->and(Siswa::query()->count())->toBe(0)
        ->and(Guru::query()->count())->toBe(0)
        ->and(SiPintuSyncLog::query()->where('status', 'failed')->count())->toBe(1);
});

it('syncs alumni students with classroom=null cleanly without flagging needs_mapping', function (): void {
    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-ALUMNI',
        'nama' => 'Siswa Alumni',
        'classroom' => null,
        'classroom_id' => null,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = sipintuServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $preview = $service->previewStudents();
    expect($preview['baru'])->toBe(1)
        ->and($preview['perlu_pemetaan'])->toBe(0);

    $result = $syncService->runSync(sipintuAdmin());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(1)
        ->and($result['stats']['students']['needs_mapping'])->toBe(0);

    $alumni = Siswa::query()->where('nis', 'S-ALUMNI')->first();
    expect($alumni)->not->toBeNull()
        ->and($alumni->class_id)->toBeNull();
});

it('does not overwrite existing user passwords or delete local records during sync', function (): void {
    $kelas = Kelas::factory()->create();
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 202,
        'kelas_id' => $kelas->id,
    ]);

    $user = User::factory()->create([
        'name' => 'Existing Siswa',
        'email' => 's-existing@smk1bangsri.sch.id',
        'password' => '$2y$10$CustomHashedPasswordValue1234567890',
    ]);
    $user->assignRole(UserRole::SISWA->value);

    $siswa = Siswa::factory()->create([
        'user_id' => $user->id,
        'nis' => 'S-EXISTING',
        'nama' => 'Existing Siswa',
        'class_id' => $kelas->id,
    ]);

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-EXISTING',
        'nama' => 'Existing Siswa Updated Name',
        'classroom_id' => 202,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = sipintuServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(sipintuAdmin());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['updated'])->toBe(1);

    $reloadedUser = User::query()->find($user->id);
    expect($reloadedUser->password)->toBe($user->password)
        ->and($reloadedUser->name)->toBe('Existing Siswa Updated Name');
});

it('handles large payloads safely in batch mode without creating duplicate records', function (): void {
    $kelas = Kelas::factory()->create();
    SipintuClassroomMapping::query()->create([
        'classroom_id' => 303,
        'kelas_id' => $kelas->id,
    ]);

    $largeStudents = [];
    for ($i = 1; $i <= 150; $i++) {
        $largeStudents[] = [
            'nis' => sprintf('S-BATCH-%03d', $i),
            'nisn' => sprintf('NISN-BATCH-%03d', $i),
            'nama' => sprintf('Siswa Batch %d', $i),
            'classroom_id' => 303,
        ];
    }

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn($largeStudents);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = sipintuServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(sipintuAdmin());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(150)
        ->and(Siswa::query()->count())->toBe(150);
});

it('sanitizes client_id and client_secret from test connection results', function (): void {
    config([
        'services.sipintu.api_url' => 'https://invalid-sipintu-domain-12345.com',
        'services.sipintu.client_id' => 'SECRET_CLIENT_ID_XYZ',
        'services.sipintu.client_secret' => 'SECRET_CLIENT_PASS_999',
    ]);

    Illuminate\Support\Facades\Http::fake([
        'https://invalid-sipintu-domain-12345.com/*' => Illuminate\Support\Facades\Http::response('X-Client-ID: SECRET_CLIENT_ID_XYZ error', 401),
    ]);

    $repository = app(SiPintuRepositoryInterface::class);
    $result = $repository->testConnection();

    expect($result['success'])->toBeFalse()
        ->and($result['error_type'])->toBe('authentication')
        ->and(json_encode($result))->not->toContain('SECRET_CLIENT_ID_XYZ')
        ->not->toContain('SECRET_CLIENT_PASS_999');
});

it('renders GET /admin/sipintu-sync instantly without calling remote API', function (): void {
    Illuminate\Support\Facades\Http::fake(function () {
        throw new \Exception('Remote HTTP call should NEVER be executed on GET index page');
    });

    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    $admin = sipintuAdmin();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $response = $this->actingAs($admin)->get(route('admin.sipintu-sync.index'));

    $response->assertStatus(200)
        ->assertViewIs('admin.sipintu-sync.index')
        ->assertSee('Sinkronisasi SiPintu');
});

it('runs test connection via POST route safely without fatal errors', function (): void {
    Illuminate\Support\Facades\Http::fake([
        'https://sipintu.smkn1bangsri.sch.id/*' => Illuminate\Support\Facades\Http::response(['status' => true], 200),
    ]);

    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    $admin = sipintuAdmin();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $response = $this->actingAs($admin)->post(route('admin.sipintu-sync.test-connection'));

    $response->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('success');
});



