<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Absensi;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Absensi>
 */
class AbsensiRepository extends EloquentRepository implements AbsensiRepositoryInterface
{
    public function __construct(Absensi $model)
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
            ]);

        // Search keyword (siswa, guru, dudi)
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

        // Sorting - safe whitelist
        $allowedSorts = ['tanggal', 'status', 'jam_masuk', 'jam_keluar', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'tanggal';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function getByPenempatan(int $penempatanPklId): Collection
    {
        return $this->newQuery()
            ->with([
                'penempatanPKL',
                'penempatanPKL.siswa',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
            ])
            ->where('penempatan_pkl_id', $penempatanPklId)
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findTodayByPenempatan(int $penempatanPklId): ?Absensi
    {
        /** @var Absensi|null $absensi */
        $absensi = $this->newQuery()
            ->where('penempatan_pkl_id', $penempatanPklId)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        return $absensi;
    }

    /**
     * {@inheritDoc}
     */
    public function getBySiswa(int $siswaId): Collection
    {
        return $this->newQuery()
            ->with([
                'penempatanPKL',
                'penempatanPKL.siswa',
                'penempatanPKL.guru',
                'penempatanPKL.dudi',
                'penempatanPKL.periodePKL',
            ])
            ->whereHas('penempatanPKL', function ($q) use ($siswaId): void {
                $q->where('siswa_id', $siswaId);
            })
            ->orderBy('tanggal', 'desc')
            ->get();
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

        $allowedSorts = ['tanggal', 'status', 'jam_masuk', 'jam_keluar', 'created_at'];
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

        $allowedSorts = ['tanggal', 'status', 'jam_masuk', 'jam_keluar', 'created_at'];
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
            ])
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Absensi $absensi): bool
    {
        return (bool) $absensi->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Absensi $absensi): bool
    {
        return (bool) $absensi->forceDelete();
    }
}

