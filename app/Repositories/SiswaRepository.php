<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Siswa;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Siswa>
 */
class SiswaRepository extends EloquentRepository implements SiswaRepositoryInterface
{
    public function __construct(Siswa $model)
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
        $query = $this->newQuery()->with('kelas');

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nis', 'like', "%{$keyword}%")
                  ->orWhere('nisn', 'like', "%{$keyword}%")
                  ->orWhere('no_telepon', 'like', "%{$keyword}%")
                  ->orWhere('alamat', 'like', "%{$keyword}%")
                  ->orWhereHas('kelas', function ($kq) use ($keyword): void {
                      $kq->where('nama', 'like', "%{$keyword}%");
                  });
            });
        }

        $allowedSorts = ['nama', 'nis', 'created_at'];
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
        return $this->model->newQuery()->withTrashed()->with('kelas')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Siswa $siswa): bool
    {
        return (bool) $siswa->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Siswa $siswa): bool
    {
        return (bool) $siswa->forceDelete();
    }
}
