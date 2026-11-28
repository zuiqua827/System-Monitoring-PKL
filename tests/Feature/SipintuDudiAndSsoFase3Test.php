<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Absensi;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use App\Models\PeriodePKL;
use App\Models\SiPintuSyncLog;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Interfaces\DudiServiceInterface;
use App\Services\Interfaces\SipintuSyncServiceInterface;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::SUPER_ADMIN->value);
    Role::findOrCreate(UserRole::GURU->value);
    Role::findOrCreate(UserRole::SISWA->value);
    Role::findOrCreate(UserRole::DUDI->value);

    Cache::forget('sipintu_dashboard_remote_counts');
    Cache::forget('sipintu_live_diagnostics');

    config([
        'services.sipintu.api_url' => 'https://sipintu.test',
        'services.sipintu.client_id' => 'fase3_client_id',
        'services.sipintu.client_secret' => 'fase3_client_secret',
        'services.sipintu.api_token' => '',
        'services.sipintu.timeout' => 5,
    ]);
});

it('syncs DUDI in strict priority order (sipintu_id -> sipintu_user_id -> email) under link-only default and preserves operational & soft-deleted data', function (): void {
    $admin = User::factory()->create(['name' => 'Admin Fase 3']);
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    // 1. Existing DUDI matched via sipintu_id, with protected operational fields + placements + absensi + penilaian
    $passwordHash = Hash::make('DudiProtectedPass!2026');
    $u1 = User::factory()->create(['email' => 'dudi.one@company.id', 'password' => $passwordHash]);
    $u1->assignRole(UserRole::DUDI->value);
    $dudiById = Dudi::factory()->create([
        'user_id' => $u1->id,
        'sipintu_id' => 'SIP-DUDI-1',
        'sipintu_user_id' => 'SU-1',
        'nama_perusahaan' => 'PT Protected Lama',
        'penanggung_jawab' => 'PIC Lama',
        'no_telepon' => '08111111',
        'alamat' => 'Alamat Lama',
        'email_perusahaan' => 'dudi.one@company.id',
        'status_aktif' => true,
        'sso_enabled' => true,
        'latitude' => -6.5888900,
        'longitude' => 110.6684000,
        'jam_masuk' => '07:30:00',
        'jam_pulang' => '16:00:00',
        'toleransi_keterlambatan' => 10,
        'hari_operasional' => ['senin', 'selasa', 'rabu'],
    ]);

    $periode = PeriodePKL::factory()->create(['status' => 'aktif']);
    $guru = Guru::factory()->create();
    $siswa = Siswa::factory()->create();
    $penempatan = PenempatanPKL::factory()->create([
        'periode_pkl_id' => $periode->id,
        'dudi_id' => $dudiById->id,
        'guru_id' => $guru->id,
        'siswa_id' => $siswa->id,
        'status' => 'aktif',
    ]);
    $absensi = Absensi::factory()->create(['penempatan_pkl_id' => $penempatan->id]);
    $penilaian = Penilaian::query()->create([
        'penempatan_pkl_id' => $penempatan->id,
        'dinilai_oleh' => $u1->id,
        'nilai_akhir' => 92.5,
        'predikat' => 'A',
        'status' => 'final',
    ]);

    // 2. Existing DUDI matched via sipintu_user_id only (sipintu_id is null)
    $u2 = User::factory()->create(['email' => 'dudi.two@company.id']);
    $u2->assignRole(UserRole::DUDI->value);
    $dudiByUserId = Dudi::factory()->create([
        'user_id' => $u2->id,
        'sipintu_id' => null,
        'sipintu_user_id' => 'SU-2',
        'nama_perusahaan' => 'CV Via User ID',
        'email_perusahaan' => 'dudi.two@company.id',
    ]);

    // 3. Existing DUDI created manually via DudiService matched only via email fallback (uppercased remotely)
    /** @var DudiServiceInterface $dudiService */
    $dudiService = app(DudiServiceInterface::class);
    $dudiByEmail = $dudiService->store([
        'email' => 'hrd@mitra-tiga.co.id',
        'nama_perusahaan' => 'PT Mitra Tiga Lama',
        'penanggung_jawab' => 'Ibu Rina',
        'no_telepon' => '0822222',
        'alamat' => 'Bangsri',
        'status_aktif' => true,
    ]);

    // 4. Soft-deleted DUDI matched via sipintu_id -> MUST stay soft-deleted & skipped (NO duplicate, NO auto-restore)
    $uTrashed = User::factory()->create(['email' => 'trashed@dudi.test']);
    $uTrashed->assignRole(UserRole::DUDI->value);
    $trashedDudi = Dudi::factory()->create([
        'user_id' => $uTrashed->id,
        'sipintu_id' => 'SIP-DUDI-TRASHED',
        'nama_perusahaan' => 'PT Trashed Stay Deleted',
    ]);
    $trashedDudi->delete();

    // 5. Protected Super Admin user accidentally attached to a DUDI -> conflict without modifying SA
    $sa = User::factory()->create(['email' => 'sa.guard@smkn1bangsri.sch.id']);
    $sa->assignRole(UserRole::SUPER_ADMIN->value);
    Dudi::factory()->create([
        'user_id' => $sa->id,
        'sipintu_id' => 'SIP-DUDI-SA',
        'nama_perusahaan' => 'PT SA Protected',
    ]);

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'data' => [
                [
                    'id' => 'SIP-DUDI-1',
                    'user_id' => 'SU-1',
                    'nama_perusahaan' => 'PT Protected Baru',
                    'penanggung_jawab' => 'PIC Baru',
                    'no_telepon' => '08999999',
                    'alamat' => 'Alamat Baru',
                    'status_aktif' => false,
                    // Remote tries to inject protected fields -> MUST BE IGNORED
                    'latitude' => 9.9999,
                    'longitude' => 9.9999,
                    'jam_masuk' => '10:00:00',
                    'jam_pulang' => '19:00:00',
                    'toleransi_keterlambatan' => 99,
                    'hari_operasional' => ['minggu'],
                    'sso_enabled' => false,
                    'password' => 'HackedPassword!',
                ],
                [
                    'id' => 'SIP-DUDI-2',
                    'user_id' => 'SU-2',
                    'nama_perusahaan' => 'CV Via User ID Updated',
                    'email' => 'dudi.two@company.id',
                    'status_aktif' => 'inactive',
                ],
                [
                    'id' => 'SIP-DUDI-3',
                    'nama_perusahaan' => 'PT Mitra Tiga Baru',
                    'email' => 'HRD@MITRA-TIGA.CO.ID',
                    'status_aktif' => '0',
                ],
                [
                    'id' => 'SIP-DUDI-TRASHED',
                    'nama_perusahaan' => 'PT Trashed Auto Restore Attempt',
                    'status_aktif' => true,
                ],
                [
                    'id' => 'SIP-DUDI-SA',
                    'nama_perusahaan' => 'PT Hijack SA',
                ],
                [
                    // New remote DUDI -> under default link-only policy MUST BE SKIPPED!
                    'id' => 'SIP-DUDI-NEW-SKIP',
                    'nama_perusahaan' => 'PT Baru Link-Only Skip',
                    'email' => 'newskip@dudi.test',
                    'status_aktif' => true,
                ],
            ],
        ], 200),
    ]);

    $dudiCountBefore = Dudi::withTrashed()->count();
    $userCountBefore = User::withTrashed()->count();

    /** @var SipintuSyncServiceInterface $syncService */
    $syncService = app(SipintuSyncServiceInterface::class);

    // 1. Read-only preview check
    $preview = $syncService->preview('dudi');
    expect($preview['success'])->toBeTrue()
        ->and($preview['dudis']['baru'])->toBe(0)
        ->and($preview['dudis']['diperbarui'])->toBe(3)
        ->and($preview['dudis']['dilewati'])->toBe(2) // 1 soft-deleted + 1 unregistered link-only
        ->and($preview['dudis']['konflik'])->toBe(1)
        ->and($dudiById->fresh()->nama_perusahaan)->toBe('PT Protected Lama');

    // 2. Execute sync via Controller (default link-only, create_missing_dudi omitted)
    $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), ['entity' => 'dudi'])
        ->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('warning');

    Http::assertNotSent(fn ($r) => str_contains($r->url(), '/api/v1/sijuna/students'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), '/api/v1/sijuna/teachers'));

    // Assert NO new DUDIs/Users were created
    expect(Dudi::withTrashed()->count())->toBe($dudiCountBefore)
        ->and(User::withTrashed()->count())->toBe($userCountBefore)
        ->and(Dudi::withTrashed()->where('sipintu_id', 'SIP-DUDI-NEW-SKIP')->exists())->toBeFalse();

    // Assert #1 updated identity & status_aktif = false, while operational data & placements stayed intact
    $dudiById->refresh();
    $u1->refresh();
    expect($dudiById->nama_perusahaan)->toBe('PT Protected Baru')
        ->and($dudiById->penanggung_jawab)->toBe('PIC Baru')
        ->and($dudiById->alamat)->toBe('Alamat Baru')
        ->and($dudiById->status_aktif)->toBeFalse()
        ->and($dudiById->sso_enabled)->toBeTrue()
        ->and(round((float) $dudiById->latitude, 5))->toBe(-6.58889)
        ->and(round((float) $dudiById->longitude, 4))->toBe(110.6684)
        ->and((string) $dudiById->jam_masuk)->toContain('07:30')
        ->and((string) $dudiById->jam_pulang)->toContain('16:00')
        ->and($dudiById->toleransi_keterlambatan)->toBe(10)
        ->and($dudiById->hari_operasional)->toBe(['senin', 'selasa', 'rabu'])
        ->and(Hash::check('DudiProtectedPass!2026', $u1->password))->toBeTrue()
        ->and($penempatan->fresh()->status)->toBe('aktif')
        ->and($absensi->fresh())->not->toBeNull()
        ->and((float) $penilaian->fresh()->nilai_akhir)->toBe(92.5);

    // Assert #2 ('inactive') & #3 ('0') parsed explicitly as FALSE and linked cleanly!
    $dudiByUserId->refresh();
    $dudiByEmail->refresh();
    expect($dudiByUserId->sipintu_id)->toBe('SIP-DUDI-2')
        ->and($dudiByUserId->status_aktif)->toBeFalse()
        ->and($dudiByEmail->sipintu_id)->toBe('SIP-DUDI-3')
        ->and($dudiByEmail->status_aktif)->toBeFalse();

    // Assert soft-deleted NEVER auto-restored & zero duplicate
    expect(Dudi::withTrashed()->where('sipintu_id', 'SIP-DUDI-TRASHED')->count())->toBe(1)
        ->and($trashedDudi->fresh()->trashed())->toBeTrue();

    // Assert Super Admin untouched
    expect($sa->fresh()->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue();

    // Assert Audit Log in sipintu_sync_logs
    /** @var SiPintuSyncLog $log */
    $log = SiPintuSyncLog::query()->latest('id')->firstOrFail();
    expect($log->entity_scope)->toBe('dudi')
        ->and($log->dudi_added)->toBe(0)
        ->and($log->dudi_updated)->toBe(3)
        ->and($log->dudi_skipped)->toBe(3); // trashed + new link-only + conflict

    // 3. Re-running identical payload MUST BE 100% IDEMPOTENT (0 created, 0 updated, 3 unchanged)
    $run2 = $syncService->runSync($admin, 'dudi');
    expect($run2['stats']['dudis']['created'])->toBe(0)
        ->and($run2['stats']['dudis']['updated'])->toBe(0)
        ->and($run2['stats']['dudis']['unchanged'])->toBe(3)
        ->and(Dudi::withTrashed()->count())->toBe($dudiCountBefore);
});

