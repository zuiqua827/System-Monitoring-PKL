<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Kelas;
use App\Repositories\Interfaces\KelasRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Kelas>
 */
class KelasRepository extends EloquentRepository implements KelasRepositoryInterface
{
    public function __construct(Kelas $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        ?int $tingkat = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery()->withTrashed()->with('jurusan');

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('tingkat', 'like', "%{$keyword}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$keyword}%")
                  ->orWhereHas('jurusan', function ($jq) use ($keyword): void {
                      $jq->where('nama', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($tingkat !== null) {
            $query->where('tingkat', $tingkat);
        }

        $allowedSorts = ['nama', 'tingkat', 'tahun_ajaran', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'nama';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        if ($sortBy === 'nama') {
            $query->orderBy('tingkat', 'asc')->orderBy('nama', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function allWithTrashed(): Collection
    {
        return $this->model->newQuery()->withTrashed()->with('jurusan')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Kelas $kelas): bool
    {
        return (bool) $kelas->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Kelas $kelas): bool
    {
        return (bool) $kelas->forceDelete();
    }
}
