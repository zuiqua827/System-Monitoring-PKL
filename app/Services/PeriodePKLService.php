<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PeriodePKL;
use App\Repositories\Interfaces\PeriodePKLRepositoryInterface;
use App\Services\Interfaces\PeriodePKLServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service layer for PeriodePKL business logic.
 *
 * Handles all business operations for Periode PKL module.
 * Controller should NOT contain business logic — it delegates to this service.
 */
class PeriodePKLService extends Service implements PeriodePKLServiceInterface
{
    public function __construct(
        private readonly PeriodePKLRepositoryInterface $periodePklRepository,
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
        return $this->periodePklRepository->search($keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): PeriodePKL
    {
        /** @var PeriodePKL|null $periodePkl */
        $periodePkl = $this->periodePklRepository->find($id);

        if ($periodePkl === null) {
            throw new ModelNotFoundException('Periode PKL tidak ditemukan.');
        }

        return $periodePkl;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): PeriodePKL
    {
        /** @var PeriodePKL $periodePkl */
        $periodePkl = $this->transaction(fn (): Model => $this->periodePklRepository->create($data));

        return $periodePkl;
    }

    /**
     * {@inheritDoc}
     */
    public function update(PeriodePKL $periodePkl, array $data): PeriodePKL
    {
        /** @var PeriodePKL $updated */
        $updated = $this->transaction(fn (): Model => $this->periodePklRepository->update($periodePkl, $data));

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(PeriodePKL $periodePkl): bool
    {
        return $this->periodePklRepository->delete($periodePkl);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(PeriodePKL $periodePkl): bool
    {
        return $this->periodePklRepository->restore($periodePkl);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(PeriodePKL $periodePkl): bool
    {
        return $this->periodePklRepository->forceDelete($periodePkl);
    }
}