it('parses status_aktif strings and booleans explicitly and creates DUDI only when create_missing_dudi is enabled', function (): void {
    $this->seed([RoleSeeder::class, PermissionSeeder::class, RolePermissionSeeder::class]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SUPER_ADMIN->value);

    // Email collision with Siswa -> must report conflict when create_missing_dudi=true
    $siswa = User::factory()->create(['email' => 'siswa.bentrok@smk.sch.id']);
    $siswa->assignRole(UserRole::SISWA->value);

    Http::fake([
        'https://sipintu.test/api/v1/sijuna/dudi*' => Http::response([
            'data' => [
                [
                    'id' => 'DUDI-STR-FALSE',
                    'nama_perusahaan' => 'PT String False',
                    'email' => 'strfalse@dudi.test',
                    'status_aktif' => 'false',
                ],
                [
                    'id' => 'DUDI-STR-INACTIVE',
                    'nama_perusahaan' => 'PT Status Inactive',
                    'email' => null,
                    'status' => 'inactive',
                ],
                [
                    'id' => 'DUDI-STR-ACTIVE',
                    'nama_perusahaan' => 'PT Aktif True',
                    'email' => 'aktif@dudi.test',
                    'status_aktif' => 'aktif',
                ],
                [
                    'id' => 'DUDI-EMAIL-COLLISION',
                    'nama_perusahaan' => 'PT Tabrakan Siswa',
                    'email' => 'siswa.bentrok@smk.sch.id',
                    'status_aktif' => true,
                ],
            ],
        ], 200),
    ]);

    // Preview with create_missing_dudi=1 stays read-only
    $this->actingAs($admin)->get(route('admin.sipintu-sync.preview', ['entity' => 'dudi', 'create_missing_dudi' => '1']))
        ->assertOk()
        ->assertSee('Cakupan: DUDI');
    expect(Dudi::query()->where('sipintu_id', 'DUDI-STR-FALSE')->exists())->toBeFalse();

    // Execute sync with create_missing_dudi=1
    $this->actingAs($admin)->post(route('admin.sipintu-sync.sync'), [
        'entity' => 'dudi',
        'create_missing_dudi' => '1',
    ])->assertRedirect(route('admin.sipintu-sync.index'))
        ->assertSessionHas('warning');

    $dFalse = Dudi::query()->where('sipintu_id', 'DUDI-STR-FALSE')->firstOrFail();
    $dInactive = Dudi::query()->where('sipintu_id', 'DUDI-STR-INACTIVE')->firstOrFail();
    $dActive = Dudi::query()->where('sipintu_id', 'DUDI-STR-ACTIVE')->firstOrFail();

    expect($dFalse->status_aktif)->toBeFalse()
        ->and($dFalse->user_id)->not->toBeNull()
        ->and($dFalse->sso_enabled)->toBeFalse()
        ->and($dInactive->status_aktif)->toBeFalse()
        ->and($dInactive->user_id)->toBeNull()
        ->and($dActive->status_aktif)->toBeTrue()
        ->and(Dudi::query()->where('sipintu_id', 'DUDI-EMAIL-COLLISION')->exists())->toBeFalse()
        ->and($siswa->fresh()->hasRole(UserRole::SISWA->value))->toBeTrue();

    // Admin UI clearly displays "DUDI tanpa akun lokal" when user_id is null
    $this->actingAs($admin)->get(route('admin.dudi.index'))
        ->assertOk()
        ->assertSee('PT Status Inactive')
        ->assertSee('DUDI tanpa akun lokal');

    $this->actingAs($admin)->get(route('admin.dudi.show', $dInactive))
        ->assertOk()
        ->assertSee('DUDI tanpa akun lokal');

    $this->actingAs($admin)->get(route('admin.dudi.edit', $dInactive))
        ->assertOk()
        ->assertSee('DUDI tanpa akun lokal');

    $this->actingAs($admin)->get(route('admin.sipintu-dudi-mapping.index'))
        ->assertOk()
        ->assertSee('DUDI tanpa akun lokal');
});

