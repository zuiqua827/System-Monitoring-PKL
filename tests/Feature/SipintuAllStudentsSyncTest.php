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
use App\Repositories\SiPintuRepository;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use App\Services\SiPintuService;
use App\Services\SipintuSyncService;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

function allStudentsServiceWithRepository(object $repository): SiPintuService
{
    return new SiPintuService(
        $repository,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );
}

function syncAdminUser(): User
{
    return User::factory()->create(['name' => 'Admin SiPintu']);
}

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::GURU->value);
});

// 1. Siswa aktif dari SiPintu ikut tersinkron (dengan kelas yang sesuai)
it('syncs active students from SiPintu and maps to local kelas by classroom name', function (): void {
    $kelas = Kelas::factory()->create(['nama' => 'XII PPLG 1']);

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-AKTIF-001',
        'nisn' => 'NISN-AKTIF-001',
        'nama' => 'Budi Aktif',
        'classroom_id' => 12,
        'classroom' => [
            'id' => 12,
            'name' => 'XII PPLG 1',
        ],
        'graduated' => false,
        'status' => 1,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $preview = $service->previewStudents();
    expect($preview['baru'])->toBe(1)
        ->and($preview['perlu_pemetaan'])->toBe(0);

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(1)
        ->and($result['stats']['students']['needs_mapping'])->toBe(0);

    $siswa = Siswa::query()->where('nis', 'S-AKTIF-001')->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->class_id)->toBe($kelas->id)
        ->and($siswa->trashed())->toBeFalse()
        ->and($siswa->user)->not->toBeNull()
        ->and($siswa->user->email)->toBe('s-aktif-001@'.Siswa::emailDomain())
        ->and($siswa->user->hasRole(UserRole::SISWA->value))->toBeTrue();
});

// 2. Siswa alumni tetap tersinkron (dengan class_id = null)
it('syncs alumni students cleanly with class_id=null', function (): void {
    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-ALUMNI-002',
        'nama' => 'Siswa Alumni Hebat',
        'classroom_id' => null,
        'classroom' => null,
        'graduated' => true,
        'status' => 2,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(1);

    $alumni = Siswa::query()->where('nis', 'S-ALUMNI-002')->first();
    expect($alumni)->not->toBeNull()
        ->and($alumni->class_id)->toBeNull()
        ->and($alumni->trashed())->toBeFalse();
});

// 3. Siswa nonaktif ikut tersinkron jika memang dikembalikan SiPintu
it('syncs nonactive students from SiPintu and keeps them safely soft-deleted', function (): void {
    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-NONAKTIF-003',
        'nama' => 'Siswa Keluar Nonaktif',
        'classroom_id' => null,
        'classroom' => null,
        'status' => 'nonaktif',
        'deleted_at' => '2026-09-01 10:00:00',
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(1);

    $siswa = Siswa::withTrashed()->where('nis', 'S-NONAKTIF-003')->first();
    expect($siswa)->not->toBeNull()
        ->and($siswa->trashed())->toBeTrue()
        ->and($siswa->user()->withTrashed()->first()->trashed())->toBeTrue();
});

// 4. Sinkronisasi kedua tidak membuat duplicate siswa (idempotensi)
it('does not duplicate students on repeated synchronizations', function (): void {
    $kelas = Kelas::factory()->create(['nama' => 'X PM 1']);

    $students = [
        [
            'nis' => 'S-MULTI-1',
            'nama' => 'Siswa Satu',
            'classroom_id' => 1,
            'classroom' => ['id' => 1, 'name' => 'X PM 1'],
            'graduated' => false,
            'status' => 1,
        ],
        [
            'nis' => 'S-MULTI-2',
            'nama' => 'Siswa Alumni',
            'classroom_id' => null,
            'classroom' => null,
            'graduated' => true,
            'status' => 2,
        ],
    ];

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn($students);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $firstRun = $syncService->runSync(syncAdminUser());
    expect($firstRun['stats']['students']['created'])->toBe(2)
        ->and(Siswa::query()->count())->toBe(2);

    $secondRun = $syncService->runSync(syncAdminUser());
    expect($secondRun['stats']['students']['created'])->toBe(0)
        ->and($secondRun['stats']['students']['unchanged'])->toBe(2)
        ->and(Siswa::query()->count())->toBe(2);
});

