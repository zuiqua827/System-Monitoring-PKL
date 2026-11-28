<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Interfaces\SiswaServiceInterface;
use Illuminate\Console\Command;

/**
 * Migrate all existing Student (Siswa) accounts to the new default password
 * policy:
 *
 *   - password             = Hash::make('password')
 *   - must_change_password = false
 *
 * SAFE BY DEFAULT: runs in dry-run mode (no DB changes) unless --execute is passed.
 * Blocked in production environment unless --force is also supplied.
 *
 * Usage:
 *   php artisan sipintu:migrate-student-passwords              # dry-run preview (aman)
 *   php artisan sipintu:migrate-student-passwords --execute    # execute (non-production)
 *   php artisan sipintu:migrate-student-passwords --execute --force  # execute di production
 *
 * Only Siswa-role users are updated. Guru, DUDI, and Super Admin accounts are
 * never touched. Idempotent -- already-compliant accounts are skipped.
 */
class MigrateStudentPasswords extends Command
{
    protected $signature = 'sipintu:migrate-student-passwords
                            {--execute : Jalankan perubahan ke database (default: dry-run, tidak ada perubahan)}
                            {--force : Izinkan eksekusi di lingkungan production (wajib dipakai bersama --execute)}';

    protected $description = 'Migrasi password seluruh akun siswa ke kebijakan default (dry-run kecuali --execute dipakai)';

    public function handle(SiswaServiceInterface $service): int
    {
        $isDryRun = ! $this->option('execute');
        $isForce  = (bool) $this->option('force');

        // Block execution in production without explicit --force
        if (! $isDryRun && app()->isProduction() && ! $isForce) {
            $this->error('Perintah ini DIBLOKIR di production. Gunakan --force untuk memaksa eksekusi.');
            $this->warn('Contoh: php artisan sipintu:migrate-student-passwords --execute --force');

            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Tidak ada data yang akan diubah. Gunakan --execute untuk menjalankan perubahan.');
            $this->newLine();
        } else {
            $this->warn('[EXECUTE] Perubahan AKAN disimpan ke database!');
            if (! $this->confirm('Apakah Anda yakin ingin melanjutkan?', false)) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $this->info('Memulai migrasi password akun siswa...');

        $result = $service->migrateStudentPasswords($isDryRun);

        $this->newLine();
        $mode = $isDryRun ? 'DRY-RUN' : 'EKSEKUSI';
        $this->info("=== Student Password Policy Migration [{$mode}] ===");
        $this->info("Total students found:  {$result['total']}");
        $this->info("Would update/Updated:  {$result['updated']}");
        $this->info("Skipped (already OK):  {$result['skipped']}");
        $this->info("Failed:                {$result['failed']}");
        $this->info('================================================');

        if ($isDryRun && $result['updated'] > 0) {
            $this->newLine();
            $this->warn("Jalankan dengan --execute untuk benar-benar mengubah {$result['updated']} akun.");
        }

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
