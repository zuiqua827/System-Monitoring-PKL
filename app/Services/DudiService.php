<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Dudi;
use App\Models\User;
use App\Repositories\Interfaces\DudiRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\DudiServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * Service layer for DUDI business logic.
 *
 * Handles all business operations for DUDI module.
 * Controller should NOT contain business logic — it delegates to this service.
 *
 * Key difference from simple modules (Jurusan, Kelas, PeriodePKL):
 * DUDI has a belongsTo relationship with User.
 * Creating/updating/deleting DUDI also affects the associated User account.
 */
class DudiService extends Service implements DudiServiceInterface
{
    public function __construct(
        private readonly DudiRepositoryInterface $dudiRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'nama_perusahaan',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->dudiRepository->search($keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Dudi
    {
        /** @var Dudi|null $dudi */
        $dudi = $this->dudiRepository->find($id);

        if ($dudi === null) {
            throw new ModelNotFoundException('DUDI tidak ditemukan.');
        }

        return $dudi;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Create User account with role "DUDI"
     * 2. Use "password" as default initial password (hashed via Hash::make, force change on first login)
     * 3. Create Dudi record linked to the new User
     * 4. All within a single database transaction
     */
    public function store(array $data): Dudi
    {
        /** @var Dudi $dudi */
        $dudi = $this->transaction(function () use ($data): Model {
            $existingUser = User::withTrashed()->where('email', $data['email'])->first();
            if ($existingUser !== null) {
                if ($existingUser->trashed()) {
                    $existingUser->restore();
                }
                $existingUser->update([
                    'name' => $data['nama_perusahaan'],
                    'password' => Hash::make($data['password'] ?? 'password'),
                ]);

                $existingDudi = Dudi::withTrashed()->where('user_id', $existingUser->id)->first();
                if ($existingDudi !== null) {
                    if ($existingDudi->trashed()) {
                        $existingDudi->restore();
                    }
                    $existingDudi->update([
                        'nama_perusahaan' => $data['nama_perusahaan'],
                        'penanggung_jawab' => $data['penanggung_jawab'],
                        'email_perusahaan' => $data['email_perusahaan'] ?? $data['email'] ?? null,
                        'no_telepon' => $data['no_telepon'],
                        'alamat' => $data['alamat'],
                        'kecamatan' => $data['kecamatan'] ?? null,
                        'kabupaten' => $data['kabupaten'] ?? null,
                        'provinsi' => $data['provinsi'] ?? null,
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                        'status_aktif' => $data['status_aktif'] ?? true,
                    ]);

                    return $existingDudi;
                }
            }

            // 1. Create User account with default password "password"
            /** @var \App\Models\User $user */
            $user = $this->userRepository->create([
                'name' => $data['nama_perusahaan'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password'),
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            // 2. Assign DUDI role via Spatie
            $user->assignRole(UserRole::DUDI->value);

            // 3. Create Dudi record linked to the User
            return $this->dudiRepository->create([
                'user_id' => $user->id,
                'nama_perusahaan' => $data['nama_perusahaan'],
                'penanggung_jawab' => $data['penanggung_jawab'],
                'email_perusahaan' => $data['email_perusahaan'] ?? $data['email'] ?? null,
                'no_telepon' => $data['no_telepon'],
                'alamat' => $data['alamat'],
                'kecamatan' => $data['kecamatan'] ?? null,
                'kabupaten' => $data['kabupaten'] ?? null,
                'provinsi' => $data['provinsi'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status_aktif' => $data['status_aktif'] ?? true,
            ]);
        });

        return $dudi;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Update Dudi record
     * 2. Sync User name and email if changed
     * 3. All within a single database transaction
     */
    public function update(Dudi $dudi, array $data): Dudi
    {
        /** @var Dudi $updated */
        $updated = $this->transaction(function () use ($dudi, $data): Model {
            // 1. Update the associated User account
            $userUpdateData = ['name' => $data['nama_perusahaan']];

            if (isset($data['email'])) {
                $userUpdateData['email'] = $data['email'];
            }

            $this->userRepository->update($dudi->user, $userUpdateData);

            // 2. Update Dudi record
            $dudiUpdateData = [
                'nama_perusahaan' => $data['nama_perusahaan'],
                'penanggung_jawab' => $data['penanggung_jawab'],
                'no_telepon' => $data['no_telepon'],
                'alamat' => $data['alamat'],
                'kecamatan' => $data['kecamatan'] ?? null,
                'kabupaten' => $data['kabupaten'] ?? null,
                'provinsi' => $data['provinsi'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status_aktif' => $data['status_aktif'] ?? true,
            ];

            if (isset($data['email'])) {
                $dudiUpdateData['email_perusahaan'] = $data['email'];
            }

            return $this->dudiRepository->update($dudi, $dudiUpdateData);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     *
     * Soft deletes both the Dudi record and its associated User.
     */
    public function destroy(Dudi $dudi): bool
    {
        return $this->transaction(function () use ($dudi): bool {
            $dudi->user->delete();

            return $this->dudiRepository->delete($dudi);
        });
    }

    /**
     * {@inheritDoc}
     *
     * Restores both the Dudi record and its associated User.
     */
    public function restore(Dudi $dudi): bool
    {
        return $this->transaction(function () use ($dudi): bool {
            $dudi->user()->withTrashed()->first()?->restore();

            return $this->dudiRepository->restore($dudi);
        });
    }

    /**
     * {@inheritDoc}
     *
     * Permanently deletes both the Dudi record and its associated User.
     */
    public function forceDelete(Dudi $dudi): bool
    {
        return $this->transaction(function () use ($dudi): bool {
            $result = $this->dudiRepository->forceDelete($dudi);

            // User cascade delete is handled by foreign key (cascadeOnDelete)
            $dudi->user()->withTrashed()->first()?->forceDelete();

            return $result;
        });
    }
}
