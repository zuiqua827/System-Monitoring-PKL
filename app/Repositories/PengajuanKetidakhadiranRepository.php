<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PengajuanKetidakhadiran;
use App\Repositories\Interfaces\PengajuanKetidakhadiranRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends EloquentRepository<PengajuanKetidakhadiran>
 */
class PengajuanKetidakhadiranRepository extends EloquentRepository implements PengajuanKetidakhadiranRepositoryInterface
{
    public function __construct(PengajuanKetidakhadiran $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getBySiswaPaginated(
        int $siswaId,
        ?string $status = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['penempatanPKL.dudi'])
            ->whereHas('penempatanPKL', function (Builder $query) use ($siswaId) {
                $query->where('siswa_id', $siswaId);
            });

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getByDudiPaginated(
        int $dudiId,
        ?string $status = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with(['penempatanPKL.siswa'])
            ->whereHas('penempatanPKL', function (Builder $query) use ($dudiId) {
                $query->where('dudi_id', $dudiId);
            });

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy($sortBy, $sortDirection)->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function existsForDate(int $penempatanPklId, string $tanggal): bool
    {
        return $this->model->newQuery()
            ->where('penempatan_pkl_id', $penempatanPklId)
            ->where('tanggal', $tanggal)
            ->exists();
    }
}
