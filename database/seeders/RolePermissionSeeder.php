<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'Guru' => [
            'dashboard.view',
            'dashboard.kpi',
            'jurusan.view',
            'siswa.view',
            'absensi.view',
            'aktivitas.view',
            'aktivitas.approve',
            'komentar.create',
            'laporan.view',
            'laporan.download',
            'penempatan.view',
            'penilaian.view',
        ],
        'DUDI' => [
            'dashboard.view',
            'siswa.view',
            'penempatan.view',
            'penilaian.view',
            'penilaian.create',
            'penilaian.update',
            'laporan.view',
        ],
        'Siswa' => [
            'dashboard.view',
            'profile.update',
            'absensi.checkin',
            'absensi.checkout',
            'aktivitas.create',
            'aktivitas.view',
            'laporan.upload',
            'laporan.view',
            'notifikasi.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        Role::firstOrCreate([
            'name' => UserRole::SUPER_ADMIN->value,
            'guard_name' => 'web',
        ])->syncPermissions($allPermissions);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