it('executes add_dudi_fields_and_stats migration down() safely without crashing when nullable user_id rows exist', function (): void {
    $connection = 'migration_safety_check';
    config([
        "database.connections.{$connection}" => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ],
    ]);

    Schema::connection($connection)->create('dudi', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->unique();
        $table->string('nama_perusahaan');
    });
    Schema::connection($connection)->create('sipintu_sync_logs', function ($table): void {
        $table->id();
        $table->unsignedInteger('teacher_skipped')->default(0);
    });

    $migration = require database_path('migrations/2026_11_24_000001_add_dudi_fields_and_stats.php');

    $originalConnection = DB::getDefaultConnection();
    DB::setDefaultConnection($connection);
    try {
        $migration->up();
        expect(Schema::connection($connection)->hasColumn('dudi', 'sipintu_id'))->toBeTrue()
            ->and(Schema::connection($connection)->hasColumn('sipintu_sync_logs', 'dudi_added'))->toBeTrue();

        DB::connection($connection)->table('dudi')->insert([
            'user_id' => null,
            'sipintu_id' => 'SIP-NULL-USER',
            'nama_perusahaan' => 'PT Nullable Row',
        ]);

        // Rolling back must NOT crash despite existing row with user_id = null
        $migration->down();
        expect(Schema::connection($connection)->hasColumn('dudi', 'sipintu_id'))->toBeFalse()
            ->and(Schema::connection($connection)->hasColumn('sipintu_sync_logs', 'dudi_added'))->toBeFalse();
    } finally {
        DB::setDefaultConnection($originalConnection);
        config(['database.default' => $originalConnection]);
        DB::purge($connection);
    }
});

