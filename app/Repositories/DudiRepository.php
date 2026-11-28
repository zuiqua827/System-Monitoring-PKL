<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Dudi;
use App\Repositories\Interfaces\DudiRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Dudi>
 */
class DudiRepository extends EloquentRepository implements DudiRepositoryInterface
{
    public function __construct(Dudi $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama_perusahaan',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery();

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama_perusahaan', 'like', "%{$keyword}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$keyword}%")
                  ->orWhere('email_perusahaan', 'like', "%{$keyword}%")
                  ->orWhere('bidang_usaha', 'like', "%{$keyword}%")
                  ->orWhere('alamat', 'like', "%{$keyword}%");
            });
        }

        $allowedSorts = ['nama_perusahaan', 'penanggung_jawab', 'bidang_usaha', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'nama_perusahaan';
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
    public function restore(Dudi $dudi): bool
    {
        return (bool) $dudi->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Dudi $dudi): bool
    {
        return (bool) $dudi->forceDelete();
    }
}
