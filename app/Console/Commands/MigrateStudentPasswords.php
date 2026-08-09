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
 * Usage:
 *   php artisan sipintu:migrate-student-passwords
 *
 * Only Siswa-role users are updated. Guru, DUDI, and Super Admin accounts are
 * never touched. Idempotent — already-compliant accounts are skipped, so this
 * command is safe to run multiple times.
 */
class MigrateStudentPasswords extends Command
{
    protected $signature = 'sipintu:migrate-student-passwords';

    protected $description = 'Migrasi password seluruh akun siswa ke kebijakan default (password)';

    public function handle(SiswaServiceInterface $service): int
    {
        $this->info('Memulai migrasi password akun siswa...');

        $result = $service->migrateStudentPasswords();

        $this->newLine();
        $this->info('=== Student Password Policy Migration ===');
        $this->info("Total students found: {$result['total']}");
        $this->info("Total updated:        {$result['updated']}");
        $this->info("Total skipped:        {$result['skipped']}");
        $this->info("Total failed:         {$result['failed']}");
        $this->info('===========================================');

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}

