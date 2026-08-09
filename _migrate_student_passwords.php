<?php

declare(strict_types=1);

use App\Services\Interfaces\SiswaServiceInterface;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * ONE-TIME student password policy migration.
 *
 * Delegates to SiswaService::migrateStudentPasswords() (Repository → Service
 * architecture) so the migration logic lives in exactly one place and is also
 * exposed through `php artisan sipintu:migrate-student-passwords`.
 *
 * Rules enforced (inside the service):
 *   - Only Siswa-role users are touched (never Guru, DUDI, Super Admin).
 *   - password             = Hash::make('password')
 *   - must_change_password = false
 *   - Each update runs inside a database transaction.
 *   - Idempotent: already-compliant accounts are skipped.
 *   - Never creates duplicates; never modifies NIS/email/profile/relations/
 *     roles/permissions.
 */

$service = $app->make(SiswaServiceInterface::class);
$result = $service->migrateStudentPasswords();

echo "=== Student Password Policy Migration ===\n";
echo "Total students found: {$result['total']}\n";
echo "Total updated:        {$result['updated']}\n";
echo "Total skipped:        {$result['skipped']}\n";
echo "Total failed:         {$result['failed']}\n";
echo "============================================\n";

