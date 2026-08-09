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
        ?int $jurusanId = null,
        ?int $kelasId = null,
        ?string $status = null,
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

        if ($jurusanId !== null && $jurusanId > 0) {
            $query->whereHas('kelas', fn ($q) => $q->where('jurusan_id', $jurusanId));
        }

        if ($kelasId !== null && $kelasId > 0) {
            $query->where('class_id', $kelasId);
        }

        if ($status !== null && $status !== '') {
            $query->whereHas('penempatan', fn ($q) => $q->where('status', $status));
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
    public function searchForSelect(?string $search = null): Collection
    {
        $query = $this->model->newQuery()
            ->withoutTrashed()
            ->with(['kelas.jurusan'])
            ->select(['id', 'class_id', 'nis', 'nisn', 'nama'])
            ->orderBy('nama')
            ->limit(25);

        if ($search !== null && $search !== '') {
            $keyword = trim($search);

            $query->where(function ($q) use ($keyword): void {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nis', 'like', "%{$keyword}%")
                  ->orWhere('nisn', 'like', "%{$keyword}%")
                  ->orWhereHas('kelas', function ($kq) use ($keyword): void {
                      $kq->where('nama', 'like', "%{$keyword}%")
                         ->orWhereHas('jurusan', function ($jq) use ($keyword): void {
                             $jq->where('nama', 'like', "%{$keyword}%");
                         });
                  });
            });
        }

        /** @var Collection<int, Siswa> $result */
        $result = $query->get();

        return $result;
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
