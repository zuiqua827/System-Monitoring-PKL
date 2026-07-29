<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Siswa;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SiswaServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    ): LengthAwarePaginator {
        return $this->siswaRepository->search($keyword, $sortBy, $sortDirection, $perPage);
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
     * 2. Use NIS as username, tanggal_lahir as initial password (hashed)
     * 3. Create Siswa record linked to the new User
     * 4. All within a single database transaction
     */
    public function store(array $data): Siswa
    {
        /** @var Siswa $siswa */
        $siswa = $this->transaction(function () use ($data): Model {
            // 1. Create User account
            /** @var \App\Models\User $user */
            $user = $this->userRepository->create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
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
     * 2. Sync User name and email if changed
     * 3. All within a single database transaction
     */
    public function update(Siswa $siswa, array $data): Siswa
    {
        /** @var Siswa $updated */
        $updated = $this->transaction(function () use ($siswa, $data): Model {
            // 1. Update the associated User account
            $userUpdateData = ['name' => $data['nama']];

            if (isset($data['email'])) {
                $userUpdateData['email'] = $data['email'];
            }

            $this->userRepository->update($siswa->user, $userUpdateData);

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
}
