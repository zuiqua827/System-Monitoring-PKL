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

it('enforces link-only default policy: skips unregistered DUDIs while linking and updating admin-registered DUDIs cleanly', function (): void {
    $localDudi = Dudi::factory()->create([
        'sipintu_id' => null,
        'sipintu_user_id' => null,
        'nama_perusahaan' => 'PT Lokal Sentosa',
        'email_perusahaan' => 'lokal@sentosa.id',
        'latitude' => -6.5888900,
        'longitude' => 110.6684000,
        'jam_masuk' => '07:45:00',
        'jam_pulang' => '16:15:00',
    ]);

    $dudiCountBefore = Dudi::query()->count();
    $userCountBefore = User::query()->count();

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-UNREGISTERED-01',
            'nama_perusahaan' => 'PT Baru Belum Ada Lokal',
            'email_perusahaan' => 'baru@unregistered.id',
            'status_aktif' => 1,
        ],
        [
            'id' => 'DUDI-EXISTING-LINK',
            'user_id' => 'sipintu_u_100',
            'nama_perusahaan' => 'PT Lokal Sentosa Baru',
            'email_perusahaan' => 'LOKAL@sentosa.id',
            'no_telepon' => '081234567899',
            'alamat' => 'Semarang',
            'penanggung_jawab' => 'Siti Rohmah',
            'bidang_usaha' => 'Teknologi Informasi',
            'latitude' => 1.234567,
            'longitude' => 2.345678,
            'jam_masuk' => '09:00:00',
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);

    // 1. Default preview must stay strictly read-only and classify unregistered as dilewati
    $preview = $service->previewDudi();
    expect($preview['baru'])->toBe(0)
        ->and($preview['dilewati'])->toBe(1)
        ->and($preview['diperbarui'])->toBe(1)
        ->and($preview['total_remote'])->toBe(2)
        ->and(Dudi::query()->count())->toBe($dudiCountBefore);

    // 2. Default sync (link-only) links & updates existing local DUDI without creating unregistered DUDI
    $stats = $service->syncDudi();
    expect($stats['created'])->toBe(0)
        ->and($stats['skipped'])->toBe(1)
        ->and($stats['updated'])->toBe(1)
        ->and(Dudi::query()->count())->toBe($dudiCountBefore)
        ->and(User::query()->count())->toBe($userCountBefore);

    $localDudi->refresh();
    expect($localDudi->sipintu_id)->toBe('DUDI-EXISTING-LINK')
        ->and($localDudi->sipintu_user_id)->toBe('sipintu_u_100')
        ->and($localDudi->nama_perusahaan)->toBe('PT Lokal Sentosa Baru')
        ->and($localDudi->alamat)->toBe('Semarang')
        // Geofence & working hours must NEVER be overwritten
        ->and(round((float) $localDudi->latitude, 5))->toBe(-6.58889)
        ->and(round((float) $localDudi->longitude, 4))->toBe(110.6684)
        ->and((string) $localDudi->jam_masuk)->toContain('07:45')
        ->and((string) $localDudi->jam_pulang)->toContain('16:15');
});

it('allows creating new DUDI with User account ONLY when explicit allowCreate confirmation is true', function (): void {
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
    $preview = $service->previewDudi(allowCreate: true);
    expect($preview['baru'])->toBe(1);

    $stats = $service->syncDudi(allowCreate: true);

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

it('creates DUDI without user account when remote email is missing under explicit allowCreate confirmation', function (): void {
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
    $stats = $service->syncDudi(allowCreate: true);

    expect($stats['created'])->toBe(1)
        ->and(User::query()->count())->toBe($userCountBefore);

    $dudi = Dudi::query()->where('sipintu_id', 'DUDI-NO-EMAIL')->first();
    expect($dudi)->not->toBeNull()
        ->and($dudi->user_id)->toBeNull();
});

it('detects email collision with Super Admin / non-DUDI account as conflict without corrupting role or data', function (): void {
    $admin = User::factory()->create(['email' => 'admin_pkl@smk.id']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    // Also create a DUDI accidentally pointing to Super Admin user to test update protection
    $protectedDudi = Dudi::factory()->create([
        'sipintu_id' => 'DUDI-PROTECTED-SA',
        'user_id' => $admin->id,
        'nama_perusahaan' => 'PT Protected Admin',
    ]);

    $repo = Mockery::mock(SiPintuRepositoryInterface::class);
    $repo->shouldReceive('fetchDudi')->andReturn([
        [
            'id' => 'DUDI-COLLISION',
            'nama_perusahaan' => 'PT Tabrakan Email',
            'email_perusahaan' => 'admin_pkl@smk.id',
            'status_aktif' => 1,
        ],
        [
            'id' => 'DUDI-PROTECTED-SA',
            'nama_perusahaan' => 'PT Should Not Mutate Admin',
            'status_aktif' => 1,
        ],
    ]);

    $service = dudiSyncService($repo);
    $stats = $service->syncDudi(allowCreate: true);

    expect($stats['conflicts'])->toBe(2)
        ->and(Dudi::query()->where('sipintu_id', 'DUDI-COLLISION')->exists())->toBeFalse()
        ->and($admin->fresh()->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue()
        ->and($protectedDudi->fresh()->nama_perusahaan)->toBe('PT Protected Admin');
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
