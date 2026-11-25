<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Absensi;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\PenempatanPKLLocationLog;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(RoleAndPermissionSeeder::class);
    Storage::fake('public');
    Carbon::setTestNow('2026-11-25 07:45:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function createLocationTestContext(): array
{
    $admin = User::factory()->create(['name' => 'Admin Lokasi', 'email_verified_at' => now()]);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    $guruUserA = User::factory()->create(['name' => 'Guru Pembimbing A', 'email_verified_at' => now()]);
    $guruUserA->assignRole(UserRole::GURU->value);
    $guruA = Guru::factory()->create(['user_id' => $guruUserA->id, 'nama' => 'Guru Pembimbing A']);

    $guruUserB = User::factory()->create(['name' => 'Guru Pembimbing B', 'email_verified_at' => now()]);
    $guruUserB->assignRole(UserRole::GURU->value);
    $guruB = Guru::factory()->create(['user_id' => $guruUserB->id, 'nama' => 'Guru Pembimbing B']);

    $dudiUser = User::factory()->create(['name' => 'PT Lokasi Maju', 'email_verified_at' => now()]);
    $dudiUser->assignRole(UserRole::DUDI->value);
    // DUDI office coordinates around Jepara (-6.5888900, 110.6684000)
    $dudi = Dudi::factory()->create([
        'user_id' => $dudiUser->id,
        'nama_perusahaan' => 'PT Lokasi Maju',
        'latitude' => -6.5888900,
        'longitude' => 110.6684000,
        'jam_masuk' => '08:00:00',
        'batas_terlambat' => '08:15:00',
        'batas_sangat_terlambat' => '09:00:00',
        'jam_pulang' => '16:00:00',
        'status_aktif' => true,
    ]);

    $siswaUserA = User::factory()->create(['name' => 'Siswa WFO/WFH A', 'email_verified_at' => now()]);
    $siswaUserA->assignRole(UserRole::SISWA->value);
    $siswaA = Siswa::factory()->create(['user_id' => $siswaUserA->id, 'nama' => 'Siswa A']);

    $siswaUserB = User::factory()->create(['name' => 'Siswa Independen B', 'email_verified_at' => now()]);
    $siswaUserB->assignRole(UserRole::SISWA->value);
    $siswaB = Siswa::factory()->create(['user_id' => $siswaUserB->id, 'nama' => 'Siswa B']);

    $periode = PeriodePKL::factory()->create([
        'tanggal_mulai' => '2026-11-01',
        'tanggal_selesai' => '2026-12-31',
        'status' => 'aktif',
    ]);

    $penempatanA = PenempatanPKL::factory()->create([
        'periode_pkl_id' => $periode->id,
        'guru_id' => $guruA->id,
        'dudi_id' => $dudi->id,
        'siswa_id' => $siswaA->id,
        'tanggal_mulai' => '2026-11-01',
        'tanggal_selesai' => '2026-12-31',
        'status' => 'aktif',
        'mode_kerja' => 'WFO',
        'validasi_lokasi' => true,
    ]);

    $penempatanB = PenempatanPKL::factory()->create([
        'periode_pkl_id' => $periode->id,
        'guru_id' => $guruB->id,
        'dudi_id' => $dudi->id,
        'siswa_id' => $siswaB->id,
        'tanggal_mulai' => '2026-11-01',
        'tanggal_selesai' => '2026-12-31',
        'status' => 'aktif',
        'mode_kerja' => 'WFO',
        'validasi_lokasi' => true,
    ]);

    return compact(
        'admin',
        'guruUserA',
        'guruA',
        'guruUserB',
        'guruB',
        'dudiUser',
        'dudi',
        'siswaUserA',
        'siswaA',
        'siswaUserB',
        'siswaB',
        'periode',
        'penempatanA',
        'penempatanB',
    );
}

it('enforces WFO geofence within DUDI radius when validasi_lokasi is active and rejects outside or missing GPS', function (): void {
    $ctx = createLocationTestContext();

    // Missing GPS rejected cleanly
    $missingGps = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), []);

    $missingGps->assertRedirect();
    expect(session('error'))->toContain('Koordinat GPS wajib dikirim')
        ->and(Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->count())->toBe(0);

    // Outside 100m DUDI radius (~1.1 km away) rejected cleanly
    $outside = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), [
            'latitude' => -6.5990000,
            'longitude' => 110.6684000,
        ]);

    $outside->assertRedirect();
    expect(session('error'))->toContain('di luar area PKL')
        ->and(Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->count())->toBe(0);

    // Inside DUDI radius (~22m away) succeeds for check-in & check-out
    $inside = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), [
            'latitude' => -6.5890000,
            'longitude' => 110.6684500,
            'accuracy' => 12,
        ]);

    $inside->assertRedirect(route('siswa.absensi.index'))
        ->assertSessionHas('success');

    $absensi = Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->first();
    expect($absensi)->not->toBeNull()
        ->and($absensi->mode_kerja)->toBe('WFO')
        ->and($absensi->validasi_lokasi_aktif)->toBeTrue()
        ->and($absensi->lokasi_valid)->toBeTrue()
        ->and($absensi->radius)->toBe(100)
        ->and((float) $absensi->jarak)->toBeLessThanOrEqual(100.0);

    // Check-out outside radius must also be rejected when validasi_lokasi is active
    Carbon::setTestNow('2026-11-25 16:05:00');
    $checkOutOutside = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-out'), [
            'latitude' => -6.6200000,
            'longitude' => 110.6684000,
        ]);
    $checkOutOutside->assertRedirect();
    expect(session('error'))->toContain('di luar area PKL')
        ->and($absensi->fresh()->jam_keluar)->toBeNull();

    // Check-out inside radius succeeds
    $checkOutInside = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-out'), [
            'latitude' => -6.5889500,
            'longitude' => 110.6684100,
        ]);
    $checkOutInside->assertRedirect(route('siswa.absensi.index'))
        ->assertSessionHas('success');
    expect($absensi->fresh()->jam_keluar)->not->toBeNull();
});

