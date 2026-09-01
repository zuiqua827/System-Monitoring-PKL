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
