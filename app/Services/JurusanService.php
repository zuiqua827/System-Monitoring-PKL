<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Jurusan;
use App\Models\SipintuClassroomMapping;
use App\Repositories\Interfaces\JurusanRepositoryInterface;
use App\Services\Interfaces\JurusanServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

/**
 * Service layer for Jurusan business logic.
 *
 * Handles all business operations for Jurusan module.
 * Controller should NOT contain business logic — it delegates to this service.
 */
class JurusanService extends Service implements JurusanServiceInterface
{
    public function __construct(
        private readonly JurusanRepositoryInterface $jurusanRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(
        ?string $keyword = null,
        string $sortBy = 'kode',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->jurusanRepository->search($keyword, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Jurusan
    {
        /** @var Jurusan|null $jurusan */
        $jurusan = $this->jurusanRepository->find($id);

        if ($jurusan === null) {
            throw new ModelNotFoundException('Jurusan tidak ditemukan.');
        }

        return $jurusan;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Jurusan
    {
        /** @var Jurusan $jurusan */
        $jurusan = $this->transaction(function () use ($data): Model {
            /** @var Jurusan|null $existing */
            $existing = Jurusan::withTrashed()
                ->where(function ($q) use ($data): void {
                    $q->where('kode', $data['kode'])
                      ->orWhere('nama', $data['nama']);
                })
                ->first();

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($data);

                return $existing;
            }

            return $this->jurusanRepository->create($data);
        });

        return $jurusan;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Jurusan $jurusan, array $data): Jurusan
    {
        /** @var Jurusan $updated */
        $updated = $this->transaction(fn (): Model => $this->jurusanRepository->update($jurusan, $data));

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Jurusan $jurusan): bool
    {
        return $this->jurusanRepository->delete($jurusan);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Jurusan $jurusan): bool
    {
        return $this->jurusanRepository->restore($jurusan);
    }

    /**
     * {@inheritDoc}
     *
     * Safely force-deletes a Jurusan within a transaction.
     * Checks for active child records first and blocks if data integrity would be compromised.
     * Cascades deletion of orphaned/trashed kelas and their sipintu_classroom_mappings.
     *
     * @throws \RuntimeException if active child records block deletion
     */
    public function forceDelete(Jurusan $jurusan): bool
    {
        return $this->transaction(function () use ($jurusan): bool {
            // Check for active child records first
            $activeKelasCount = $jurusan->kelas()->whereNull('deleted_at')->count();
            if ($activeKelasCount > 0) {
                throw new \RuntimeException(
                    "Jurusan \"{$jurusan->nama}\" tidak dapat dihapus permanen karena masih memiliki {$activeKelasCount} kelas aktif."
                );
            }

            // All remaining kelas are soft-deleted, safe to cascade
            $trashedKelas = $jurusan->kelas()->onlyTrashed()->get();
            $kelasService = app(\App\Services\Interfaces\KelasServiceInterface::class);
            foreach ($trashedKelas as $kelas) {
                $kelasService->forceDelete($kelas);
            }

            return $this->jurusanRepository->forceDelete($jurusan);
        });
    }
}