it('allows WFO check-in without geofence rejection when validasi_lokasi is disabled while recording mode and metadata', function (): void {
    $ctx = createLocationTestContext();

    $this->actingAs($ctx['admin'])
        ->put(route('admin.penempatan-pkl.location.update', $ctx['penempatanA']), [
            'mode_kerja' => 'WFO',
            'validasi_lokasi' => '0',
            'reason' => 'Kendala sinyal GPS sementara di area pabrik',
        ])
        ->assertRedirect(route('admin.penempatan-pkl.location.edit', $ctx['penempatanA']));

    // Placement B stays independent (still WFO + validasi_lokasi = true)
    expect($ctx['penempatanB']->fresh()->validasi_lokasi)->toBeTrue();

    // Check-in far away succeeds on Placement A because geofence is disabled
    $response = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), [
            'latitude' => -6.7000000,
            'longitude' => 110.7500000,
            'lokasi_masuk' => '-6.7000000, 110.7500000',
        ]);

    $response->assertRedirect(route('siswa.absensi.index'))
        ->assertSessionHas('success');

    $absensi = Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->first();
    expect($absensi)->not->toBeNull()
        ->and($absensi->mode_kerja)->toBe('WFO')
        ->and($absensi->validasi_lokasi_aktif)->toBeFalse()
        ->and($absensi->lokasi_valid)->toBeTrue()
        ->and($absensi->status)->toBe('hadir')
        ->and($absensi->ip_address)->not->toBeNull();
});

