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
        ?int $jurusanId = null,
        ?int $kelasId = null,
        ?int $dudiId = null,
        ?int $guruId = null,
        ?string $status = null,
    ): LengthAwarePaginator {
$query = $this->newQuery()
            ->with(['siswa.kelas', 'guru', 'dudi', 'periodePKL']);

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

        if ($jurusanId !== null && $jurusanId !== 0) {
            $query->whereHas('siswa.kelas', fn ($q) => $q->where('jurusan_id', $jurusanId));
        }

if ($kelasId !== null && $kelasId !== 0) {
            $query->whereHas('siswa', fn ($q) => $q->where('class_id', $kelasId));
        }

        if ($dudiId !== null && $dudiId !== 0) {
            $query->where('dudi_id', $dudiId);
        }

        if ($guruId !== null && $guruId !== 0) {
            $query->where('guru_id', $guruId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $allowedSorts = ['created_at', 'status', 'nomor_surat', 'tanggal_mulai', 'tanggal_selesai'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'created_at';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    public function searchByDudi(
        int $dudiId,
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery()
            ->with(['siswa.kelas.jurusan', 'guru', 'periodePKL'])
            ->where('dudi_id', $dudiId);

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->whereHas('siswa', function ($sq) use ($keyword): void {
                      $sq->where('nama', 'like', "%{$keyword}%")
                         ->orWhere('nis', 'like', "%{$keyword}%");
                  });
            });
        }

        $allowedSorts = ['created_at', 'status'];
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