// 5. Pagination SiPintu mengambil seluruh halaman
it('fetches and aggregates all pages when SiPintu returns paginated data', function (): void {
    config()->set('services.sipintu.api_url', 'https://sipintu.test');
    config()->set('services.sipintu.client_id', 'client_test');
    config()->set('services.sipintu.client_secret', 'secret_test');

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/students?page=2*' => Http::response([
            'status' => 'success',
            'data' => [
                ['nis' => 'PAG-003', 'nama' => 'Student Page 2 A'],
                ['nis' => 'PAG-004', 'nama' => 'Student Page 2 B'],
            ],
            'meta' => [
                'current_page' => 2,
                'last_page' => 2,
                'total' => 4,
            ],
        ], 200),
        'https://sipintu.test/api/v1/sijuna/students*' => Http::response([
            'status' => 'success',
            'data' => [
                ['nis' => 'PAG-001', 'nama' => 'Student Page 1 A'],
                ['nis' => 'PAG-002', 'nama' => 'Student Page 1 B'],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 2,
                'total' => 4,
            ],
        ], 200),
    ]);

    $repo = new SiPintuRepository();
    $allStudents = $repo->fetchStudents();

    expect(count($allStudents))->toBe(4)
        ->and($allStudents[0]['nis'])->toBe('PAG-001')
        ->and($allStudents[1]['nis'])->toBe('PAG-002')
        ->and($allStudents[2]['nis'])->toBe('PAG-003')
        ->and($allStudents[3]['nis'])->toBe('PAG-004');
});

// 6. Data lama diperbarui ketika data SiPintu berubah
it('updates existing student fields when SiPintu data changes', function (): void {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 's-update-01@'.Siswa::emailDomain(),
    ]);
    $user->assignRole(UserRole::SISWA->value);

    $siswa = Siswa::factory()->create([
        'user_id' => $user->id,
        'nis' => 'S-UPDATE-01',
        'nama' => 'Old Name',
        'no_telepon' => '0811111111',
    ]);

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-UPDATE-01',
        'nama' => 'New Name From SiPintu',
        'no_telepon' => '0899999999',
        'classroom_id' => null,
        'classroom' => null,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['updated'])->toBe(1);

    $freshSiswa = Siswa::query()->where('nis', 'S-UPDATE-01')->first();
    expect($freshSiswa->nama)->toBe('New Name From SiPintu')
        ->and($freshSiswa->no_telepon)->toBe('0899999999')
        ->and($freshSiswa->user->name)->toBe('New Name From SiPintu');
});

// 7. Siswa lokal yang tidak muncul pada satu halaman API tidak otomatis dihapus
it('does not delete local students missing from the API response', function (): void {
    $existingUser = User::factory()->create(['email' => 's-local-keep@'.Siswa::emailDomain()]);
    $existingUser->assignRole(UserRole::SISWA->value);
    $localStudent = Siswa::factory()->create([
        'user_id' => $existingUser->id,
        'nis' => 'S-LOCAL-KEEP',
        'nama' => 'Siswa Lokal Bertahan',
    ]);

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    // Remote only returns a completely different student
    $repository->shouldReceive('fetchStudents')->andReturn([[
        'nis' => 'S-REMOTE-ONLY',
        'nama' => 'Siswa Baru Remote',
        'classroom_id' => null,
        'classroom' => null,
    ]]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and(Siswa::query()->where('nis', 'S-LOCAL-KEEP')->exists())->toBeTrue()
        ->and(Siswa::query()->where('nis', 'S-REMOTE-ONLY')->exists())->toBeTrue();
});

// 8. Error API ditangani dengan aman tanpa merusak data lokal
it('handles API errors safely without altering existing local database records', function (): void {
    $user = User::factory()->create(['email' => 's-safe@'.Siswa::emailDomain()]);
    $user->assignRole(UserRole::SISWA->value);
    Siswa::factory()->create([
        'user_id' => $user->id,
        'nis' => 'S-SAFE',
        'nama' => 'Siswa Aman',
    ]);

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andThrow(SiPintuApiException::apiError('Gateway timeout 504'));

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Gateway timeout 504')
        ->and(Siswa::query()->count())->toBe(1)
        ->and(Siswa::query()->where('nis', 'S-SAFE')->first()->nama)->toBe('Siswa Aman')
        ->and(SiPintuSyncLog::query()->where('status', 'failed')->count())->toBe(1);
});

// 9. Response kosong tidak menyebabkan seluruh data lokal terhapus
it('does not wipe local students when API returns an empty dataset', function (): void {
    $user = User::factory()->create(['email' => 's-empty-guard@'.Siswa::emailDomain()]);
    $user->assignRole(UserRole::SISWA->value);
    Siswa::factory()->create([
        'user_id' => $user->id,
        'nis' => 'S-EMPTY-GUARD',
        'nama' => 'Data Siswa Aman',
    ]);

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn([]);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));

    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and(Siswa::query()->count())->toBe(1)
        ->and(Siswa::query()->where('nis', 'S-EMPTY-GUARD')->exists())->toBeTrue();
});

