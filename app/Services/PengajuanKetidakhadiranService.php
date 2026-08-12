<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Absensi;
use App\Models\PengajuanKetidakhadiran;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use App\Repositories\Interfaces\PengajuanKetidakhadiranRepositoryInterface;
use App\Services\Interfaces\PengajuanKetidakhadiranServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PengajuanKetidakhadiranService extends Service implements PengajuanKetidakhadiranServiceInterface
{
    public function __construct(
        private readonly PengajuanKetidakhadiranRepositoryInterface $pengajuanRepository,
        private readonly AbsensiRepositoryInterface $absensiRepository
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return $this->pengajuanRepository->getPaginated();
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): PengajuanKetidakhadiran
    {
        /** @var PengajuanKetidakhadiran|null $pengajuan */
        $pengajuan = $this->pengajuanRepository->find($id);

        if ($pengajuan === null) {
            throw new ModelNotFoundException('Pengajuan tidak ditemukan.');
        }

        return $pengajuan;
    }

    /**
     * {@inheritDoc}
     */
    public function getSiswaPengajuanPaginated(int $siswaId, array $filters = []): LengthAwarePaginator
    {
        return $this->pengajuanRepository->getBySiswaPaginated(
            siswaId: $siswaId,
            status: $filters['status'] ?? null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 15),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiPengajuanPaginated(int $dudiId, array $filters = []): LengthAwarePaginator
    {
        return $this->pengajuanRepository->getByDudiPaginated(
            dudiId: $dudiId,
            status: $filters['status'] ?? null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 15),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): PengajuanKetidakhadiran
    {
        return $this->storePengajuan($data);
    }

    /**
     * {@inheritDoc}
     */
    public function storePengajuan(array $data): PengajuanKetidakhadiran
    {
        if ($this->pengajuanRepository->existsForDate((int) $data['penempatan_pkl_id'], $data['tanggal'])) {
            throw new RuntimeException('Anda sudah membuat pengajuan untuk tanggal tersebut.');
        }

        /** @var PengajuanKetidakhadiran $pengajuan */
        $pengajuan = $this->transaction(function () use ($data): Model {
            return $this->pengajuanRepository->create([
                'penempatan_pkl_id' => $data['penempatan_pkl_id'],
                'tanggal' => $data['tanggal'],
                'jenis' => $data['jenis'],
                'alasan' => $data['alasan'],
                'lampiran' => $data['lampiran'] ?? null,
                'status' => 'menunggu',
            ]);
        });

        return $pengajuan;
    }

    /**
     * {@inheritDoc}
     */
    public function process(PengajuanKetidakhadiran $pengajuan, string $status, ?string $catatan, int $validatorId): PengajuanKetidakhadiran
    {
        if ($pengajuan->status !== 'menunggu') {
            throw new RuntimeException('Pengajuan ini sudah diproses.');
        }

        /** @var PengajuanKetidakhadiran $updated */
        $updated = $this->transaction(function () use ($pengajuan, $status, $catatan, $validatorId): Model {
            $pengajuan = $this->pengajuanRepository->update($pengajuan, [
                'status' => $status,
                'catatan_validasi' => $catatan,
                'validated_by' => $validatorId,
                'validated_at' => now(),
            ]);

            // Jika disetujui, buat/update record di tabel absensi
            if ($status === 'disetujui') {
                $existingAbsensi = $this->absensiRepository->findByPenempatanAndTanggal(
                    (int) $pengajuan->penempatan_pkl_id,
                    $pengajuan->tanggal
                );

                if ($existingAbsensi) {
                    $this->absensiRepository->update($existingAbsensi, [
                        'status' => $pengajuan->jenis,
                        'keterangan' => 'Disetujui dari pengajuan ' . $pengajuan->jenis . ': ' . $pengajuan->alasan,
                    ]);
                } else {
                    $this->absensiRepository->create([
                        'penempatan_pkl_id' => $pengajuan->penempatan_pkl_id,
                        'tanggal' => $pengajuan->tanggal,
                        'status' => $pengajuan->jenis,
                        'keterangan' => 'Disetujui dari pengajuan ' . $pengajuan->jenis . ': ' . $pengajuan->alasan,
                    ]);
                }
            }

            return $pengajuan;
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Model $model, array $data): Model
    {
        return $this->pengajuanRepository->update($model, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Model $model): bool
    {
        if ($model->lampiran) {
            Storage::disk('public')->delete($model->lampiran);
        }
        return $this->pengajuanRepository->delete($model);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Model $model): bool
    {
        return $this->pengajuanRepository->restore($model);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Model $model): bool
    {
        if ($model->lampiran) {
            Storage::disk('public')->delete($model->lampiran);
        }
        return $this->pengajuanRepository->forceDelete($model);
    }
}
