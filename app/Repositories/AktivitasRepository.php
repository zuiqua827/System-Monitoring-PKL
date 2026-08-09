<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Aktivitas;
use App\Repositories\Interfaces\AktivitasRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Aktivitas>
 */
class AktivitasRepository extends EloquentRepository implements AktivitasRepositoryInterface
{
    public function __construct(Aktivitas $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function search(
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        ?int $guruId = null,
        ?int $siswaId = null,
        string $sortBy = 'tanggal',
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
                'validatedBy',
            ]);

        // Search by keyword (siswa, judul)
        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('judul', 'like', "%{$keyword}%")
                  ->orWhere('deskripsi', 'like', "%{$keyword}%")
                  ->orWhereHas('penempatanPKL.siswa', function ($sq) use ($keyword): void {
                      $sq->where('nama', 'like', "%{$keyword}%")
                         ->orWhere('nis', 'like', "%{$keyword}%");
                  });
            });
        }

        // Filter by tanggal
        if ($tanggal !== null && $tanggal !== '') {
            $query->whereDate('tanggal', $tanggal);
        }

        // Filter by status
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        // Filter by periode
        if ($periodeId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($periodeId): void {
                $pq->where('periode_pkl_id', $periodeId);
            });
        }

        // Filter by guru
        if ($guruId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($guruId): void {
                $pq->where('guru_id', $guruId);
            });
        }

        // Filter by siswa
        if ($siswaId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($siswaId): void {
                $pq->where('siswa_id', $siswaId);
            });
        }

        // Sorting - safe whitelist
        $allowedSorts = ['tanggal', 'status', 'jam_mulai', 'jam_selesai', 'created_at', 'updated_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'tanggal';
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
        ?string $tanggal = null,
        ?string $status = null,
        string $sortBy = 'tanggal',
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
                'validatedBy',
            ])
            ->whereHas('penempatanPKL', function ($q) use ($siswaId): void {
                $q->where('siswa_id', $siswaId);
            });

        if ($tanggal !== null && $tanggal !== '') {
            $query->whereDate('tanggal', $tanggal);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $allowedSorts = ['tanggal', 'status', 'jam_mulai', 'jam_selesai', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'tanggal';
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
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'tanggal',
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
                'validatedBy',
            ])
            ->whereHas('penempatanPKL', function ($q) use ($guruId): void {
                $q->where('guru_id', $guruId);
            });

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('judul', 'like', "%{$keyword}%")
                  ->orWhereHas('penempatanPKL.siswa', function ($sq) use ($keyword): void {
                      $sq->where('nama', 'like', "%{$keyword}%")
                         ->orWhere('nis', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($tanggal !== null && $tanggal !== '') {
            $query->whereDate('tanggal', $tanggal);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($periodeId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($periodeId): void {
                $pq->where('periode_pkl_id', $periodeId);
            });
        }

        $allowedSorts = ['tanggal', 'status', 'jam_mulai', 'jam_selesai', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'tanggal';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    public function getByDudiPaginated(
        int $dudiId,
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'tanggal',
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
                'validatedBy',
            ])
            ->whereHas('penempatanPKL', function ($q) use ($dudiId): void {
                $q->where('dudi_id', $dudiId);
            });

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('judul', 'like', "%{$keyword}%")
                  ->orWhereHas('penempatanPKL.siswa', function ($sq) use ($keyword): void {
                      $sq->where('nama', 'like', "%{$keyword}%")
                         ->orWhere('nis', 'like', "%{$keyword}%");
                  });
            });
        }

        if ($tanggal !== null && $tanggal !== '') {
            $query->whereDate('tanggal', $tanggal);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($periodeId !== null) {
            $query->whereHas('penempatanPKL', function ($pq) use ($periodeId): void {
                $pq->where('periode_pkl_id', $periodeId);
            });
        }

        $allowedSorts = ['tanggal', 'status', 'jam_mulai', 'jam_selesai', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'tanggal';
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
                'validatedBy',
            ])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Aktivitas $aktivitas): bool
    {
        return (bool) $aktivitas->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Aktivitas $aktivitas): bool
    {
        return (bool) $aktivitas->forceDelete();
    }
}