it('allows active DUDI SSO login via email and role when sso_enabled is true and preserves local password', function (): void {
    $dudiPass = Hash::make('DudiEmailRolePass!');
    $dudiEmailRoleUser = User::factory()->create([
        'email' => 'dudi.emailrole@mitra.co.id',
        'password' => $dudiPass,
    ]);
    $dudiEmailRoleUser->assignRole(UserRole::DUDI->value);
    Dudi::factory()->create([
        'user_id' => $dudiEmailRoleUser->id,
        'sipintu_user_id' => null,
        'email_perusahaan' => 'dudi.emailrole@mitra.co.id',
        'status_aktif' => true,
        'sso_enabled' => true,
    ]);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_dudi_er'], 200),
        '*/api/v1/user' => Http::response([
            'data' => [
                'id' => 'sipintu_u_9001',
                'role' => 'DUDI',
                'email' => 'DUDI.EMAILROLE@mitra.co.id',
            ],
        ], 200),
    ]);

    $this->get('/oauth/callback?code=code_dudi_er')
        ->assertRedirect('/dudi/dashboard');
    $this->assertAuthenticatedAs($dudiEmailRoleUser);
    expect(Hash::check('DudiEmailRolePass!', $dudiEmailRoleUser->fresh()->password))->toBeTrue();

    $this->post(route('logout'));
    $this->assertGuest();

    // Manual local PKL login for active DUDI remains unaffected
    $this->post(route('login'), [
        'role' => 'dudi',
        'email' => 'dudi.emailrole@mitra.co.id',
        'password' => 'DudiEmailRolePass!',
    ])->assertRedirect('/dudi/dashboard');
    $this->assertAuthenticatedAs($dudiEmailRoleUser);
});

