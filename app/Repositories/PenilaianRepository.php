<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Penilaian;
use App\Repositories\Interfaces\PenilaianRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Penilaian>
 */
class PenilaianRepository extends EloquentRepository implements PenilaianRepositoryInterface
{
    public function __construct(Penilaian $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        ?string $status = null,
        ?int $guruId = null,
        ?int $periodeId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery()
            ->with([
                'penempatanPKL',
                'penempatanPKL.siswa',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
                'dinilaiOleh',
            ]);

        // Search by keyword (siswa, guru, dudi)
        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->whereHas('penempatanPKL.siswa', function ($sq) use ($keyword): void {
                    $sq->where('nama', 'like', "%{$keyword}%")
                       ->orWhere('nis', 'like', "%{$keyword}%");
                })
                ->orWhereHas('penempatanPKL.guru', function ($gq) use ($keyword): void {
                    $gq->where('nama', 'like', "%{$keyword}%");
                })
                ->orWhereHas('penempatanPKL.dudi', function ($dq) use ($keyword): void {
                    $dq->where('nama_perusahaan', 'like', "%{$keyword}%");
                })
                ->orWhere('predikat', 'like', "%{$keyword}%");
            });
        }

        // Filter by status
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        // Filter by guru
        if ($guruId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($guruId): void {
                $pq->where('guru_id', $guruId);
            });
        }

        // Filter by periode
        if ($periodeId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($periodeId): void {
                $pq->where('periode_pkl_id', $periodeId);
            });
        }

        // Sorting - safe whitelist
        $allowedSorts = ['created_at', 'status', 'nilai_akhir', 'predikat', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function getByGuruPaginated(
        int $guruId,
        ?string $keyword = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery()
            ->with([
                'penempatanPKL',
                'penempatanPKL.siswa',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
                'dinilaiOleh',
            ])
            ->whereHas('penempatanPKL', function ($q) use ($guruId): void {
                $q->where('guru_id', $guruId);
            });

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->whereHas('penempatanPKL.siswa', function ($sq) use ($keyword): void {
                    $sq->where('nama', 'like', "%{$keyword}%")
                       ->orWhere('nis', 'like', "%{$keyword}%");
                })
                ->orWhereHas('penempatanPKL.dudi', function ($dq) use ($keyword): void {
                    $dq->where('nama_perusahaan', 'like', "%{$keyword}%");
                });
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($periodeId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($periodeId): void {
                $pq->where('periode_pkl_id', $periodeId);
            });
        }

        $allowedSorts = ['created_at', 'status', 'nilai_akhir', 'predikat', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function getBySiswaPaginated(
        int $siswaId,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery()
            ->with([
                'penempatanPKL',
                'penempatanPKL.siswa',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
                'dinilaiOleh',
            ])
            ->whereHas('penempatanPKL', function ($q) use ($siswaId): void {
                $q->where('siswa_id', $siswaId);
            });

        $allowedSorts = ['created_at', 'status', 'nilai_akhir', 'predikat', 'updated_at'];
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
            ->with([
                'penempatanPKL',
                'penempatanPKL.siswa',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
                'dinilaiOleh',
            ])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Penilaian $penilaian): bool
    {
        return (bool) $penilaian->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Penilaian $penilaian): bool
    {
        return (bool) $penilaian->forceDelete();
    }
}
