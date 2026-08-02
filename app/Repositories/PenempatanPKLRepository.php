<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PenempatanPKL;
use App\Repositories\Interfaces\PenempatanPKLRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<PenempatanPKL>
 */
class PenempatanPKLRepository extends EloquentRepository implements PenempatanPKLRepositoryInterface
{
    public function __construct(PenempatanPKL $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery()
            ->with(['siswa', 'guru', 'dudi', 'periodePKL']);

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('nomor_surat', 'like', "%{$keyword}%")
                  ->orWhere('status', 'like', "%{$keyword}%")
                  ->orWhere('catatan', 'like', "%{$keyword}%")
                  ->orWhereHas('siswa', function ($sq) use ($keyword): void {
                      $sq->where('nama', 'like', "%{$keyword}%")
                         ->orWhere('nis', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('guru', function ($gq) use ($keyword): void {
                      $gq->where('nama', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('dudi', function ($dq) use ($keyword): void {
                      $dq->where('nama_perusahaan', 'like', "%{$keyword}%");
                  })
                  ->orWhereHas('periodePKL', function ($pq) use ($keyword): void {
                      $pq->where('nama', 'like', "%{$keyword}%");
                  });
            });
        }

        $allowedSorts = ['created_at', 'status', 'nomor_surat', 'tanggal_mulai', 'tanggal_selesai'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function allWithTrashed(): Collection
    {
        return $this->model->newQuery()->withTrashed()
            ->with(['siswa', 'guru', 'dudi', 'periodePKL'])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(PenempatanPKL $penempatanPkl): bool
    {
        return (bool) $penempatanPkl->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(PenempatanPKL $penempatanPkl): bool
    {
        return (bool) $penempatanPkl->forceDelete();
    }
}
