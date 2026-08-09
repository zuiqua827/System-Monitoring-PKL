<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SiPintuApiException;
use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Console\Command;

/**
 * Synchronize real students and teachers from the SiPintu Gateway.
 *
 * Usage:
 *   php artisan sipintu:sync
 *
 * This command is safe to run on a schedule (e.g. daily/hourly).
 * Student data → Siswa module. Teacher data → Guru module.
 */
class SyncSiPintuStudents extends Command
{
    protected $signature = 'sipintu:sync {--students-only : Hanya sinkronkan data siswa} {--teachers-only : Hanya sinkronkan data guru}';

    protected $description = 'Sinkronisasi data siswa dan guru dari gateway SiPintu';

    public function handle(SiPintuServiceInterface $service): int
    {
        $studentsOnly = (bool) $this->option('students-only');
        $teachersOnly = (bool) $this->option('teachers-only');

        $this->info('Memulai sinkronisasi data dari SiPintu...');

        try {
            $studentStats = null;
            $teacherStats = null;

            if (! $teachersOnly) {
                $this->info('Menyinkronkan data siswa...');
                $studentStats = $service->syncStudents();
                $this->info("Siswa selesai. Dibuat: {$studentStats['created']}, Diperbarui: {$studentStats['updated']}, Dinonaktifkan: {$studentStats['deleted']}, Dilewati: {$studentStats['skipped']}");
            }

            if (! $studentsOnly) {
                $this->info('Menyinkronkan data guru...');
                $teacherStats = $service->syncTeachers();
                $this->info("Guru selesai. Dibuat: {$teacherStats['created']}, Diperbarui: {$teacherStats['updated']}, Dinonaktifkan: {$teacherStats['deleted']}, Dilewati: {$teacherStats['skipped']}");
            }
        } catch (SiPintuApiException $e) {
            $this->error("Sinkronisasi gagal: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info('Sinkronisasi selesai.');

        return self::SUCCESS;
    }
}
