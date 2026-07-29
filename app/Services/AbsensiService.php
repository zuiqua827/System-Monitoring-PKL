<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Models\Absensi;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for Absensi business logic.
 */
class AbsensiService extends Service implements AbsensiServiceInterface
{
    public function __construct(
        private readonly AbsensiRepositoryInterface $absensiRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return $this->absensiRepository->search(
            keyword: $filters['search'] ?? null,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: isset($filters['periode_id']) ? (int) $filters['periode_id'] : null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Absensi
    {
        /** @var Absensi|null $absensi */
        $absensi = $this->absensiRepository->find($id);

        if ($absensi === null) {
            throw new ModelNotFoundException('Absensi tidak ditemukan.');
        }

        return $absensi;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Absensi
    {
        /** @var Absensi $absensi */
        $absensi = $this->transaction(function () use ($data): Model {
            // Auto-set tanggal if not provided
            if (!isset($data['tanggal'])) {
                $data['tanggal'] = now()->toDateString();
            }

            return $this->absensiRepository->create([
                'penempatan_pkl_id' => $data['penempatan_pkl_id'],
                'tanggal' => $data['tanggal'],
                'jam_masuk' => $data['jam_masuk'] ?? null,
                'jam_keluar' => $data['jam_keluar'] ?? null,
                'status' => $data['status'] ?? AbsensiStatus::HADIR->value,
                'lokasi_masuk' => $data['lokasi_masuk'] ?? null,
                'lokasi_pulang' => $data['lokasi_pulang'] ?? null,
                'foto_masuk' => $data['foto_masuk'] ?? null,
                'foto_pulang' => $data['foto_pulang'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'latitude_masuk' => $data['latitude_masuk'] ?? null,
                'longitude_masuk' => $data['longitude_masuk'] ?? null,
                'latitude_keluar' => $data['latitude_keluar'] ?? null,
                'longitude_keluar' => $data['longitude_keluar'] ?? null,
                'device' => $data['device'] ?? null,
            ]);
        });

        return $absensi;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Absensi $absensi, array $data): Absensi
    {
        /** @var Absensi $updated */
        $updated = $this->transaction(function () use ($absensi, $data): Model {
            return $this->absensiRepository->update($absensi, [
                'penempatan_pkl_id' => $data['penempatan_pkl_id'] ?? $absensi->penempatan_pkl_id,
                'tanggal' => $data['tanggal'] ?? $absensi->tanggal,
                'jam_masuk' => $data['jam_masuk'] ?? $absensi->jam_masuk,
                'jam_keluar' => $data['jam_keluar'] ?? $absensi->jam_keluar,
                'status' => $data['status'] ?? $absensi->status,
                'lokasi_masuk' => $data['lokasi_masuk'] ?? $absensi->lokasi_masuk,
                'lokasi_pulang' => $data['lokasi_pulang'] ?? $absensi->lokasi_pulang,
                'foto_masuk' => $data['foto_masuk'] ?? $absensi->foto_masuk,
                'foto_pulang' => $data['foto_pulang'] ?? $absensi->foto_pulang,
                'keterangan' => $data['keterangan'] ?? $absensi->keterangan,
                'latitude_masuk' => $data['latitude_masuk'] ?? $absensi->latitude_masuk,
                'longitude_masuk' => $data['longitude_masuk'] ?? $absensi->longitude_masuk,
                'latitude_keluar' => $data['latitude_keluar'] ?? $absensi->latitude_keluar,
                'longitude_keluar' => $data['longitude_keluar'] ?? $absensi->longitude_keluar,
                'device' => $data['device'] ?? $absensi->device,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(Absensi $absensi): bool
    {
        return $this->absensiRepository->delete($absensi);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Absensi $absensi): bool
    {
        return $this->absensiRepository->restore($absensi);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Absensi $absensi): bool
    {
        return $this->absensiRepository->forceDelete($absensi);
    }

    /**
     * {@inheritDoc}
     *
     * Business logic:
     * - Siswa can only check in once per day
     * - Auto-determine status: if jam_masuk > batas jam (e.g., 07:30), set status to 'terlambat'
     */
    public function checkIn(int $penempatanPklId, array $data): Absensi
    {
        // Check if already checked in today
        $existing = $this->absensiRepository->findTodayByPenempatan($penempatanPklId);

        if ($existing !== null) {
            throw new \RuntimeException('Anda sudah melakukan Check In hari ini.');
        }

        /** @var Absensi $absensi */
        $absensi = $this->transaction(function () use ($penempatanPklId, $data): Model {
            $tanggal = now()->toDateString();
            $jamMasuk = $data['jam_masuk'] ?? now()->format('H:i:s');

            // Auto-determine status based on time
            $status = AbsensiStatus::HADIR->value;
            $batasJam = Carbon::createFromTimeString('07:30:00');
            $jamMasukCarbon = Carbon::createFromFormat('H:i:s', $jamMasuk);

            if ($jamMasukCarbon !== false && $jamMasukCarbon->gt($batasJam)) {
                $status = AbsensiStatus::TERLAMBAT->value;
            }

            return $this->absensiRepository->create([
                'penempatan_pkl_id' => $penempatanPklId,
                'tanggal' => $tanggal,
                'jam_masuk' => $jamMasuk,
                'status' => $status,
                'lokasi_masuk' => $data['lokasi_masuk'] ?? null,
                'foto_masuk' => $data['foto_masuk'] ?? null,
                'latitude_masuk' => $data['latitude_masuk'] ?? null,
                'longitude_masuk' => $data['longitude_masuk'] ?? null,
                'device' => $data['device'] ?? request()->userAgent(),
            ]);
        });

        return $absensi;
    }

    /**
     * {@inheritDoc}
     *
     * Business logic:
     * - Can only check out if already checked in
     * - Cannot check out twice
     */
    public function checkOut(int $penempatanPklId, array $data): Absensi
    {
        $todayAbsensi = $this->absensiRepository->findTodayByPenempatan($penempatanPklId);

        if ($todayAbsensi === null) {
            throw new \RuntimeException('Anda belum melakukan Check In hari ini.');
        }

        if ($todayAbsensi->jam_keluar !== null) {
            throw new \RuntimeException('Anda sudah melakukan Check Out hari ini.');
        }

        /** @var Absensi $updated */
        $updated = $this->transaction(function () use ($todayAbsensi, $data): Model {
            return $this->absensiRepository->update($todayAbsensi, [
                'jam_keluar' => $data['jam_keluar'] ?? now()->format('H:i:s'),
                'lokasi_pulang' => $data['lokasi_pulang'] ?? null,
                'foto_pulang' => $data['foto_pulang'] ?? null,
                'latitude_keluar' => $data['latitude_keluar'] ?? null,
                'longitude_keluar' => $data['longitude_keluar'] ?? null,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function getTodayAbsensi(int $penempatanPklId): ?Absensi
    {
        return $this->absensiRepository->findTodayByPenempatan($penempatanPklId);
    }

    /**
     * {@inheritDoc}
     */
    public function getSiswaAbsensiPaginated(int $siswaId, array $filters = []): LengthAwarePaginator
    {
        return $this->absensiRepository->getBySiswaPaginated(
            siswaId: $siswaId,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getGuruAbsensiPaginated(int $guruId, array $filters = []): LengthAwarePaginator
    {
        return $this->absensiRepository->getByGuruPaginated(
            guruId: $guruId,
            keyword: $filters['search'] ?? null,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: isset($filters['periode_id']) ? (int) $filters['periode_id'] : null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateAbsensi(Absensi $absensi, array $data): Absensi
    {
        /** @var Absensi $updated */
        $updated = $this->transaction(function () use ($absensi, $data): Model {
            return $this->absensiRepository->update($absensi, [
                'status' => $data['status'] ?? $absensi->status,
                'keterangan' => $data['keterangan'] ?? $absensi->keterangan,
            ]);
        });

        return $updated;
    }
}

