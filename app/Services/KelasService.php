<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kelas;
use App\Repositories\Interfaces\KelasRepositoryInterface;
use App\Services\Interfaces\KelasServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service layer for Kelas business logic.
 *
 * Handles all business operations for Kelas module.
 * Controller should NOT contain business logic — it delegates to this service.
 */
class KelasService extends Service implements KelasServiceInterface
{
    public function __construct(
        private readonly KelasRepositoryInterface $kelasRepository,
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
        return $this->kelasRepository->search($keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Kelas
    {
        /** @var Kelas|null $kelas */
        $kelas = $this->kelasRepository->find($id);

        if ($kelas === null) {
            throw new ModelNotFoundException('Kelas tidak ditemukan.');
        }

        return $kelas;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Kelas
    {
        /** @var Kelas $kelas */
        $kelas = $this->transaction(fn (): Model => $this->kelasRepository->create($data));

        return $kelas;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Kelas $kelas, array $data): Kelas
    {
        /** @var Kelas $updated */
        $updated = $this->transaction(fn (): Model => $this->kelasRepository->update($kelas, $data));

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Kelas $kelas): bool
    {
        return $this->kelasRepository->delete($kelas);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Kelas $kelas): bool
    {
        return $this->kelasRepository->restore($kelas);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Kelas $kelas): bool
    {
        return $this->kelasRepository->forceDelete($kelas);
    }
}
