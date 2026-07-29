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
     *
     * Business Rules:
     * 1. Validate that there is no other active period if status is "Aktif"
     * 2. All within a single database transaction
     */
    public function store(array $data): PeriodePKL
    {
        /** @var PeriodePKL $periodePkl */
        $periodePkl = $this->transaction(function () use ($data): Model {
            // Check for duplicate active period
            if ($data['status'] === 'Aktif') {
                $this->ensureNoActivePeriod();
            }

            return $this->periodePklRepository->create($data);
        });

        return $periodePkl;
    }

    /**
     * {@inheritDoc}
     *
     * Business Rules:
     * 1. Validate that there is no other active period if status is "Aktif"
     * 2. All within a single database transaction
     */
    public function update(PeriodePKL $periodePkl, array $data): PeriodePKL
    {
        /** @var PeriodePKL $updated */
        $updated = $this->transaction(function () use ($periodePkl, $data): Model {
            // Check for duplicate active period (exclude current record)
            if ($data['status'] === 'Aktif') {
                $this->ensureNoActivePeriod($periodePkl->id);
            }

            return $this->periodePklRepository->update($periodePkl, $data);
        });

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

    /**
     * Ensure no other active period exists.
     *
     * @throws \InvalidArgumentException
     */
    private function ensureNoActivePeriod(?int $excludeId = null): void
    {
        $query = PeriodePKL::where('status', 'Aktif');

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \InvalidArgumentException(
                'Tidak dapat mengaktifkan lebih dari satu periode PKL dalam waktu yang sama. ' .
                'Nonaktifkan periode aktif terlebih dahulu.'
            );
        }
    }
}
