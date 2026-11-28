<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PeriodePKL;
use App\Repositories\Interfaces\PeriodePKLRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<PeriodePKL>
 */
class PeriodePKLRepository extends EloquentRepository implements PeriodePKLRepositoryInterface
{
    public function __construct(PeriodePKL $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery();

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$keyword}%")
                  ->orWhere('status', 'like', "%{$keyword}%")
                  ->orWhere('keterangan', 'like', "%{$keyword}%");
            });
        }

        $allowedSorts = ['nama', 'tahun_ajaran', 'tanggal_mulai', 'tanggal_selesai', 'status', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'nama';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function allWithTrashed(): Collection
    {
        return $this->model->newQuery()->withTrashed()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(PeriodePKL $periodePkl): bool
    {
        return (bool) $periodePkl->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(PeriodePKL $periodePkl): bool
    {
        return (bool) $periodePkl->forceDelete();
    }
}
