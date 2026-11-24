<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

describe('SiPintu DUDI SSO & Mapping Feature Test Suite', function () {
    beforeEach(function () {
        Role::findOrCreate(UserRole::SUPER_ADMIN->value);
        Role::findOrCreate(UserRole::GURU->value);
        Role::findOrCreate(UserRole::SISWA->value);
        Role::findOrCreate(UserRole::DUDI->value);

        config([
            'services.sipintu.api_url' => 'https://sipintu.smkn1bangsri.sch.id',
            'services.sipintu.client_id' => 'test_client_id_dudi',
            'services.sipintu.client_secret' => 'test_client_secret_dudi',
        ]);
    });

    it('1. Only Super Admin can access DUDI mapping page', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $guru = User::factory()->create();
        $guru->assignRole(UserRole::GURU->value);

        // Guest redirected to login
        $this->get(route('admin.sipintu-dudi-mapping.index'))->assertRedirect(route('login'));

        // Guru forbidden (403)
        $this->actingAs($guru)->get(route('admin.sipintu-dudi-mapping.index'))->assertStatus(403);

        // Admin ok (200)
        $this->actingAs($admin)->get(route('admin.sipintu-dudi-mapping.index'))->assertOk()
            ->assertSee('Pemetaan Akun DUDI SiPintu');
    });

    it('2. Super Admin can map SIPINTU User ID to a local DUDI and SSO starts as disabled (false)', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $dudiUser = User::factory()->create(['email' => 'pt_maju@example.com']);
        $dudiUser->assignRole(UserRole::DUDI->value);

        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'nama_perusahaan' => 'PT Maju Bersama',
            'sipintu_user_id' => null,
            'sso_enabled' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sipintu-dudi-mapping.store', $dudi), [
            'sipintu_user_id' => 'sipintu_usr_9988',
            'sipintu_email' => 'perwakilan.dudi@sipintu.sch.id',
        ]);

        $response->assertRedirect(route('admin.sipintu-dudi-mapping.index'))
            ->assertSessionHas('success');

        $dudi->refresh();
        expect($dudi->sipintu_user_id)->toBe('sipintu_usr_9988')
            ->and($dudi->sipintu_email)->toBe('perwakilan.dudi@sipintu.sch.id')
            ->and($dudi->sso_enabled)->toBeFalse(); // Rule 2: sso_enabled MUST be false by default

        // Verify Audit Log recorded
        expect(AuditLog::query()->where('action', 'sipintu_dudi_mapping.updated')->where('record_id', $dudi->id)->exists())->toBeTrue();
    });

    it('3. Mapping rejects duplicate SIPINTU User ID across different DUDIs (Strict 1:1 rule)', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $dudiUser1 = User::factory()->create();
        $dudiUser1->assignRole(UserRole::DUDI->value);
        $dudi1 = Dudi::factory()->create([
            'user_id' => $dudiUser1->id,
            'sipintu_user_id' => 'duplicate_usr_111',
        ]);

        $dudiUser2 = User::factory()->create();
        $dudiUser2->assignRole(UserRole::DUDI->value);
        $dudi2 = Dudi::factory()->create([
            'user_id' => $dudiUser2->id,
            'sipintu_user_id' => null,
        ]);

        // Attempt to map dudi2 using dudi1's sipintu_user_id
        $response = $this->actingAs($admin)->post(route('admin.sipintu-dudi-mapping.store', $dudi2), [
            'sipintu_user_id' => 'duplicate_usr_111',
        ]);

        $response->assertSessionHasErrors(['sipintu_user_id']);
        $dudi2->refresh();
        expect($dudi2->sipintu_user_id)->toBeNull();
    });

    it('4. Super Admin can toggle SSO activation for a verified and active DUDI', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $dudiUser = User::factory()->create();
        $dudiUser->assignRole(UserRole::DUDI->value);
        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'sipintu_user_id' => 'usr_verified_777',
            'status_aktif' => true,
            'sso_enabled' => false,
        ]);

        // 1. Activate SSO
        $resActivate = $this->actingAs($admin)->post(route('admin.sipintu-dudi-mapping.toggle-sso', $dudi));
        $resActivate->assertRedirect(route('admin.sipintu-dudi-mapping.index'))->assertSessionHas('success');

        $dudi->refresh();
        expect($dudi->sso_enabled)->toBeTrue();

        // 2. Deactivate SSO
        $resDeactivate = $this->actingAs($admin)->post(route('admin.sipintu-dudi-mapping.toggle-sso', $dudi));
        $resDeactivate->assertRedirect(route('admin.sipintu-dudi-mapping.index'))->assertSessionHas('success');

        $dudi->refresh();
        expect($dudi->sso_enabled)->toBeFalse();
    });

    it('5. Cannot activate SSO if DUDI has no SIPINTU User ID or is inactive', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $dudiUser1 = User::factory()->create();
        $dudiUser1->assignRole(UserRole::DUDI->value);

        // Case A: Unmapped DUDI
        $unmappedDudi = Dudi::factory()->create([
            'user_id' => $dudiUser1->id,
            'sipintu_user_id' => null,
            'status_aktif' => true,
            'sso_enabled' => false,
        ]);

        $res1 = $this->actingAs($admin)->post(route('admin.sipintu-dudi-mapping.toggle-sso', $unmappedDudi));
        $res1->assertSessionHas('error');
        expect($unmappedDudi->fresh()->sso_enabled)->toBeFalse();

        $dudiUser2 = User::factory()->create();
        $dudiUser2->assignRole(UserRole::DUDI->value);

        // Case B: Inactive DUDI
        $inactiveDudi = Dudi::factory()->create([
            'user_id' => $dudiUser2->id,
            'sipintu_user_id' => 'usr_inactive_888',
            'status_aktif' => false,
            'sso_enabled' => false,
        ]);

        $res2 = $this->actingAs($admin)->post(route('admin.sipintu-dudi-mapping.toggle-sso', $inactiveDudi));
        $res2->assertSessionHas('error');
        expect($inactiveDudi->fresh()->sso_enabled)->toBeFalse();
    });

    it('6. Unmapping resets sipintu_user_id and disables sso without deleting DUDI record or PKL placements', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $dudiUser = User::factory()->create();
        $dudiUser->assignRole(UserRole::DUDI->value);
        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'sipintu_user_id' => 'usr_to_unmap_123',
            'sipintu_email' => 'unmap@sipintu.sch.id',
            'sso_enabled' => true,
        ]);

        // Place a mock PKL penempatan
        $penempatan = PenempatanPKL::factory()->create([
            'dudi_id' => $dudi->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.sipintu-dudi-mapping.destroy', $dudi));
        $response->assertRedirect(route('admin.sipintu-dudi-mapping.index'))->assertSessionHas('success');

        $dudi->refresh();
        expect($dudi->sipintu_user_id)->toBeNull()
            ->and($dudi->sipintu_email)->toBeNull()
            ->and($dudi->sso_enabled)->toBeFalse();

        // Rule 4 & 5: DUDI, User, and Penempatan PKL MUST NOT be deleted
        expect(Dudi::query()->find($dudi->id))->not->toBeNull()
            ->and(User::query()->find($dudiUser->id))->not->toBeNull()
            ->and(PenempatanPKL::query()->find($penempatan->id))->not->toBeNull();
    });

    it('7. DUDI SSO succeeds when sipintu_user_id is mapped, role is DUDI, status_aktif is true, and sso_enabled is true', function () {
        $dudiUser = User::factory()->create([
            'name' => 'PT Cipta Kreasi',
            'email' => 'cipta@kreasi.com',
        ]);
        $dudiUser->assignRole(UserRole::DUDI->value);

        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'nama_perusahaan' => 'PT Cipta Kreasi',
            'sipintu_user_id' => 'sso_dudi_valid_1001',
            'sipintu_email' => 'cipta@sipintu.smkn1bangsri.sch.id',
            'status_aktif' => true,
            'sso_enabled' => true,
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_dudi_ok',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 'sso_dudi_valid_1001',
                    'name' => 'Cipta Kreasi Akun',
                    'email' => 'cipta@sipintu.smkn1bangsri.sch.id',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_dudi_valid_999');

        $this->assertAuthenticatedAs($dudiUser);
        $response->assertRedirect('/dudi/dashboard');
        expect(session('auth_via_sso'))->toBeTrue();
    });

    it('8. DUDI SSO is rejected when sso_enabled is false', function () {
        $dudiUser = User::factory()->create();
        $dudiUser->assignRole(UserRole::DUDI->value);

        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'sipintu_user_id' => 'sso_dudi_disabled_1002',
            'status_aktif' => true,
            'sso_enabled' => false, // Not activated yet
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_disabled',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 'sso_dudi_disabled_1002',
                    'email' => 'disabled@dudi.com',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_disabled_sso');

        $this->assertGuest();
        $response->assertRedirect('/login')
            ->assertSessionHas('error')
            ->assertSessionHasErrors(['login']);
        expect(session('error'))->toContain('belum diaktifkan oleh Super Admin');
    });

    it('9. DUDI SSO is rejected when status_aktif is false (inactive)', function () {
        $dudiUser = User::factory()->create();
        $dudiUser->assignRole(UserRole::DUDI->value);

        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'sipintu_user_id' => 'sso_dudi_inactive_1003',
            'status_aktif' => false, // Inactive
            'sso_enabled' => true,
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_inactive',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 'sso_dudi_inactive_1003',
                    'email' => 'inactive@dudi.com',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_inactive_sso');

        $this->assertGuest();
        $response->assertRedirect('/login')
            ->assertSessionHas('error')
            ->assertSessionHasErrors(['login']);
        expect(session('error'))->toContain('sedang dinonaktifkan');
    });

    it('10. Prevents email-only fallback login for DUDI without verified sipintu_user_id mapping', function () {
        // DUDI exists locally with this email, but has NOT been mapped by Super Admin
        $dudiUser = User::factory()->create(['email' => 'unmapped_dudi@company.com']);
        $dudiUser->assignRole(UserRole::DUDI->value);
        $dudi = Dudi::factory()->create([
            'user_id' => $dudiUser->id,
            'sipintu_user_id' => null, // Unmapped!
            'sso_enabled' => false,
        ]);

        Http::fake([
            '*/oauth/token' => Http::response([
                'access_token' => 'mock_token_unmapped_email',
            ], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 'sipintu_random_id_555',
                    'email' => 'unmapped_dudi@company.com',
                ],
            ], 200),
        ]);

        $response = $this->get('/oauth/callback?code=code_email_only_fallback');

        $this->assertGuest();
        $response->assertRedirect('/login')
            ->assertSessionHas('error');
        expect(session('error'))->toContain('belum terhubung dengan data DUDI SIMONGAN atau belum diaktifkan');
    });

    it('11. Preserves Siswa SSO login without regression', function () {
        $siswaUser = User::factory()->create([
            'email' => 'siswa11@smkn1bangsri.sch.id',
            'name' => 'Siswa Test Regresi',
        ]);
        $siswaUser->assignRole(UserRole::SISWA->value);

        $siswa = Siswa::factory()->create([
            'user_id' => $siswaUser->id,
            'nis' => '112233',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'token_siswa'], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 'sipintu_siswa_id_1',
                    'nis' => '112233',
                    'email' => $siswaUser->email,
                ],
            ], 200),
        ]);

        $resSiswa = $this->get('/oauth/callback?code=code_siswa_test');
        $this->assertAuthenticatedAs($siswaUser);
        $resSiswa->assertRedirect('/siswa/dashboard');
    });

    it('12. Preserves Guru SSO login without regression', function () {
        $guruUser = User::factory()->create([
            'email' => 'guru12@smkn1bangsri.sch.id',
            'name' => 'Guru Test Regresi',
        ]);
        $guruUser->assignRole(UserRole::GURU->value);

        $guru = Guru::factory()->create([
            'user_id' => $guruUser->id,
            'nip' => '198501012010011002',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'token_guru'], 200),
            '*/api/v1/user' => Http::response([
                'data' => [
                    'id' => 'sipintu_guru_id_2',
                    'nip' => '198501012010011002',
                    'email' => $guruUser->email,
                ],
            ], 200),
        ]);

        $resGuru = $this->get('/oauth/callback?code=code_guru_test');
        $this->assertAuthenticatedAs($guruUser);
        $resGuru->assertRedirect('/guru/dashboard');
    });
});
