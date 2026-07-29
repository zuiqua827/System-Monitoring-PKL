<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Guru;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\GuruServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service layer for Guru business logic.
 *
 * Handles all business operations for Guru module.
 * Controller should NOT contain business logic — it delegates to this service.
 *
 * Key difference from simple modules (Jurusan, Kelas, PeriodePKL):
 * Guru has a belongsTo relationship with User.
 * Creating/updating/deleting Guru also affects the associated User account.
 */
class GuruService extends Service implements GuruServiceInterface
{
    public function __construct(
        private readonly GuruRepositoryInterface $guruRepository,
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
        return $this->guruRepository->search($keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Guru
    {
        /** @var Guru|null $guru */
        $guru = $this->guruRepository->find($id);

        if ($guru === null) {
            throw new ModelNotFoundException('Guru tidak ditemukan.');
        }

        return $guru;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Create User account with role "Guru"
     * 2. Use NIP Guru as initial password (hashed, force change on first login)
     * 3. Create Guru record linked to the new User
     * 4. All within a single database transaction
     */
    public function store(array $data): Guru
    {
        /** @var Guru $guru */
        $guru = $this->transaction(function () use ($data): Model {
            // 1. Create User account with NIP as initial password
            /** @var \App\Models\User $user */
            $user = $this->userRepository->create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => bcrypt($data['nip']),
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            // 2. Assign Guru role via Spatie
            $user->assignRole(UserRole::GURU->value);

            // 3. Create Guru record linked to the User
            return $this->guruRepository->create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);
        });

        return $guru;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Update Guru record
     * 2. Sync User name and email if changed
     * 3. All within a single database transaction
     */
    public function update(Guru $guru, array $data): Guru
    {
        /** @var Guru $updated */
        $updated = $this->transaction(function () use ($guru, $data): Model {
            // 1. Update the associated User account
            $userUpdateData = ['name' => $data['nama']];

            if (isset($data['email'])) {
                $userUpdateData['email'] = $data['email'];
            }

            $this->userRepository->update($guru->user, $userUpdateData);

            // 2. Update Guru record
            return $this->guruRepository->update($guru, [
                'nip' => $data['nip'],
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     *
     * Soft deletes both the Guru record and its associated User.
     */
    public function destroy(Guru $guru): bool
    {
        return $this->transaction(function () use ($guru): bool {
            $guru->user->delete();

            return $this->guruRepository->delete($guru);
        });
    }

    /**
     * {@inheritDoc}
     *
     * Restores both the Guru record and its associated User.
     */
    public function restore(Guru $guru): bool
    {
        return $this->transaction(function () use ($guru): bool {
            $guru->user()->withTrashed()->first()?->restore();

            return $this->guruRepository->restore($guru);
        });
    }

    /**
     * {@inheritDoc}
     *
     * Permanently deletes both the Guru record and its associated User.
     */
    public function forceDelete(Guru $guru): bool
    {
        return $this->transaction(function () use ($guru): bool {
            $result = $this->guruRepository->forceDelete($guru);

            // User cascade delete is handled by foreign key (cascadeOnDelete)
            $guru->user()->withTrashed()->first()?->forceDelete();

            return $result;
        });
    }
}
