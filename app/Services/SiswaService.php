<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SiswaServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * Service layer for Siswa business logic.
 *
 * Handles all business operations for Siswa module.
 * Controller should NOT contain business logic — it delegates to this service.
 *
 * Key difference from simple modules (Jurusan, Kelas, PeriodePKL):
 * Siswa has a belongsTo relationship with User.
 * Creating/updating/deleting Siswa also affects the associated User account.
 */
class SiswaService extends Service implements SiswaServiceInterface
{
    public function __construct(
        private readonly SiswaRepositoryInterface $siswaRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
        ?int $jurusanId = null,
        ?int $kelasId = null,
        ?string $status = null,
    ): LengthAwarePaginator {
        return $this->siswaRepository->search($keyword, $sortBy, $sortDirection, $perPage, $jurusanId, $kelasId, $status);
    }

/**
     * {@inheritDoc}
     */
    public function searchForSelect(string $search): array
    {
        $students = $this->siswaRepository->searchForSelect($search);

        return $students->map(fn (Siswa $siswa): array => [
            'id' => $siswa->id,
            'nama' => $siswa->nama,
            'nis' => $siswa->nis,
            'nisn' => $siswa->nisn,
            'kelas' => $siswa->kelas?->nama,
            'jurusan' => $siswa->kelas?->jurusan?->nama,
        ])->values()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Siswa
    {
        /** @var Siswa|null $siswa */
        $siswa = $this->siswaRepository->find($id);

        if ($siswa === null) {
            throw new ModelNotFoundException('Siswa tidak ditemukan.');
        }

        return $siswa;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Create User account with role "Siswa"
     * 2. Use tanggal_lahir as initial password (hashed)
     * 3. Create Siswa record linked to the new User
     * 4. All within a single database transaction
     */
    public function store(array $data): Siswa
    {
        /** @var Siswa $siswa */
        $siswa = $this->transaction(function () use ($data): Model {
            // 1. Create User account
            // Initial password = tanggal_lahir (hashed). Student is forced
            // to change it on first login (must_change_password = true).
            // Email is auto-generated from NIS — the admin never inputs it.
            $email = Siswa::generateEmail((string) $data['nis']);

            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $data['nama'],
                'email' => $email,
                'password' => bcrypt($data['tanggal_lahir']),
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            // 2. Assign Siswa role via Spatie
            $user->assignRole(UserRole::SISWA->value);

            // 3. Create Siswa record linked to the User
            return $this->siswaRepository->create([
                'user_id' => $user->id,
                'class_id' => $data['class_id'],
                'nis' => $data['nis'],
                'nisn' => $data['nisn'] ?? null,
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'no_telepon' => $data['no_telepon'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);
        });

        return $siswa;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Update Siswa record
     * 2. Sync User name and auto-generated email (from NIS)
     * 3. All within a single database transaction
     */
    public function update(Siswa $siswa, array $data): Siswa
    {
        /** @var Siswa $updated */
        $updated = $this->transaction(function () use ($siswa, $data): Model {
            // 1. Update the associated User account.
            // Email is always re-generated from NIS so it stays in sync.
            $email = Siswa::generateEmail((string) $data['nis']);

            $this->userRepository->update($siswa->user, [
                'name' => $data['nama'],
                'email' => $email,
            ]);

            // 2. Update Siswa record
            return $this->siswaRepository->update($siswa, [
                'class_id' => $data['class_id'],
                'nis' => $data['nis'],
                'nisn' => $data['nisn'] ?? null,
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'no_telepon' => $data['no_telepon'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     *
     * Soft deletes only the Siswa record. Does NOT delete the User.
     */
    public function destroy(Siswa $siswa): bool
    {
        return $this->siswaRepository->delete($siswa);
    }

    /**
     * {@inheritDoc}
     *
     * Restores a soft-deleted siswa.
     */
    public function restore(Siswa $siswa): bool
    {
        return $this->siswaRepository->restore($siswa);
    }

    /**
     * {@inheritDoc}
     *
     * Permanently deletes both the Siswa record and its associated User.
     */
    public function forceDelete(Siswa $siswa): bool
    {
        return $this->transaction(function () use ($siswa): bool {
            $result = $this->siswaRepository->forceDelete($siswa);

            // User cascade delete is handled by foreign key (cascadeOnDelete)
            $siswa->user()->withTrashed()->first()?->forceDelete();

            return $result;
        });
    }

    /**
     * {@inheritDoc}
     *
     * @return array{total: int, updated: int, skipped: int, failed: int}
     */
    public function migrateStudentPasswords(): array
    {
        $total = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Siswa> $students */
        $students = Siswa::query()->withoutTrashed()->with('user')->get();

        $total = $students->count();

        foreach ($students as $siswa) {
            /** @var User|null $user */
            $user = $siswa->user;

            // Guard: student must have a linked, non-deleted User with the Siswa role.
            if ($user === null || $user->trashed()) {
                $skipped++;
                continue;
            }

            if (! $user->hasRole(UserRole::SISWA->value)) {
                $skipped++;
                continue;
            }

            // Idempotency: skip if already compliant.
            if (Hash::check('password', (string) $user->password) && ! (bool) $user->must_change_password) {
                $skipped++;
                continue;
            }

            try {
                $this->userRepository->update($user, [
                    'password' => Hash::make('password'),
                    'must_change_password' => false,
                ]);

                $updated++;
            } catch (\Throwable $e) {
                $failed++;
                // Log the error; don't halt the entire migration.
                logger()->error("Failed to migrate student password for NIS={$siswa->nis}, user_id={$user->id}: {$e->getMessage()}");
            }
        }

        return [
            'total' => $total,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }
}