// 10. Jumlah data hasil sinkronisasi dapat diverifikasi secara akurat
it('verifies exact counts and classifications for active, alumni, and nonactive students', function (): void {
    $kelasX = Kelas::factory()->create(['nama' => 'X MPLB 1']);
    $kelasXI = Kelas::factory()->create(['nama' => 'XI AKL 1']);

    $mockData = [
        // 2 Siswa Aktif
        ['nis' => 'TEST-01', 'nama' => 'Aktif Satu', 'classroom_id' => 1, 'classroom' => ['id' => 1, 'name' => 'X MPLB 1'], 'status' => 1, 'graduated' => false],
        ['nis' => 'TEST-02', 'nama' => 'Aktif Dua', 'classroom_id' => 2, 'classroom' => ['id' => 2, 'name' => 'XI AKL 1'], 'status' => 1, 'graduated' => false],
        // 2 Siswa Alumni
        ['nis' => 'TEST-03', 'nama' => 'Alumni Satu', 'classroom_id' => null, 'classroom' => null, 'status' => 2, 'graduated' => true],
        ['nis' => 'TEST-04', 'nama' => 'Alumni Dua', 'classroom_id' => null, 'classroom' => null, 'status' => 'alumni', 'graduated' => true],
        // 1 Siswa Nonaktif
        ['nis' => 'TEST-05', 'nama' => 'Nonaktif Satu', 'classroom_id' => null, 'classroom' => null, 'status' => 'nonaktif', 'deleted_at' => '2026-09-01 00:00:00'],
    ];

    $repository = Mockery::mock(SiPintuRepositoryInterface::class);
    $repository->shouldReceive('fetchStudents')->andReturn($mockData);
    $repository->shouldReceive('fetchTeachers')->andReturn([]);

    $service = allStudentsServiceWithRepository($repository);
    $preview = $service->previewStudents();

    expect($preview['baru'])->toBe(5)
        ->and($preview['perlu_pemetaan'])->toBe(0)
        ->and($preview['konflik'])->toBe(0)
        ->and($preview['total_remote'])->toBe(5);

    $syncService = new SipintuSyncService($service, app(SipintuSyncLogRepositoryInterface::class));
    $result = $syncService->runSync(syncAdminUser());

    expect($result['success'])->toBeTrue()
        ->and($result['stats']['students']['created'])->toBe(5)
        ->and($result['stats']['students']['skipped'])->toBe(0)
        ->and(Siswa::withTrashed()->count())->toBe(5)
        // 4 Siswa Aktif + Alumni (non-trashed)
        ->and(Siswa::query()->withoutTrashed()->count())->toBe(4)
        // 1 Siswa Nonaktif (trashed)
        ->and(Siswa::query()->onlyTrashed()->count())->toBe(1)
        // 2 Siswa dengan kelas
        ->and(Siswa::query()->whereNotNull('class_id')->count())->toBe(2)
        // 2 Siswa alumni tanpa kelas
        ->and(Siswa::query()->withoutTrashed()->whereNull('class_id')->count())->toBe(2);
});
