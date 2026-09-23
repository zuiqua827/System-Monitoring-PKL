<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
use App\Repositories\Interfaces\KelasRepositoryInterface;
use App\Services\Interfaces\KelasServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service layer for Kelas business logic.
 *
 * Handles all business operations for Kelas module.
 * Controller should NOT contain business logic — it delegates to this service.
 */
class KelasService extends Service implements KelasServiceInterface
{
    public function __construct(
        private readonly KelasRepositoryInterface $kelasRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(
        ?string $keyword = null,
        ?int $tingkat = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        return $this->kelasRepository->search($keyword, $tingkat, $sortBy, $sortDirection, $perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Kelas
    {
        /** @var Kelas|null $kelas */
        $kelas = $this->kelasRepository->find($id);

        if ($kelas === null) {
            throw new ModelNotFoundException('Kelas tidak ditemukan.');
        }

        return $kelas;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Kelas
    {
        return $this->transaction(function () use ($data): Kelas {
            /** @var Kelas|null $existing */
            $existing = Kelas::withTrashed()
                ->where('jurusan_id', $data['jurusan_id'])
                ->where('nama', $data['nama'])
                ->where('tahun_ajaran', $data['tahun_ajaran'])
                ->first();

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($data);

                return $existing;
            }

            /** @var Kelas $kelas */
            $kelas = $this->kelasRepository->create($data);

            return $kelas;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function update(Kelas $kelas, array $data): Kelas
    {
        /** @var Kelas $updated */
        $updated = $this->transaction(fn (): Model => $this->kelasRepository->update($kelas, $data));

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Kelas $kelas): bool
    {
        return $this->kelasRepository->delete($kelas);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Kelas $kelas): bool
    {
        return $this->kelasRepository->restore($kelas);
    }

    /**
     * {@inheritDoc}
     *
     * Safely force-deletes a Kelas within a transaction.
     * Blocks deletion if siswa records still reference this kelas.
     * Cleans up sipintu_classroom_mappings before deleting.
     *
     * @throws \RuntimeException if siswa records block deletion
     */
    public function forceDelete(Kelas $kelas): bool
    {
        return $this->transaction(function () use ($kelas): bool {
            $activeSiswaCount = $kelas->siswa()->whereNull('deleted_at')->count();
            if ($activeSiswaCount > 0) {
                throw new \RuntimeException(
                    "Kelas \"{$kelas->nama}\" tidak dapat dihapus permanen karena masih memiliki {$activeSiswaCount} siswa aktif."
                );
            }

            // All remaining siswa are soft-deleted, safe to cascade
            $trashedSiswa = $kelas->siswa()->onlyTrashed()->get();
            $siswaService = app(\App\Services\Interfaces\SiswaServiceInterface::class);
            foreach ($trashedSiswa as $siswa) {
                $siswaService->forceDelete($siswa);
            }

            // Remove sipintu_classroom_mappings that reference this kelas
            SipintuClassroomMapping::where('kelas_id', $kelas->id)->delete();

            return $this->kelasRepository->forceDelete($kelas);
        });
    }
}
