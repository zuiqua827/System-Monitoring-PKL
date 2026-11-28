<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Jurusan;
use App\Repositories\Interfaces\JurusanRepositoryInterface;
use App\Services\Interfaces\JurusanServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service layer for Jurusan business logic.
 *
 * Handles all business operations for Jurusan module.
 * Controller should NOT contain business logic — it delegates to this service.
 */
class JurusanService extends Service implements JurusanServiceInterface
{
    public function __construct(
        private readonly JurusanRepositoryInterface $jurusanRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'kode',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->jurusanRepository->search($keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Jurusan
    {
        /** @var Jurusan|null $jurusan */
        $jurusan = $this->jurusanRepository->find($id);

        if ($jurusan === null) {
            throw new ModelNotFoundException('Jurusan tidak ditemukan.');
        }

        return $jurusan;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Jurusan
    {
        /** @var Jurusan $jurusan */
        $jurusan = $this->transaction(fn (): Model => $this->jurusanRepository->create($data));

        return $jurusan;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Jurusan $jurusan, array $data): Jurusan
    {
        /** @var Jurusan $updated */
        $updated = $this->transaction(fn (): Model => $this->jurusanRepository->update($jurusan, $data));

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Jurusan $jurusan): bool
    {
        return $this->jurusanRepository->delete($jurusan);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Jurusan $jurusan): bool
    {
        return $this->jurusanRepository->restore($jurusan);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Jurusan $jurusan): bool
    {
        return $this->jurusanRepository->forceDelete($jurusan);
    }
}
