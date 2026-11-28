<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPermissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_super_admin_can_access_admin_siswa_without_permission_error(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $response = $this->actingAs($admin)->get('/admin/siswa');

        $response->assertOk();
    }

    public function test_super_admin_can_access_admin_guru_without_permission_error(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $response = $this->actingAs($admin)->get('/admin/guru');

        $response->assertOk();
    }

    public function test_super_admin_has_siswa_view_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $this->assertTrue($admin->hasPermissionTo('siswa.view'));
        $this->assertTrue($admin->can('siswa.view'));
    }

    public function test_super_admin_has_guru_view_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SUPER_ADMIN->value);

        $this->assertTrue($admin->hasPermissionTo('guru.view'));
        $this->assertTrue($admin->can('guru.view'));
    }

    public function test_non_admin_cannot_access_admin_siswa(): void
    {
        $siswaUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $siswaUser->assignRole(UserRole::SISWA->value);

        $response = $this->actingAs($siswaUser)->get('/admin/siswa');

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_access_admin_guru(): void
    {
        $guruUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $guruUser->assignRole(UserRole::GURU->value);

        $response = $this->actingAs($guruUser)->get('/admin/guru');

        $response->assertForbidden();
    }

    public function test_all_permissions_used_in_policies_are_registered_in_database(): void
    {
        $requiredPolicyPermissions = [
            'siswa.view', 'siswa.create', 'siswa.update', 'siswa.delete', 'siswa.restore', 'siswa.forceDelete',
            'guru.view', 'guru.create', 'guru.update', 'guru.delete', 'guru.restore', 'guru.forceDelete',
            'dudi.view', 'dudi.create', 'dudi.update', 'dudi.delete', 'dudi.restore', 'dudi.forceDelete',
            'jurusan.view', 'jurusan.create', 'jurusan.update', 'jurusan.delete', 'jurusan.restore', 'jurusan.forceDelete',
            'kelas.view', 'kelas.create', 'kelas.update', 'kelas.delete', 'kelas.restore', 'kelas.forceDelete',
            'periode.view', 'periode.create', 'periode.update', 'periode.delete', 'periode.restore', 'periode.forceDelete',
            'penempatan.view', 'penempatan.create', 'penempatan.update', 'penempatan.delete', 'penempatan.restore', 'penempatan.forceDelete',
            'absensi.view', 'absensi.verify',
            'aktivitas.view', 'aktivitas.create', 'aktivitas.update', 'aktivitas.delete', 'aktivitas.restore', 'aktivitas.forceDelete',
            'penilaian.view', 'penilaian.create', 'penilaian.update', 'penilaian.delete', 'penilaian.restore', 'penilaian.forceDelete',
        ];

        $existingPermissions = Permission::where('guard_name', 'web')->pluck('name')->all();

        foreach ($requiredPolicyPermissions as $permission) {
            $this->assertContains(
                $permission,
                $existingPermissions,
                "Permission '{$permission}' is used in Policies but not registered in database."
            );
        }
    }

    public function test_all_roles_and_permissions_use_web_guard(): void
    {
        $invalidPermissionsCount = Permission::where('guard_name', '!=', 'web')->count();
        $this->assertSame(0, $invalidPermissionsCount, 'Found permissions with guard other than web');

        $invalidRolesCount = Role::where('guard_name', '!=', 'web')->count();
        $this->assertSame(0, $invalidRolesCount, 'Found roles with guard other than web');
    }
}