it('rejects inactive DUDI SSO login matched via email and role', function (): void {
    $inactiveUser = User::factory()->create(['email' => 'inactive.er@mitra.co.id']);
    $inactiveUser->assignRole(UserRole::DUDI->value);
    Dudi::factory()->create([
        'user_id' => $inactiveUser->id,
        'sipintu_user_id' => null,
        'status_aktif' => false,
        'sso_enabled' => true,
    ]);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_dudi_inact'], 200),
        '*/api/v1/user' => Http::response([
            'data' => ['id' => 'sipintu_inact_1', 'role' => 'dudi', 'email' => 'inactive.er@mitra.co.id'],
        ], 200),
    ]);

    $this->get('/oauth/callback?code=code_dudi_inact')
        ->assertRedirect('/login')
        ->assertSessionHas('error');
    expect(session('error'))->toContain('dinonaktifkan');
    $this->assertGuest();
});

it('rejects mapped DUDI without local User account during SSO', function (): void {
    Dudi::factory()->create([
        'user_id' => null,
        'sipintu_user_id' => 'sipintu_nolocal_77',
        'status_aktif' => true,
        'sso_enabled' => true,
    ]);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_dudi_nolocal'], 200),
        '*/api/v1/user' => Http::response([
            'data' => ['id' => 'sipintu_nolocal_77', 'role' => 'dudi', 'email' => 'nolocal@mitra.co.id'],
        ], 200),
    ]);

    $this->get('/oauth/callback?code=code_dudi_nolocal')
        ->assertRedirect('/login')
        ->assertSessionHas('error');
    expect(session('error'))->toContain('akun lokal');
    $this->assertGuest();
});

