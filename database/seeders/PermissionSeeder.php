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
        // User Management
        'user.view',
        'user.create',
        'user.update',
        'user.delete',
        'user.restore',
        'user.forceDelete',

        // Guru Management
        'guru.view',
        'guru.create',
        'guru.update',
        'guru.delete',
        'guru.restore',
        'guru.forceDelete',

        // DUDI Management
        'dudi.view',
        'dudi.create',
        'dudi.update',
        'dudi.delete',
        'dudi.restore',
        'dudi.forceDelete',

        // Siswa Management
        'siswa.view',
        'siswa.create',
        'siswa.update',
        'siswa.delete',
        'siswa.restore',
        'siswa.forceDelete',

        // Jurusan Management
        'jurusan.view',
        'jurusan.create',
        'jurusan.update',
        'jurusan.delete',
        'jurusan.restore',
        'jurusan.forceDelete',

        // Kelas Management
        'kelas.view',
        'kelas.create',
        'kelas.update',
        'kelas.delete',
        'kelas.restore',
        'kelas.forceDelete',

        // Periode PKL Management
        'periode.view',
        'periode.create',
        'periode.update',
        'periode.delete',
        'periode.restore',
        'periode.forceDelete',

        // Penempatan PKL Management
        'penempatan.view',
        'penempatan.create',
        'penempatan.update',
        'penempatan.delete',
        'penempatan.restore',
        'penempatan.forceDelete',

        // Absensi Management
        'absensi.view',
        'absensi.checkin',
        'absensi.checkout',
        'absensi.verify',

        // Aktivitas PKL Management
        'aktivitas.view',
        'aktivitas.create',
        'aktivitas.update',
        'aktivitas.delete',
        'aktivitas.restore',
        'aktivitas.forceDelete',
        'aktivitas.approve',

        // Komentar
        'komentar.view',
        'komentar.create',

        // Laporan PKL
        'laporan.view',
        'laporan.upload',
        'laporan.download',
        'laporan.validate',

        // Penilaian PKL
        'penilaian.view',
        'penilaian.create',
        'penilaian.update',
        'penilaian.delete',
        'penilaian.restore',
        'penilaian.forceDelete',

        // Dashboard & Analytics
        'dashboard.view',
        'dashboard.kpi',

        // Settings & System
        'settings.view',
        'settings.update',
        'notifikasi.view',
        'audit.view',
        'profile.update',

        // WhatsApp Gateway Integration
        'whatsapp.view',
        'whatsapp.update',

        // SiPintu Integration
        'sipintu.view',
        'sipintu.sync',
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