it('validates WFH attendance strictly against approved WFH coordinates (not DUDI) when geofence is active', function (): void {
    $ctx = createLocationTestContext();

    // Validation rejects enabling WFH + geofence without WFH coordinates
    $invalidReq = $this->actingAs($ctx['guruUserA'])
        ->put(route('guru.penempatan-pkl.location.update', $ctx['penempatanA']), [
            'mode_kerja' => 'WFH',
            'validasi_lokasi' => '1',
            'reason' => 'Coba WFH tanpa titik rumah',
        ]);
    $invalidReq->assertSessionHasErrors(['wfh_latitude', 'wfh_longitude']);

    // Guru Pembimbing sets approved WFH spot in Semarang (~55 km from DUDI Jepara)
    $this->actingAs($ctx['guruUserA'])
        ->put(route('guru.penempatan-pkl.location.update', $ctx['penempatanA']), [
            'mode_kerja' => 'WFH',
            'validasi_lokasi' => '1',
            'wfh_latitude' => -6.9666670,
            'wfh_longitude' => 110.4166640,
            'wfh_radius' => 150,
            'wfh_alamat' => 'Rumah Siswa, Semarang',
            'reason' => 'Siswa bertugas WFH proyek remote',
        ])
        ->assertRedirect(route('guru.penempatan-pkl.location.edit', $ctx['penempatanA']))
        ->assertSessionHas('success');

    // Submitting at DUDI coordinates (far from WFH coordinates) MUST be rejected!
    $atDudiCoords = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), [
            'latitude' => -6.5888900,
            'longitude' => 110.6684000,
        ]);
    $atDudiCoords->assertRedirect();
    expect(session('error'))->toContain('luar radius lokasi WFH')
        ->and(Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->count())->toBe(0);

    // Submitting within approved WFH radius succeeds and stores WFH mode + radius
    $atWfhCoords = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), [
            'latitude' => -6.9667000,
            'longitude' => 110.4167000,
        ]);
    $atWfhCoords->assertRedirect(route('siswa.absensi.index'))
        ->assertSessionHas('success');

    $absensi = Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->first();
    expect($absensi)->not->toBeNull()
        ->and($absensi->mode_kerja)->toBe('WFH')
        ->and($absensi->validasi_lokasi_aktif)->toBeTrue()
        ->and($absensi->lokasi_valid)->toBeTrue()
        ->and($absensi->radius)->toBe(150)
        ->and((float) $absensi->jarak)->toBeLessThanOrEqual(150.0);
});

it('allows WFH check-in without geofence when validasi_lokasi is disabled while still enforcing core placement, schedule, and duplicate protections', function (): void {
    $ctx = createLocationTestContext();

    // Admin enables WFH with validasi_lokasi = false (geofence disabled)
    $this->actingAs($ctx['admin'])
        ->put(route('admin.penempatan-pkl.location.update', $ctx['penempatanA']), [
            'mode_kerja' => 'WFH',
            'validasi_lokasi' => '0',
            'reason' => 'WFA/WFH darurat tanpa batasan koordinat',
        ])
        ->assertRedirect(route('admin.penempatan-pkl.location.edit', $ctx['penempatanA']));

    // Late check-in (08:30 vs jam_masuk 08:00) STILL enforces schedule lateness even with geofence off!
    Carbon::setTestNow('2026-11-25 08:30:00');
    $firstCheckIn = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), []);

    $firstCheckIn->assertRedirect(route('siswa.absensi.index'))
        ->assertSessionHas('success');

    $absensi = Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanA']->id)->first();
    expect($absensi)->not->toBeNull()
        ->and($absensi->mode_kerja)->toBe('WFH')
        ->and($absensi->validasi_lokasi_aktif)->toBeFalse()
        ->and($absensi->lokasi_valid)->toBeTrue()
        ->and($absensi->status)->toBe('terlambat');

    // Duplicate Check-In on same day is still blocked even when geofence is disabled
    $duplicate = $this->actingAs($ctx['siswaUserA'])
        ->post(route('siswa.absensi.check-in'), []);
    $duplicate->assertRedirect();
    expect(session('error'))->toContain('sudah melakukan Check In hari ini');

    // Core check: inactive or future placement cannot check in even on WFH + geofence disabled
    $ctx['penempatanB']->update([
        'mode_kerja' => 'WFH',
        'validasi_lokasi' => false,
        'tanggal_mulai' => '2026-12-01',
        'tanggal_selesai' => '2026-12-31',
    ]);
    $outOfSchedule = $this->actingAs($ctx['siswaUserB'])
        ->post(route('siswa.absensi.check-in'), []);
    $outOfSchedule->assertRedirect();
    expect(session('error'))->toContain('belum dimulai')
        ->and(Absensi::query()->where('penempatan_pkl_id', $ctx['penempatanB']->id)->count())->toBe(0);

    // Siswa page displays WFH & Geofence Nonaktif read-only badges
    $this->actingAs($ctx['siswaUserA'])
        ->get(route('siswa.absensi.index'))
        ->assertOk()
        ->assertSee('Mode: WFH')
        ->assertSee('Geofence: Nonaktif');
});

