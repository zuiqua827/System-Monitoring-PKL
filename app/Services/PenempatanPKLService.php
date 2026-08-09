<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PenempatanPKL;
use App\Repositories\Interfaces\PenempatanPKLRepositoryInterface;
use App\Services\Interfaces\PenempatanPKLServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

/**
 * Service layer for PenempatanPKL business logic.
 */
class PenempatanPKLService extends Service implements PenempatanPKLServiceInterface
{
    public function __construct(
        private readonly PenempatanPKLRepositoryInterface $penempatanPklRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
public function getPaginated(
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
        return $this->penempatanPklRepository->search(
            $keyword,
            $sortBy,
            $sortDirection,
            $perPage,
            $jurusanId,
            $kelasId,
            $dudiId,
            $guruId,
            $status,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiSiswaPaginated(
        int $dudiId,
        ?string $keyword = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->penempatanPklRepository->searchByDudi($dudiId, $keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): PenempatanPKL
    {
        /** @var PenempatanPKL|null $penempatanPkl */
        $penempatanPkl = $this->penempatanPklRepository->find($id);

        if ($penempatanPkl === null) {
            throw new ModelNotFoundException('Penempatan PKL tidak ditemukan.');
        }

        return $penempatanPkl;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): PenempatanPKL
    {
        /** @var PenempatanPKL $penempatanPkl */
        $penempatanPkl = $this->transaction(function () use ($data): Model {
            // Auto-set dibuat_oleh to current authenticated user
            $data['dibuat_oleh'] = Auth::id();

            return $this->penempatanPklRepository->create([
                'periode_pkl_id' => $data['periode_pkl_id'],
                'guru_id' => $data['guru_id'],
                'dudi_id' => $data['dudi_id'],
                'siswa_id' => $data['siswa_id'],
                'dibuat_oleh' => $data['dibuat_oleh'],
                'approved_by' => $data['approved_by'] ?? null,
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'tanggal_mulai' => $data['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'catatan' => $data['catatan'] ?? null,
            ]);
        });

        return $penempatanPkl;
    }

    /**
     * {@inheritDoc}
     */
    public function update(PenempatanPKL $penempatanPkl, array $data): PenempatanPKL
    {
        /** @var PenempatanPKL $updated */
        $updated = $this->transaction(function () use ($penempatanPkl, $data): Model {
            return $this->penempatanPklRepository->update($penempatanPkl, [
                'periode_pkl_id' => $data['periode_pkl_id'],
                'guru_id' => $data['guru_id'],
                'dudi_id' => $data['dudi_id'],
                'siswa_id' => $data['siswa_id'],
                'nomor_surat' => $data['nomor_surat'] ?? null,
                'tanggal_mulai' => $data['tanggal_mulai'] ?? null,
                'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'approved_by' => $data['approved_by'] ?? $penempatanPkl->approved_by,
                'approved_at' => $data['approved_at'] ?? $penempatanPkl->approved_at,
                'catatan' => $data['catatan'] ?? null,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(PenempatanPKL $penempatanPkl): bool
    {
        return $this->penempatanPklRepository->delete($penempatanPkl);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(PenempatanPKL $penempatanPkl): bool
    {
        return $this->penempatanPklRepository->restore($penempatanPkl);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(PenempatanPKL $penempatanPkl): bool
    {
        return $this->penempatanPklRepository->forceDelete($penempatanPkl);
    }
}