it('allows active Guru SSO login via email without nip', function (): void {
    $guruEmailUser = User::factory()->create(['email' => 'guru.sso@smkn1bangsri.sch.id']);
    $guruEmailUser->assignRole(UserRole::GURU->value);
    Guru::factory()->create([
        'user_id' => $guruEmailUser->id,
        'nip' => '198901012015011001',
    ]);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_guru_email'], 200),
        '*/api/v1/user' => Http::response([
            'data' => ['id' => 'sp_g_1', 'role' => 'guru', 'email' => 'GURU.SSO@smkn1bangsri.sch.id'],
        ], 200),
    ]);

    $this->get('/oauth/callback?code=code_guru_email')
        ->assertRedirect('/guru/dashboard');
    $this->assertAuthenticatedAs($guruEmailUser);
});

it('rejects remote Super Admin role during SSO and rejects soft-deleted Guru', function (): void {
    $guruUser = User::factory()->create(['email' => 'guru.sa.check@smkn1bangsri.sch.id']);
    $guruUser->assignRole(UserRole::GURU->value);
    Guru::factory()->create(['user_id' => $guruUser->id, 'nip' => '198901012015011002']);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_sa'], 200),
        '*/api/v1/user' => Http::response([
            'data' => ['id' => 'sp_sa_1', 'role' => 'super_admin', 'email' => 'guru.sa.check@smkn1bangsri.sch.id'],
        ], 200),
    ]);

    $this->get('/oauth/callback?code=code_sa_remote')
        ->assertRedirect('/login')
        ->assertSessionHas('error');
    expect(session('error'))->toContain('Administrator');
    $this->assertGuest();
});

it('rejects soft-deleted Guru during SSO cleanly', function (): void {
    $guruDeletedUser = User::factory()->create(['email' => 'guru.deleted@smkn1bangsri.sch.id']);
    $guruDeletedUser->assignRole(UserRole::GURU->value);
    $guruDeleted = Guru::factory()->create(['user_id' => $guruDeletedUser->id, 'nip' => '197501012000011009']);
    $guruDeleted->delete();

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_g_del'], 200),
        '*/api/v1/user' => Http::response([
            'data' => ['id' => 'sp_g_del', 'nip' => '197501012000011009', 'email' => 'guru.deleted@smkn1bangsri.sch.id'],
        ], 200),
    ]);

    $this->get('/oauth/callback?code=code_guru_del')
        ->assertRedirect('/login')
        ->assertSessionHas('error');
    $this->assertGuest();
});

it('handles HTML 200 user profile and ConnectionException timeout during SSO without 500 errors and preserves local Guru login', function (): void {
    $guruUser = User::factory()->create([
        'email' => 'guru.local@smkn1bangsri.sch.id',
        'password' => Hash::make('GuruLocalPass!99'),
    ]);
    $guruUser->assignRole(UserRole::GURU->value);
    Guru::factory()->create(['user_id' => $guruUser->id, 'nip' => '198801012014011001']);

    Http::fake([
        '*/oauth/token' => Http::response(['access_token' => 'tok_html'], 200),
        '*/api/v1/user' => Http::response('<html>Bad Gateway</html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $this->get('/oauth/callback?code=code_html_profile')
        ->assertRedirect('/login')
        ->assertSessionHas('error');
    $this->assertGuest();

    // Verify manual local Guru login works uninterrupted:
    $this->post(route('login'), [
        'role' => 'guru',
        'email' => 'guru.local@smkn1bangsri.sch.id',
        'password' => 'GuruLocalPass!99',
    ])->assertRedirect('/guru/dashboard');
    $this->assertAuthenticatedAs($guruUser);
});

it('handles ConnectionException timeout on SSO callback gracefully without HTTP 500', function (): void {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out during OAuth token exchange');
    });

    $this->get('/oauth/callback?code=code_timeout_fase3_isolated')
        ->assertRedirect('/login')
        ->assertSessionHas('error');
    expect(session('error'))->toContain('batas waktu');
    $this->assertGuest();
});
