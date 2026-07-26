<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'user.view',
        'user.create',
        'user.update',
        'user.delete',
        'guru.view',
        'guru.create',
        'guru.update',
        'guru.delete',
        'dudi.view',
        'dudi.create',
        'dudi.update',
        'dudi.delete',
        'siswa.view',
        'siswa.create',
        'siswa.update',
        'siswa.delete',
        'jurusan.view',
        'jurusan.create',
        'jurusan.update',
        'jurusan.delete',
        'jurusan.restore',
        'jurusan.forceDelete',
        'kelas.view',
        'kelas.create',
        'kelas.update',
        'kelas.delete',
        'kelas.restore',
        'kelas.forceDelete',
        'periode.view',
        'periode.create',
        'periode.update',
        'periode.delete',
        'periode.restore',
        'periode.forceDelete',
        'penempatan.view',
        'penempatan.create',
        'penempatan.update',
        'penempatan.delete',
        'absensi.view',
        'absensi.checkin',
        'absensi.checkout',
        'absensi.verify',
        'aktivitas.view',
        'aktivitas.create',
        'aktivitas.update',
        'aktivitas.approve',
        'komentar.view',
        'komentar.create',
        'laporan.view',
        'laporan.upload',
        'laporan.download',
        'laporan.validate',
        'penilaian.view',
        'penilaian.create',
        'penilaian.update',
        'dashboard.view',
        'dashboard.kpi',
        'settings.view',
        'settings.update',
        'notifikasi.view',
        'audit.view',
        'profile.update',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
