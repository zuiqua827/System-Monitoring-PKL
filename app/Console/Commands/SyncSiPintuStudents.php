<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\SiPintuApiException;
use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Console\Command;

/**
 * Synchronize real students from the SiPintu Gateway into the local database.
 *
 * Usage:
 *   php artisan sipintu:sync-students
 *
 * This command is safe to run on a schedule (e.g. daily/hourly).
 */
class SyncSiPintuStudents extends Command
{
    protected $signature = 'sipintu:sync-students';

    protected $description = 'Sinkronisasi data siswa dari gateway SiPintu';

    public function handle(SiPintuServiceInterface $service): int
    {
        $this->info('Memulai sinkronisasi data siswa dari SiPintu...');

        try {
            $stats = $service->syncStudents();
        } catch (SiPintuApiException $e) {
            $this->error("Sinkronisasi gagal: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Selesai. Dibuat: {$stats['created']}, Diperbarui: {$stats['updated']}, Dinonaktifkan: {$stats['deleted']}, Dilewati: {$stats['skipped']}");

        return self::SUCCESS;
    }
}
