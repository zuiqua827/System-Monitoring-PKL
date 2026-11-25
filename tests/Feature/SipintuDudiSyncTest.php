<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Exceptions\SiPintuApiException;
use App\Models\Dudi;
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
    Role::findOrCreate(UserRole::DUDI->value);
});

function dudiSyncService(SiPintuRepositoryInterface $repository): SiPintuService
{
    return new SiPintuService(
        $repository,
        app(SiswaRepositoryInterface::class),
        app(GuruRepositoryInterface::class),
        app(UserRepositoryInterface::class),
        app(SipintuClassroomMappingServiceInterface::class),
    );
}

it('handles HTTP 404 gracefully during DUDI preview without throwing uncaught exceptions', function (): void {
    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andThrow(
        SiPintuApiException::apiError('Endpoint API SiPintu (dudi) tidak ditemukan (HTTP 404).')
    );

    $service = dudiSyncService($repo);
    $preview = $service->previewDudi();

    expect($preview['endpoint_unavailable'])->toBe(1)
        ->and($preview['total_remote'])->toBe(0)
        ->and($preview['endpoint_message'])->toContain('404');
});

it('previews remote DUDI correctly and keeps preview strictly read-only', function (): void {
    $localDudi = Dudi::factory()->create([
        'sipintu_id' => 'DUDI-EXISTING',
        'nama_perusahaan' => 'PT Lokal Sentosa',
        'email_perusahaan' => 'lokal@sentosa.id',
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-NEW-01',
            'nama_perusahaan' => 'PT Maju Terus',
            'email_perusahaan' => 'info@majuterus.id',
            'no_telepon' => '081234567890',
            'alamat' => 'Jepara',
            'penanggung_jawab' => 'Budi Santoso',
            'bidang_usaha' => 'Teknologi Informasi',
            'status_aktif' => 1,
        ],
        [
            'id' => 'DUDI-EXISTING',
            'nama_perusahaan' => 'PT Lokal Sentosa Baru', // modified name
            'email_perusahaan' => 'lokal@sentosa.id',
            'no_telepon' => '081234567899',
            'alamat' => 'Semarang',
            'penanggung_jawab' => 'Siti Rohmah',
            'bidang_usaha' => 'Teknologi Informasi',
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);
    $preview = $service->previewDudi();

    expect($preview['baru'])->toBe(1)
        ->and($preview['diperbarui'])->toBe(1)
        ->and($preview['total_remote'])->toBe(2)
        ->and(Dudi::query()->count())->toBe(1); // Read-only: no new record created
});

it('synchronizes new DUDI companies with User account according to CREATE policy', function (): void {
    $userCountBefore = User::query()->count();

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-AUTO-01',
            'nama_perusahaan' => 'CV Karya Mandiri',
            'email_perusahaan' => 'karya@mandiri.id',
            'no_telepon' => '08991234567',
            'alamat' => 'Bangsri, Jepara',
            'penanggung_jawab' => 'H. Sukardi',
            'bidang_usaha' => 'Otomotif',
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);
    $stats = $service->syncDudi();

    expect($stats['created'])->toBe(1)
        ->and(User::query()->count())->toBe($userCountBefore + 1);

    $createdDudi = Dudi::query()->where('sipintu_id', 'DUDI-AUTO-01')->first();
    expect($createdDudi)->not->toBeNull()
        ->and($createdDudi->user_id)->not->toBeNull()
        ->and($createdDudi->nama_perusahaan)->toBe('CV Karya Mandiri')
        ->and($createdDudi->status_aktif)->toBeTrue()
        ->and($createdDudi->sso_enabled)->toBeFalse();

    $user = $createdDudi->user;
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('karya@mandiri.id')
        ->and($user->hasRole(UserRole::DUDI->value))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull();
});

it('creates DUDI without user account when remote email is missing without failing sync', function (): void {
    $userCountBefore = User::query()->count();

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-NO-EMAIL',
            'nama_perusahaan' => 'Bengkel Tanpa Email',
            'email_perusahaan' => null,
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);
    $stats = $service->syncDudi();

    expect($stats['created'])->toBe(1)
        ->and(User::query()->count())->toBe($userCountBefore);

    $dudi = Dudi::query()->where('sipintu_id', 'DUDI-NO-EMAIL')->first();
    expect($dudi)->not->toBeNull()
        ->and($dudi->user_id)->toBeNull();
});

it('detects email collision with non-DUDI account as conflict without corrupting data', function (): void {
    $admin = User::factory()->create(['email' => 'admin_pkl@smk.id']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-COLLISION',
            'nama_perusahaan' => 'PT Tabrakan Email',
            'email_perusahaan' => 'admin_pkl@smk.id',
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);
    $stats = $service->syncDudi();

    expect($stats['conflicts'])->toBe(1)
        ->and(Dudi::query()->where('sipintu_id', 'DUDI-COLLISION')->exists())->toBeFalse();
});

it('updates existing DUDI matched by sipintu_id without affecting unrelated records', function (): void {
    $existing = Dudi::factory()->create([
        'sipintu_id' => 'DUDI-UP-01',
        'nama_perusahaan' => 'PT Lama Banget',
        'alamat' => 'Alamat Lama',
        'status_aktif' => true,
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-UP-01',
            'nama_perusahaan' => 'PT Baru Banget',
            'alamat' => 'Alamat Baru No 123',
            'penanggung_jawab' => 'Iwan Fals',
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);
    $stats = $service->syncDudi();

    expect($stats['updated'])->toBe(1);

    $existing->refresh();
    expect($existing->nama_perusahaan)->toBe('PT Baru Banget')
        ->and($existing->alamat)->toBe('Alamat Baru No 123')
        ->and($existing->sipintu_synced_at)->not->toBeNull();
});

it('does not delete local DUDI records that are absent from SiPintu response', function (): void {
    $localDudi1 = Dudi::factory()->create([
        'sipintu_id' => null,
        'nama_perusahaan' => 'Bengkel Las Lokal (Manual Entry)',
    ]);
    $localDudi2 = Dudi::factory()->create([
        'sipintu_id' => 'DUDI-OLD',
        'nama_perusahaan' => 'PT Mitra Sejati',
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([]); // Empty response

    $service = dudiSyncService($repo);
    $stats = $service->syncDudi();

    expect($stats['deleted'])->toBe(0)
        ->and(Dudi::query()->where('id', $localDudi1->id)->exists())->toBeTrue()
        ->and(Dudi::query()->where('id', $localDudi2->id)->exists())->toBeTrue();
});
