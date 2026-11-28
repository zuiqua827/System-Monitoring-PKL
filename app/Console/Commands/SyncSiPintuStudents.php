<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SiPintuApiException;
use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Console\Command;

/**
 * Synchronize students, teachers, and/or DUDI from the SiPintu Gateway.
 *
 * Usage:
 *   php artisan sipintu:sync                  # sync siswa + guru (default)
 *   php artisan sipintu:sync --scope=all      # sync siswa + guru + dudi
 *   php artisan sipintu:sync --scope=dudi     # hanya DUDI
 *   php artisan sipintu:sync --students-only  # hanya siswa (legacy flag)
 *   php artisan sipintu:sync --teachers-only  # hanya guru (legacy flag)
 */
class SyncSiPintuStudents extends Command
{
    protected $signature = 'sipintu:sync
                            {--scope= : Lingkup entitas: all|siswa|guru|dudi (default: siswa+guru)}
                            {--students-only : Hanya sinkronkan data siswa}
                            {--teachers-only : Hanya sinkronkan data guru}
                            {--create-missing-dudi : Buat akun DUDI baru jika belum ada di lokal}';

    protected $description = 'Sinkronisasi data siswa, guru, dan DUDI dari gateway SiPintu';

    public function handle(SiPintuServiceInterface $service): int
    {
        $scope = strtolower(trim((string) $this->option('scope')));
        $studentsOnly = (bool) $this->option('students-only');
        $teachersOnly = (bool) $this->option('teachers-only');
        $createMissingDudi = (bool) $this->option('create-missing-dudi');

        if ($scope !== '' && ! in_array($scope, ['all', 'siswa', 'guru', 'dudi'], true)) {
            $this->error("Scope tidak valid: '{$scope}'. Gunakan all, siswa, guru, atau dudi.");

            return self::FAILURE;
        }

        $runSiswa = $scope === 'all' || $scope === 'siswa' || ($scope === '' && ! $teachersOnly);
        $runGuru = $scope === 'all' || $scope === 'guru' || ($scope === '' && ! $studentsOnly);
        $runDudi = $scope === 'all' || $scope === 'dudi';

        $this->info('Memulai sinkronisasi data dari SiPintu...');

        try {
            if ($runSiswa) {
                $this->info('Menyinkronkan data siswa...');
                $studentStats = $service->syncStudents();
                $this->info("Siswa selesai. Dibuat: {$studentStats['created']}, Diperbarui: {$studentStats['updated']}, Dinonaktifkan: {$studentStats['deleted']}, Dilewati: {$studentStats['skipped']}");
            }

            if ($runGuru) {
                $this->info('Menyinkronkan data guru...');
                $teacherStats = $service->syncTeachers();
                $this->info("Guru selesai. Dibuat: {$teacherStats['created']}, Diperbarui: {$teacherStats['updated']}, Dinonaktifkan: {$teacherStats['deleted']}, Dilewati: {$teacherStats['skipped']}");
            }

            if ($runDudi) {
                $this->info('Menyinkronkan data DUDI...');
                $dudiStats = $service->syncDudi(allowCreate: $createMissingDudi);
                $this->info("DUDI selesai. Dibuat: {$dudiStats['created']}, Diperbarui: {$dudiStats['updated']}, Dinonaktifkan: {$dudiStats['deleted']}, Dilewati: {$dudiStats['skipped']}");
            }
        } catch (SiPintuApiException $e) {
            $this->error("Sinkronisasi gagal: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Sinkronisasi selesai.');

        return self::SUCCESS;
    }
}
