<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Models\Absensi;
use App\Models\PenempatanPKL;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service layer for Absensi business logic.
 *
 * Handles:
 * - CRUD operations
 * - Check In with GPS validation and radius check
 * - Check Out with GPS update
 * - Base64 photo handling
 * - Haversine distance calculation
 */
class AbsensiService extends Service implements AbsensiServiceInterface
{
    /**
     * Maximum allowed radius in meters (100m as per requirement).
     */
    private const MAX_RADIUS_METERS = 100;

    /**
     * Batas jam untuk deteksi keterlambatan (07:30).
     */
    private const BATAS_JAM_MASUK = '07:30:00';

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
            if (!isset($data['tanggal'])) {
                $data['tanggal'] = now()->toDateString();
            }

            // Handle base64 photo if present
            $fotoPath = null;
            if (!empty($data['foto_base64'])) {
                $fotoPath = $this->saveBase64Photo($data['foto_base64'], 'absensi/foto_masuk');
            }

            return $this->absensiRepository->create([
                'penempatan_pkl_id' => $data['penempatan_pkl_id'],
                'tanggal' => $data['tanggal'],
                'jam_masuk' => $data['jam_masuk'] ?? null,
                'jam_keluar' => $data['jam_keluar'] ?? null,
                'status' => $data['status'] ?? AbsensiStatus::HADIR->value,
                'lokasi_masuk' => $data['lokasi_masuk'] ?? null,
                'lokasi_pulang' => $data['lokasi_pulang'] ?? null,
                'foto_masuk' => $fotoPath ?? ($data['foto_masuk'] ?? null),
                'foto_pulang' => $data['foto_pulang'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'latitude_masuk' => $data['latitude_masuk'] ?? null,
                'longitude_masuk' => $data['longitude_masuk'] ?? null,
                'latitude_keluar' => $data['latitude_keluar'] ?? null,
                'longitude_keluar' => $data['longitude_keluar'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'device' => $data['device'] ?? request()->userAgent(),
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
            $updateData = [
                'penempatan_pkl_id' => $data['penempatan_pkl_id'] ?? $absensi->penempatan_pkl_id,
                'tanggal' => $data['tanggal'] ?? $absensi->tanggal,
                'jam_masuk' => $data['jam_masuk'] ?? $absensi->jam_masuk,
                'jam_keluar' => $data['jam_keluar'] ?? $absensi->jam_keluar,
                'status' => $data['status'] ?? $absensi->status,
                'lokasi_masuk' => $data['lokasi_masuk'] ?? $absensi->lokasi_masuk,
                'lokasi_pulang' => $data['lokasi_pulang'] ?? $absensi->lokasi_pulang,
                'keterangan' => $data['keterangan'] ?? $absensi->keterangan,
                'latitude_masuk' => $data['latitude_masuk'] ?? $absensi->latitude_masuk,
                'longitude_masuk' => $data['longitude_masuk'] ?? $absensi->longitude_masuk,
                'latitude_keluar' => $data['latitude_keluar'] ?? $absensi->latitude_keluar,
                'longitude_keluar' => $data['longitude_keluar'] ?? $absensi->longitude_keluar,
                'accuracy' => $data['accuracy'] ?? $absensi->accuracy,
                'device' => $data['device'] ?? $absensi->device,
            ];

            // Handle foto replacement
            if (!empty($data['foto_base64'])) {
                // Delete old photo
                if ($absensi->foto_masuk) {
                    Storage::disk('public')->delete($absensi->foto_masuk);
                }
                $updateData['foto_masuk'] = $this->saveBase64Photo($data['foto_base64'], 'absensi/foto_masuk');
            } elseif (isset($data['foto_masuk'])) {
                $updateData['foto_masuk'] = $data['foto_masuk'];
            }

            if (isset($data['foto_pulang'])) {
                $updateData['foto_pulang'] = $data['foto_pulang'];
            }

            return $this->absensiRepository->update($absensi, $updateData);
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
        // Delete associated photos
        if ($absensi->foto_masuk) {
            Storage::disk('public')->delete($absensi->foto_masuk);
        }
        if ($absensi->foto_pulang) {
            Storage::disk('public')->delete($absensi->foto_pulang);
        }

        return $this->absensiRepository->forceDelete($absensi);
    }

    /**
     * {@inheritDoc}
     *
     * Business logic:
     * - Check only one check-in per day
     * - Validate GPS radius (max 100m from DUDI)
     * - Auto-determine status: Hadir or Terlambat
     * - Handle base64 camera photo
     */
    public function checkIn(int $penempatanPklId, array $data): Absensi
    {
        // Check if already checked in today
        $existing = $this->absensiRepository->findTodayByPenempatan($penempatanPklId);

        if ($existing !== null) {
            throw new \RuntimeException('Anda sudah melakukan Check In hari ini.');
        }

        // Validate GPS radius against DUDI location
        $this->validateGpsRadius($penempatanPklId, $data);

        /** @var Absensi $absensi */
        $absensi = $this->transaction(function () use ($penempatanPklId, $data): Model {
            $tanggal = now()->toDateString();
            $jamMasuk = $data['jam_masuk'] ?? now()->format('H:i:s');

            // Auto-determine status based on time
            $status = AbsensiStatus::HADIR->value;
            $batasJam = Carbon::createFromTimeString(self::BATAS_JAM_MASUK);
            $jamMasukCarbon = Carbon::createFromFormat('H:i:s', $jamMasuk);

            if ($jamMasukCarbon !== false && $jamMasukCarbon->gt($batasJam)) {
                $status = AbsensiStatus::TERLAMBAT->value;
            }

            // Handle base64 photo from camera
            $fotoPath = null;
            if (!empty($data['foto_base64'])) {
                $fotoPath = $this->saveBase64Photo($data['foto_base64'], 'absensi/foto_masuk');
            } elseif (!empty($data['foto_masuk']) && is_string($data['foto_masuk'])) {
                $fotoPath = $data['foto_masuk'];
            }

            return $this->absensiRepository->create([
                'penempatan_pkl_id' => $penempatanPklId,
                'tanggal' => $tanggal,
                'jam_masuk' => $jamMasuk,
                'status' => $status,
                'lokasi_masuk' => $data['lokasi_masuk'] ?? null,
                'foto_masuk' => $fotoPath,
                'latitude_masuk' => $data['latitude'] ?? null,
                'longitude_masuk' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
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
     * - Handle base64 camera photo
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
            $updateData = [
                'jam_keluar' => $data['jam_keluar'] ?? now()->format('H:i:s'),
                'lokasi_pulang' => $data['lokasi_pulang'] ?? null,
                'latitude_keluar' => $data['latitude'] ?? null,
                'longitude_keluar' => $data['longitude'] ?? null,
                'accuracy' => $data['accuracy'] ?? $todayAbsensi->accuracy,
            ];

            // Handle base64 photo from camera
            if (!empty($data['foto_base64'])) {
                $updateData['foto_pulang'] = $this->saveBase64Photo($data['foto_base64'], 'absensi/foto_pulang');
            } elseif (!empty($data['foto_pulang']) && is_string($data['foto_pulang'])) {
                $updateData['foto_pulang'] = $data['foto_pulang'];
            }

            return $this->absensiRepository->update($todayAbsensi, $updateData);
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

    /**
     * Validate GPS radius using Haversine formula.
     *
     * @param int $penempatanPklId The penempatan PKL ID
     * @param array<string, mixed> $data The request data containing latitude/longitude
     * @throws \RuntimeException If outside radius or GPS not available
     */
    private function validateGpsRadius(int $penempatanPklId, array $data): void
    {
        // If no GPS data provided, skip validation (allow Check In)
        if (empty($data['latitude']) || empty($data['longitude'])) {
            return;
        }

        // Get DUDI location from the penempatan
        /** @var PenempatanPKL|null $penempatan */
        $penempatan = PenempatanPKL::with('dudi')->find($penempatanPklId);

        if ($penempatan === null || $penempatan->dudi === null) {
            return; // Cannot validate if DUDI not found
        }

        $dudiLat = (float) $penempatan->dudi->latitude;
        $dudiLng = (float) $penempatan->dudi->longitude;

        // If DUDI has no coordinates, skip validation
        if ($dudiLat === 0.0 && $dudiLng === 0.0) {
            return;
        }

        $userLat = (float) $data['latitude'];
        $userLng = (float) $data['longitude'];

        // Calculate distance using Haversine formula
        $distance = $this->haversineDistance($userLat, $userLng, $dudiLat, $dudiLng);

        // Check if within radius
        if ($distance > self::MAX_RADIUS_METERS) {
            throw new \RuntimeException(
                'Anda berada di luar area PKL. Jarak Anda: ' . 
                number_format($distance, 0, ',', '.') . 
                ' meter dari lokasi DUDI (maksimal ' . 
                self::MAX_RADIUS_METERS . ' meter).'
            );
        }
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula.
     *
     * @param float $lat1 User latitude
     * @param float $lon1 User longitude
     * @param float $lat2 DUDI latitude
     * @param float $lon2 DUDI longitude
     * @return float Distance in meters
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Save a base64 encoded photo to storage.
     *
     * @param string $base64Data The base64 encoded image data
     * @param string $path Storage path prefix
     * @return string The stored file path
     */
    private function saveBase64Photo(string $base64Data, string $path = 'absensi'): string
    {
        // Remove data:image/jpeg;base64, prefix if present
        if (str_contains($base64Data, 'base64,')) {
            $base64Data = substr($base64Data, strpos($base64Data, 'base64,') + 7);
        }

        $imageData = base64_decode($base64Data);

        if ($imageData === false) {
            throw new \RuntimeException('Gagal mendekode foto.');
        }

        $filename = uniqid('absensi_', true) . '.jpg';
        $filePath = $path . '/' . $filename;

        Storage::disk('public')->put($filePath, $imageData);

        return $filePath;
    }
}
