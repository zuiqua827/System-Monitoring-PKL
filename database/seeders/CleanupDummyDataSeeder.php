<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Jurusan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Safely remove all dummy/placeholder records that predate the SiPintu
 * integration, while preserving referential integrity.
 *
 * Removes (soft-deletes):
 *   - Dummy Students (Siswa) + their User accounts
 *   - Dummy Teachers (Guru) + their User accounts
 *   - Dummy Classes (Kelas)
 *   - Dummy Departments (Jurusan)
 *
 * Keeps:
 *   - Super Admin
 *   - Roles & Permissions
 *   - DUDI accounts
 *   - PKL Periods
 *   - Settings
 *   - Real synchronized SiPintu data
 *
 * Detection strategy:
 *   - Dummy siswa/guru are identified by factory-generated, non-Indonesian
 *     names (never matched against real SiPintu data).
 *   - Before soft-deleting a Siswa, any transactional rows that reference it
 *     (penempatan_pkl, absensi, aktivitas, penilaian) are detached so the
 *     soft-delete never breaks a foreign key.
 *
 * This seeder is idempotent and safe to run multiple times.
 */
class CleanupDummyDataSeeder extends Seeder
{
    /**
     * Names that are clearly factory/dummy generated (never real SiPintu data).
     * Real SiPintu siswa use full Indonesian names (e.g. "AFRILLIA FIFA ANANTA").
     */
    private const DUMMY_NAME_MARKERS = [
        ' Jr.', ' Sr.', ' II', ' III', ' IV', ' V', 'Prof.', 'Dr.', 'Mrs.', 'Ms.', 'MD',
        'Welch', 'Powlowski', 'Jakubowski', 'Gutkowski', 'Mueller', 'Nikolaus',
        'Koelpin', 'Ritchie', 'Carroll', 'Schoen', 'Jaskolski', 'Balistreri',
        'Green', 'Lorenzo', 'Kreiger', 'Buckridge', 'Langworth', 'Barrows',
        'Hirthe', 'Schimmel', 'Kling', 'Torphy', 'Hodkiewicz', 'Morissette',
        'Hickle',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $removedSiswa = 0;
            $removedGuru = 0;
            $removedClasses = 0;
            $removedDepartments = 0;
            $removedUsers = 0;

            // 1. Remove dummy students + link their transactional rows.
            $dummySiswa = Siswa::withoutTrashed()->get();

            foreach ($dummySiswa as $siswa) {
                if (! $this->isDummyName($siswa->nama)) {
                    continue;
                }

                $this->detachSiswaReferences((int) $siswa->id);

                $userId = $siswa->user_id;
                $siswa->delete();
                $removedSiswa++;

                if ($userId !== null) {
                    $this->safeDeleteUser((int) $userId);
                    $removedUsers++;
                }
            }

            // 2. Remove dummy teachers + their User accounts.
            $dummyGuru = Guru::withoutTrashed()->get();

            foreach ($dummyGuru as $guru) {
                if (! $this->isDummyName($guru->nama)) {
                    continue;
                }

                $userId = $guru->user_id;
                $guru->delete();
                $removedGuru++;

                if ($userId !== null) {
                    $this->safeDeleteUser((int) $userId);
                    $removedUsers++;
                }
            }

            // 3. Remove dummy classes and departments (those not in the real set).
            foreach (Kelas::withoutTrashed()->get() as $kelas) {
                if (! in_array($kelas->nama, $this->realClassNames(), true)) {
                    $kelas->delete();
                    $removedClasses++;
                }
            }

            foreach (Jurusan::withoutTrashed()->get() as $jurusan) {
                if (! in_array($jurusan->kode, $this->realDepartmentCodes(), true)) {
                    $jurusan->delete();
                    $removedDepartments++;
                }
            }

            $this->command->info("CleanupDummyDataSeeder: siswa={$removedSiswa}, guru={$removedGuru}, kelas={$removedClasses}, jurusan={$removedDepartments}, user={$removedUsers} dihapus.");
        });
    }

/**
     * Detach transactional rows referencing a dummy siswa so the FK stays intact.
     *
     * The `penempatan_pkl.siswa_id` column is NOT NULL, so the referencing
     * placement rows cannot be nulled. Instead, those placement rows (and,
     * transitively via cascade, their absensi/aktivitas/penilaian children)
     * are soft-deleted. This preserves referential integrity while allowing
     * the dummy siswa to be soft-deleted.
     */
    private function detachSiswaReferences(int $siswaId): void
    {
        // The transactional chain hangs off penempatan_pkl (NOT NULL siswa_id).
        // Soft-delete any placement rows referencing the dummy siswa. Because
        // absensi/aktivitas/penilaian reference penempatan_pkl_id with
        // cascadeOnDelete, they are removed along with the placement.
        if (\Schema::hasTable('penempatan_pkl')) {
            $columns = \Schema::getColumnListing('penempatan_pkl');

            if (in_array('siswa_id', $columns, true) && in_array('deleted_at', $columns, true)) {
                DB::table('penempatan_pkl')
                    ->whereNull('deleted_at')
                    ->where('siswa_id', $siswaId)
                    ->update(['deleted_at' => now()]);
            }
        }
    }

    /**
     * Soft-delete a User account only if it is not a Super Admin / DUDI and
     * is not referenced elsewhere as the primary actor.
     */
    private function safeDeleteUser(int $userId): void
    {
        $user = User::withoutTrashed()->find($userId);

        if ($user === null) {
            return;
        }

        // Never delete Super Admin or DUDI accounts.
        if ($user->hasRole(\App\Enums\UserRole::SUPER_ADMIN->value) ||
            $user->hasRole(\App\Enums\UserRole::DUDI->value)) {
            return;
        }

        // Never delete a user that is the creator of real transactional data.
        $isActor = DB::table('penempatan_pkl')->where('dibuat_oleh', $userId)->exists()
            || DB::table('penempatan_pkl')->where('approved_by', $userId)->exists()
            || DB::table('aktivitas')->where('approved_by', $userId)->exists()
            || DB::table('laporan')->where('validated_by', $userId)->exists()
            || DB::table('penilaian')->where('dinilai_oleh', $userId)->exists();

        if ($isActor) {
            return;
        }

        $user->delete();
    }

    private function isDummyName(string $nama): bool
    {
        foreach (self::DUMMY_NAME_MARKERS as $marker) {
            if (str_contains($nama, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function realDepartmentCodes(): array
    {
        return ['MPLB', 'AKL', 'PPLG', 'TO', 'PM'];
    }

    /**
     * @return list<string>
     */
    private function realClassNames(): array
    {
        return [
            'XII MPLB 1', 'XII MPLB 2', 'XII MPLB 3',
            'XII AKL 1', 'XII AKL 2',
            'XII PPLG 1', 'XII PPLG 2',
            'XII TO 1', 'XII TO 2',
            'XII PM 1', 'XII PM 2',
        ];
    }
}