it('forbids Siswa, DUDI, and unrelated Guru from modifying location mode and logs full location audit history', function (): void {
    $ctx = createLocationTestContext();

    $payload = [
        'mode_kerja' => 'WFH',
        'validasi_lokasi' => '0',
        'reason' => 'Percobaan ubah ilegal',
    ];

    // Siswa forbidden on Admin & Guru routes
    $this->actingAs($ctx['siswaUserA'])
        ->put(route('admin.penempatan-pkl.location.update', $ctx['penempatanA']), $payload)
        ->assertStatus(403);
    $this->actingAs($ctx['siswaUserA'])
        ->put(route('guru.penempatan-pkl.location.update', $ctx['penempatanA']), $payload)
        ->assertStatus(403);

    // DUDI forbidden on Admin & Guru routes
    $this->actingAs($ctx['dudiUser'])
        ->put(route('admin.penempatan-pkl.location.update', $ctx['penempatanA']), $payload)
        ->assertStatus(403);
    $this->actingAs($ctx['dudiUser'])
        ->put(route('guru.penempatan-pkl.location.update', $ctx['penempatanA']), $payload)
        ->assertStatus(403);

    // Guru B cannot edit placement belonging to Guru A
    $this->actingAs($ctx['guruUserB'])
        ->get(route('guru.penempatan-pkl.location.edit', $ctx['penempatanA']))
        ->assertStatus(403);
    $this->actingAs($ctx['guruUserB'])
        ->put(route('guru.penempatan-pkl.location.update', $ctx['penempatanA']), $payload)
        ->assertStatus(403);

    // Zero illegal logs recorded
    expect(PenempatanPKLLocationLog::query()->count())->toBe(0);

    // Super Admin changes WFO -> WFH and logs full before/after audit trail
    $this->actingAs($ctx['admin'])
        ->put(route('admin.penempatan-pkl.location.update', $ctx['penempatanA']), [
            'mode_kerja' => 'WFH',
            'validasi_lokasi' => '1',
            'wfh_latitude' => -6.5500000,
            'wfh_longitude' => 110.6700000,
            'wfh_radius' => 120,
            'wfh_alamat' => 'Posko Kegiatan Sekolah',
            'reason' => 'Penugasan proyek sekolah selama 3 hari',
        ])
        ->assertRedirect(route('admin.penempatan-pkl.location.edit', $ctx['penempatanA']));

    /** @var PenempatanPKLLocationLog|null $log */
    $log = PenempatanPKLLocationLog::query()->first();
    expect($log)->not->toBeNull()
        ->and($log->penempatan_pkl_id)->toBe($ctx['penempatanA']->id)
        ->and($log->changed_by)->toBe($ctx['admin']->id)
        ->and($log->changed_by_name)->toBe($ctx['admin']->name)
        ->and($log->changed_by_role)->toBe(UserRole::SUPER_ADMIN->value)
        ->and($log->old_values['mode_kerja'])->toBe('WFO')
        ->and($log->old_values['validasi_lokasi'])->toBeTrue()
        ->and($log->new_values['mode_kerja'])->toBe('WFH')
        ->and($log->new_values['wfh_radius'])->toBe(120)
        ->and($log->reason)->toBe('Penugasan proyek sekolah selama 3 hari');

    // UI pages render location history & management forms properly for Admin and Guru A
    $this->actingAs($ctx['admin'])
        ->get(route('admin.penempatan-pkl.location.edit', $ctx['penempatanA']))
        ->assertOk()
        ->assertSee('Penugasan proyek sekolah selama 3 hari')
        ->assertSee('Admin Lokasi');

    $this->actingAs($ctx['guruUserA'])
        ->get(route('guru.penempatan-pkl.index'))
        ->assertOk()
        ->assertSee('Siswa A')
        ->assertSee('WFH');
});
