<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AbsensiStatus;
use App\Models\Absensi;
use App\Models\PenempatanPKL;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use App\Services\Interfaces\AbsensiServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;
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
     * Stored in the existing keterangan field because the attendance status
     * column cannot be changed without a database migration.
     */
    private const SANGAT_TERLAMBAT = 'Sangat Terlambat';


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
                $data['tanggal'] = Carbon::today(config('app.timezone'))->toDateString();
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
                'jam_keluar' => $data['jam_keluar'] ?? $data['jam_pulang'] ?? null,
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
                'jam_keluar' => $data['jam_keluar'] ?? $data['jam_pulang'] ?? $absensi->jam_keluar,
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
     * - Auto-determine status from DUDI's scheduled start time and tolerance
     * - Handle base64 camera photo
     */
    public function checkIn(int $penempatanPklId, array $data): Absensi
    {
        $waktuPresensi = Carbon::now(config('app.timezone'));

        // Check if already checked in today
        $existing = $this->absensiRepository->findByPenempatanAndTanggal(
            $penempatanPklId,
            $waktuPresensi->toDateString(),
        );

        if ($existing !== null) {
            throw new \RuntimeException('Anda sudah melakukan Check In hari ini.');
        }

        // Validate GPS radius against DUDI location
        $this->validateGpsRadius($penempatanPklId, $data);

        // Get penempatan and DUDI's configured schedule.
        /** @var PenempatanPKL $penempatan */
        $penempatan = PenempatanPKL::with('dudi')->find($penempatanPklId);
        if ($penempatan === null || $penempatan->dudi === null) {
            throw new \RuntimeException('Jadwal masuk DUDI tidak ditemukan.');
        }

        $dudi = $penempatan->dudi;
        
        [$status, $keterangan] = $this->determineCheckInStatus(
            $waktuPresensi,
            $dudi->jam_masuk,
            $dudi->getEffectiveBatasTerlambat(),
            $dudi->getEffectiveBatasSangatTerlambat(),
        );

        $fotoPath = null;

        try {
            /** @var Absensi $absensi */
            $absensi = $this->transaction(function () use (
                $penempatanPklId,
                $data,
                $waktuPresensi,
                $status,
                $keterangan,
                &$fotoPath,
            ): Model {
                // Recheck immediately before saving to give a clear response
                // during normal repeated submissions.
                if ($this->absensiRepository->findByPenempatanAndTanggal(
                    $penempatanPklId,
                    $waktuPresensi->toDateString(),
                ) !== null) {
                    throw new \RuntimeException('Anda sudah melakukan Check In hari ini.');
                }

                // Store the photo only after all validation has passed. If the
                // database transaction fails, the catch block removes it again.
                $fotoPath = $this->storePresensiPhoto($data, 'foto_masuk', 'absensi/foto_masuk');

                return $this->absensiRepository->create([
                    'penempatan_pkl_id' => $penempatanPklId,
                    'tanggal' => $waktuPresensi->toDateString(),
                    'jam_masuk' => $waktuPresensi->format('H:i:s'),
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'lokasi_masuk' => $data['lokasi_masuk'] ?? null,
                    'foto_masuk' => $fotoPath,
                    'latitude_masuk' => $data['latitude'] ?? null,
                    'longitude_masuk' => $data['longitude'] ?? null,
                    'accuracy' => $data['accuracy'] ?? null,
                    'device' => $data['device'] ?? request()->userAgent(),
                ]);
            });
        } catch (QueryException $e) {
            $this->deletePhoto($fotoPath);

            // The unique index on (penempatan_pkl_id, tanggal) is the final
            // protection against simultaneous duplicate Check In submissions.
            throw new \RuntimeException('Anda sudah melakukan Check In hari ini.', previous: $e);
        } catch (\Throwable $e) {
            $this->deletePhoto($fotoPath);

            throw $e;
        }

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
        $fotoPath = null;

        try {
            /** @var Absensi $updated */
            $updated = $this->transaction(function () use ($penempatanPklId, $data, &$fotoPath): Model {
                // Locking makes two concurrent Check Out attempts run one at a
                // time, so the second attempt receives the correct message.
                $todayAbsensi = $this->absensiRepository->lockTodayByPenempatan($penempatanPklId);

                if ($todayAbsensi === null) {
                    throw new \RuntimeException('Silakan lakukan Check In terlebih dahulu.');
                }

                if ($todayAbsensi->jam_keluar !== null) {
                    throw new \RuntimeException('Anda sudah melakukan Check Out hari ini.');
                }

                $fotoPath = $this->storePresensiPhoto($data, 'foto_pulang', 'absensi/foto_pulang');

                return $this->absensiRepository->update($todayAbsensi, [
                    'jam_keluar' => Carbon::now(config('app.timezone'))->format('H:i:s'),
                    'lokasi_pulang' => $data['lokasi_pulang'] ?? null,
                    'foto_pulang' => $fotoPath,
                    'latitude_keluar' => $data['latitude'] ?? null,
                    'longitude_keluar' => $data['longitude'] ?? null,
                    'accuracy' => $data['accuracy'] ?? $todayAbsensi->accuracy,
                ]);
            });
        } catch (\Throwable $e) {
            $this->deletePhoto($fotoPath);

            throw $e;
        }

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
    public function getDudiAbsensiPaginated(int $dudiId, array $filters = []): LengthAwarePaginator
    {
        return $this->absensiRepository->getByDudiPaginated(
            dudiId: $dudiId,
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
        if (
            !array_key_exists('latitude', $data) ||
            !array_key_exists('longitude', $data) ||
            $data['latitude'] === null ||
            $data['longitude'] === null ||
            $data['latitude'] === '' ||
            $data['longitude'] === ''
        ) {
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

    private function determineCheckInStatus(CarbonInterface $waktuPresensi, mixed $jamMasukDudi, string $batasTerlambatStr, string $batasSangatTerlambatStr): array
    {
        $timezone = config('app.timezone');
        $jamMasuk = $jamMasukDudi instanceof \DateTimeInterface
            ? $jamMasukDudi->format('H:i:s')
            : Carbon::parse((string) $jamMasukDudi, $timezone)->format('H:i:s');

        // On time is <= batas terlambat
        $batasTerlambat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $waktuPresensi->toDateString() . ' ' . $batasTerlambatStr,
            $timezone,
        );
        
        $batasSangatTerlambat = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $waktuPresensi->toDateString() . ' ' . $batasSangatTerlambatStr,
            $timezone,
        );

        if ($waktuPresensi->lte($batasTerlambat)) {
            return [AbsensiStatus::HADIR->value, null];
        }

        if ($waktuPresensi->lte($batasSangatTerlambat)) {
            return [AbsensiStatus::TERLAMBAT->value, null];
        }

        return [AbsensiStatus::TERLAMBAT->value, self::SANGAT_TERLAMBAT];
    }

    /**
     * Store a camera or fallback-upload photo only after attendance validation.
     *
     * @param array<string, mixed> $data
     */
    private function storePresensiPhoto(array $data, string $fileField, string $directory): ?string
    {
        if (!empty($data['foto_base64']) && is_string($data['foto_base64'])) {
            return $this->saveBase64Photo($data['foto_base64'], $directory);
        }

        $file = $data[$fileField] ?? null;
        if (!$file instanceof UploadedFile) {
            return null;
        }

        $path = $file->store($directory, 'public');
        if ($path === false) {
            throw new \RuntimeException('Gagal menyimpan foto Presensi.');
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
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

        $imageData = base64_decode($base64Data, true);

        if ($imageData === false || @getimagesizefromstring($imageData) === false) {
            throw new \RuntimeException('Gagal mendekode foto.');
        }

        $filename = uniqid('absensi_', true) . '.jpg';
        $filePath = $path . '/' . $filename;

        if (!Storage::disk('public')->put($filePath, $imageData)) {
            throw new \RuntimeException('Gagal menyimpan foto Presensi.');
        }

        return $filePath;
    }

    /**
     * {@inheritDoc}
     */
    public function getRekapPresensi(int $penempatanPklId): array
    {
        $penempatan = \App\Models\PenempatanPKL::find($penempatanPklId);
        $emptyRekap = [
            'total_hari' => 0,
            'hari_berjalan' => 0,
            'hadir' => [],
            'terlambat' => [],
            'sangat_terlambat' => [],
            'izin' => [],
            'sakit' => [],
            'alpha' => [],
            'bolos' => [],
        ];

        if (!$penempatan) {
            return $emptyRekap;
        }

        $tanggalMulai = $penempatan->tanggal_mulai;
        $tanggalSelesai = $penempatan->tanggal_selesai;

        if (!$tanggalMulai || !$tanggalSelesai || $tanggalMulai->gt($tanggalSelesai)) {
            return $emptyRekap;
        }

        $timezone = config('app.timezone');
        $today = Carbon::today($timezone);
        $endCalculationDate = $today->lt($tanggalSelesai) ? clone $today : clone $tanggalSelesai;
        $startCalculationDate = clone $tanggalMulai;

        $dudi = $penempatan->dudi;
        
        $expectedDays = [];
        while ($startCalculationDate->lte($endCalculationDate)) {
            if ($dudi && $dudi->isHariOperasional($startCalculationDate)) {
                $expectedDays[] = $startCalculationDate->format('Y-m-d');
            } elseif (!$dudi && $startCalculationDate->isWeekday()) {
                $expectedDays[] = $startCalculationDate->format('Y-m-d');
            }
            $startCalculationDate = $startCalculationDate->addDay();
        }

        $absensis = \App\Models\Absensi::where('penempatan_pkl_id', $penempatanPklId)
            ->get()
            ->keyBy(function($item) {
                return $item->tanggal->format('Y-m-d');
            });

        $rekap = $emptyRekap;

        // Total hari PKL should be from tanggal_mulai to tanggal_selesai
        $totalStart = clone $tanggalMulai;
        $totalEnd = clone $tanggalSelesai;
        $totalExpectedDays = 0;
        while ($totalStart->lte($totalEnd)) {
            if ($dudi && $dudi->isHariOperasional($totalStart)) {
                $totalExpectedDays++;
            } elseif (!$dudi && $totalStart->isWeekday()) {
                $totalExpectedDays++;
            }
            $totalStart = $totalStart->addDay();
        }
        $rekap['total_hari'] = $totalExpectedDays;
        $rekap['hari_berjalan'] = count($expectedDays);

        foreach ($absensis as $date => $ab) {
            if ($ab->status === \App\Enums\AbsensiStatus::HADIR->value) {
                $rekap['hadir'][] = $ab;
            } elseif ($ab->status === \App\Enums\AbsensiStatus::TERLAMBAT->value) {
                if ($ab->keterangan === self::SANGAT_TERLAMBAT) {
                    $rekap['sangat_terlambat'][] = $ab;
                } else {
                    $rekap['terlambat'][] = $ab;
                }
            } elseif ($ab->status === \App\Enums\AbsensiStatus::IZIN->value) {
                $rekap['izin'][] = $ab;
            } elseif ($ab->status === \App\Enums\AbsensiStatus::SAKIT->value) {
                $rekap['sakit'][] = $ab;
            } elseif ($ab->status === \App\Enums\AbsensiStatus::ALPHA->value) {
                $rekap['alpha'][] = $ab;
            }
        }

        foreach ($expectedDays as $date) {
            if (!$absensis->has($date)) {
                if (Carbon::parse($date, $timezone)->lt($today)) {
                    $rekap['bolos'][] = $date;
                }
            }
        }

        return $rekap;
    }
}
