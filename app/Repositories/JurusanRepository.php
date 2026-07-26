<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Jurusan;
use App\Repositories\Interfaces\JurusanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Jurusan>
 */
class JurusanRepository extends EloquentRepository implements JurusanRepositoryInterface
{
    public function __construct(Jurusan $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'kode',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery();

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('kode', 'like', "%{$keyword}%")
                  ->orWhere('nama', 'like', "%{$keyword}%")
                  ->orWhere('deskripsi', 'like', "%{$keyword}%");
            });
        }

        $allowedSorts = ['kode', 'nama', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'kode';
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
    public function restore(Jurusan $jurusan): bool
    {
        return (bool) $jurusan->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Jurusan $jurusan): bool
    {
        return (bool) $jurusan->forceDelete();
    }
}
