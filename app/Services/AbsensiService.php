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

    /**
     * Determine check-in status (hadir/terlambat) and keterangan ('Sangat Terlambat' or null).
     *
     * Rules:
     * - waktuPresensi <= jamMasuk -> Hadir (Tepat Waktu)
     * - waktuPresensi >= batasSangatTerlambat -> Terlambat (Sangat Terlambat)
     * - jamMasuk < waktuPresensi < batasSangatTerlambat -> Terlambat (Terlambat)
     */
    public function determineCheckInStatus(
        CarbonInterface $waktuPresensi,
        mixed $jamMasukDudi,
        ?string $batasTerlambatStr = null,
        ?string $batasSangatTerlambatStr = null
    ): array {
        $timezone = config('app.timezone');
        $jamMasuk = $jamMasukDudi instanceof \DateTimeInterface
            ? $jamMasukDudi->format('H:i:s')
            : Carbon::parse((string) $jamMasukDudi, $timezone)->format('H:i:s');

        $jamMasukCarbon = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $waktuPresensi->toDateString() . ' ' . $jamMasuk,
            $timezone,
        );

        $batasSangatTerlambatStr = $batasSangatTerlambatStr ?: '09:00:00';
        $batasSangatTerlambatCarbon = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $waktuPresensi->toDateString() . ' ' . $batasSangatTerlambatStr,
            $timezone,
        );

        // Safeguard if batas_sangat_terlambat is set earlier than or equal to jam_masuk
        if ($batasSangatTerlambatCarbon->lte($jamMasukCarbon)) {
            $batasSangatTerlambatCarbon = $jamMasukCarbon->copy()->addMinutes(60);
        }

        // 1. Tepat Waktu: jika check_in_time <= jam_masuk
        if ($waktuPresensi->lte($jamMasukCarbon)) {
            return [AbsensiStatus::HADIR->value, null];
        }

        // 2. Sangat Terlambat: jika check_in_time >= batas_sangat_terlambat
        if ($waktuPresensi->gte($batasSangatTerlambatCarbon)) {
            return [AbsensiStatus::TERLAMBAT->value, self::SANGAT_TERLAMBAT];
        }

        // 3. Terlambat: jika setelah jam masuk dan sebelum batas_sangat_terlambat
        return [AbsensiStatus::TERLAMBAT->value, null];
    }

    /**
     * Determine check-out status based on DUDI's scheduled departure time.
     *
     * Rules:
     * - waktuCheckOut === null -> Belum Check Out
     * - waktuCheckOut >= jamPulang -> Tepat Waktu
     * - waktuCheckOut < jamPulang -> Pulang Sebelum Waktu
     *
     * @return array{status: string, key: string, badge_color: string}
     */
    public function determineCheckOutStatus(
        ?CarbonInterface $waktuCheckOut,
        mixed $jamPulangDudi,
        ?string $dateString = null
    ): array {
        if ($waktuCheckOut === null) {
            return [
                'status' => 'Belum Check Out',
                'key' => 'belum_checkout',
                'badge_color' => 'bg-blue-100 text-blue-800',
            ];
        }

        $timezone = config('app.timezone');
        $date = $dateString ?? $waktuCheckOut->toDateString();

        $jamPulang = $jamPulangDudi instanceof \DateTimeInterface
            ? $jamPulangDudi->format('H:i:s')
            : Carbon::parse((string) $jamPulangDudi, $timezone)->format('H:i:s');

        $jamPulangCarbon = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $date . ' ' . $jamPulang,
            $timezone,
        );

        // Jika check_out >= jam_pulang -> Tepat Waktu
        if ($waktuCheckOut->gte($jamPulangCarbon)) {
            return [
                'status' => 'Tepat Waktu',
                'key' => 'tepat_waktu',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
            ];
        }

        // Jika check_out < jam_pulang -> Pulang Sebelum Waktu
        return [
            'status' => 'Pulang Sebelum Waktu',
            'key' => 'pulang_sebelum_waktu',
            'badge_color' => 'bg-amber-100 text-amber-800',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getPresensiStatusDetails(?Absensi $absensi, ?\App\Models\Dudi $dudi = null): array
    {
        $timezone = config('app.timezone');
        $today = Carbon::today($timezone)->toDateString();
        $dateString = $absensi?->tanggal
            ? ($absensi->tanggal instanceof \DateTimeInterface ? $absensi->tanggal->format('Y-m-d') : (string) $absensi->tanggal)
            : $today;

        // Resolve DUDI if not explicitly passed
        $dudi = $dudi ?? $absensi?->penempatanPKL?->dudi ?? $absensi?->penempatan?->dudi;

        $jamMasukDudi = $dudi?->jam_masuk ?? '08:00:00';
        $jamPulangDudi = $dudi?->jam_pulang ?? '16:00:00';
        $batasSangatTerlambat = $dudi?->getEffectiveBatasSangatTerlambat() ?? '09:00:00';
        $batasTerlambat = $dudi?->getEffectiveBatasTerlambat() ?? '08:15:00';

        // Check In Details
        $checkInCarbon = $absensi?->jam_masuk
            ? $this->parseTimeToCarbon($absensi->jam_masuk, $dateString, $timezone)
            : null;

        $checkInStatus = 'Belum Check In';
        $checkInColor = 'bg-amber-100 text-amber-800';
        $checkInTimeFormatted = $checkInCarbon ? $checkInCarbon->format('H:i') : null;

        if ($checkInCarbon !== null) {
            // Handle non-attendance enum cases if present (izin/sakit/alpha)
            if ($absensi?->status === AbsensiStatus::IZIN->value) {
                $checkInStatus = 'Izin';
                $checkInColor = 'bg-blue-100 text-blue-800';
            } elseif ($absensi?->status === AbsensiStatus::SAKIT->value) {
                $checkInStatus = 'Sakit';
                $checkInColor = 'bg-orange-100 text-orange-800';
            } elseif ($absensi?->status === AbsensiStatus::ALPHA->value) {
                $checkInStatus = 'Tidak Hadir';
                $checkInColor = 'bg-red-100 text-red-800';
            } else {
                [$statusVal, $keteranganVal] = $this->determineCheckInStatus(
                    $checkInCarbon,
                    $jamMasukDudi,
                    $batasTerlambat,
                    $batasSangatTerlambat,
                );

                if ($statusVal === AbsensiStatus::HADIR->value) {
                    $checkInStatus = 'Tepat Waktu';
                    $checkInColor = 'bg-emerald-100 text-emerald-800';
                } elseif ($keteranganVal === self::SANGAT_TERLAMBAT || $absensi?->keterangan === self::SANGAT_TERLAMBAT) {
                    $checkInStatus = 'Sangat Terlambat';
                    $checkInColor = 'bg-red-100 text-red-800';
                } else {
                    $checkInStatus = 'Terlambat';
                    $checkInColor = 'bg-amber-100 text-amber-800';
                }
            }
        }

        // Check Out Details
        $checkOutCarbon = $absensi?->jam_keluar
            ? $this->parseTimeToCarbon($absensi->jam_keluar, $dateString, $timezone)
            : null;

        $checkOutTimeFormatted = $checkOutCarbon ? $checkOutCarbon->format('H:i') : null;
        $checkOutResult = $this->determineCheckOutStatus($checkOutCarbon, $jamPulangDudi, $dateString);

        return [
            'check_in' => [
                'time' => $checkInTimeFormatted,
                'status' => $checkInStatus,
                'badge_color' => $checkInColor,
                'is_valid' => $checkInCarbon !== null,
            ],
            'check_out' => [
                'time' => $checkOutTimeFormatted,
                'status' => $checkOutResult['status'],
                'badge_color' => $checkOutResult['badge_color'],
                'is_valid' => $checkOutCarbon !== null,
            ],
        ];
    }

    /**
     * Safely parse mixed time format to a Carbon instance for accurate comparisons.
     */
    private function parseTimeToCarbon(mixed $time, string $dateString, string $timezone): ?Carbon
    {
        if ($time === null || $time === '') {
            return null;
        }

        if ($time instanceof CarbonInterface) {
            return Carbon::parse($time->format('Y-m-d H:i:s.u'), $timezone);
        }

        if ($time instanceof \DateTimeInterface) {
            return Carbon::parse($time->format('Y-m-d H:i:s.u'), $timezone);
        }

        $str = trim((string) $time);
        if (preg_match('/^\d{2}:\d{2}$/', $str)) {
            $str .= ':00';
        }

        try {
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $str)) {
                return Carbon::createFromFormat('Y-m-d H:i:s', $dateString . ' ' . $str, $timezone);
            }

            return Carbon::parse($str, $timezone);
        } catch (\Throwable) {
            return null;
        }
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
    public function getHariWajibPKL(PenempatanPKL $penempatan, $startDate = null, $endDate = null): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $formalMulai = $penempatan->tanggal_mulai ?? $penempatan->periodePKL?->tanggal_mulai;
        $formalSelesai = $penempatan->tanggal_selesai ?? $penempatan->periodePKL?->tanggal_selesai;

        if (!$formalMulai && !$startDate) {
            return [];
        }

        $effectiveMulai = $formalMulai
            ? Carbon::parse($formalMulai, $timezone)->startOfDay()
            : Carbon::parse($startDate, $timezone)->startOfDay();

        $effectiveSelesai = $formalSelesai
            ? Carbon::parse($formalSelesai, $timezone)->endOfDay()
            : ($endDate ? Carbon::parse($endDate, $timezone)->endOfDay() : (clone $effectiveMulai)->addMonths(3)->endOfDay());

        if ($startDate !== null) {
            $rangeStart = Carbon::parse($startDate, $timezone)->startOfDay();
            if ($rangeStart->gt($effectiveMulai)) {
                $effectiveMulai = $rangeStart;
            }
        }

        if ($endDate !== null) {
            $rangeEnd = Carbon::parse($endDate, $timezone)->endOfDay();
            if ($rangeEnd->lt($effectiveSelesai)) {
                $effectiveSelesai = $rangeEnd;
            }
        }

        if ($effectiveMulai->gt($effectiveSelesai)) {
            return [];
        }

        $dudi = $penempatan->dudi;
        $cur = clone $effectiveMulai;
        $wajibDates = [];

        while ($cur->lte($effectiveSelesai)) {
            $isOp = $dudi ? $dudi->isHariOperasional($cur) : $cur->isWeekday();
            if ($isOp) {
                $wajibDates[] = $cur->format('Y-m-d');
            }
            $cur->addDay();
        }

        return $wajibDates;
    }

    // ============================================================
    // SINGLE SOURCE OF TRUTH (SSOT) PERHITUNGAN ABSENSI
    // ============================================================
    // Perhitungan absensi dipusatkan pada service ini agar
    // seluruh fitur menggunakan sumber perhitungan yang konsisten,
    // seperti rekap bulanan, laporan PKL, dan penilaian.
    //
    // Alur Kerja:
    // 1. Menentukan rentang tanggal efektif PKL berdasarkan hari operasional DUDI.
    // 2. Mengambil record absensi riil (hadir, terlambat, sangat terlambat).
    // 3. Mengambil data pengajuan ketidakhadiran yang disetujui DUDI (izin, sakit).
    // 4. Hari kerja yang terlewat tanpa check-in/pengajuan dihitung sebagai 'bolos/alpha'.
    // ============================================================

    /**
     * {@inheritDoc}
     */
    public function calculateAttendance(PenempatanPKL|int $penempatan, $startDate = null, $endDate = null): array
    {

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
            'by_date' => [],
            'status' => 'periode_tidak_valid',
            'summary' => [
                'hadir' => 0,
                'terlambat' => 0,
                'sangat_terlambat' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alfa' => 0,
                'alpha' => 0,
                'total_hadir' => 0,
                'total_hari' => 0,
                'total_hari_wajib' => 0,
                'hari_berjalan' => 0,
                'hadir_pct' => 0.0,
                'sakit_pct' => 0.0,
                'izin_pct' => 0.0,
                'alpha_pct' => 0.0,
            ],
        ];

        if (!$penempatan instanceof PenempatanPKL) {
            $penempatan = PenempatanPKL::with(['dudi', 'periodePKL', 'siswa.kelas.jurusan', 'guru'])->find($penempatan);
        }

        if (!$penempatan || $penempatan->status === 'dibatalkan') {
            return $emptyRekap;
        }

        $timezone = config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::today($timezone);
        $yesterday = (clone $today)->subDay();

        $formalMulai = $penempatan->tanggal_mulai ?? $penempatan->periodePKL?->tanggal_mulai;
        $formalSelesai = $penempatan->tanggal_selesai ?? $penempatan->periodePKL?->tanggal_selesai;

        $absensiQuery = Absensi::where('penempatan_pkl_id', $penempatan->id)
            ->orderBy('tanggal', 'asc');

        if ($startDate !== null) {
            $absensiQuery->whereDate('tanggal', '>=', Carbon::parse($startDate, $timezone)->format('Y-m-d'));
        }
        if ($endDate !== null) {
            $absensiQuery->whereDate('tanggal', '<=', Carbon::parse($endDate, $timezone)->format('Y-m-d'));
        }

        $absensis = $absensiQuery->get()->keyBy(function ($item) {
            return $item->tanggal ? $item->tanggal->format('Y-m-d') : '';
        });

        $pengajuanQuery = \App\Models\PengajuanKetidakhadiran::where('penempatan_pkl_id', $penempatan->id)
            ->where('status', 'disetujui');

        if ($startDate !== null) {
            $pengajuanQuery->whereDate('tanggal', '>=', Carbon::parse($startDate, $timezone)->format('Y-m-d'));
        }
        if ($endDate !== null) {
            $pengajuanQuery->whereDate('tanggal', '<=', Carbon::parse($endDate, $timezone)->format('Y-m-d'));
        }

        $pengajuan = $pengajuanQuery->get()->keyBy(function ($item) {
            return $item->tanggal ? $item->tanggal->format('Y-m-d') : '';
        });

        // Determine effective overall start and end dates
        $earliestAbsensi = $absensis->keys()->filter()->min();
        $latestAbsensi = $absensis->keys()->filter()->max();

        if ($earliestAbsensi !== null) {
            $earliestCarbon = Carbon::parse($earliestAbsensi, $timezone)->startOfDay();
            if (!$formalMulai || Carbon::parse($formalMulai, $timezone)->gt($today) || $earliestCarbon->lt(Carbon::parse($formalMulai, $timezone))) {
                $effectiveMulai = $earliestCarbon;
            } else {
                $effectiveMulai = Carbon::parse($formalMulai, $timezone)->startOfDay();
            }
        } else {
            $effectiveMulai = $formalMulai ? Carbon::parse($formalMulai, $timezone)->startOfDay() : clone $today;
        }

        if ($latestAbsensi !== null) {
            $latestCarbon = Carbon::parse($latestAbsensi, $timezone)->endOfDay();
            if ($formalSelesai && $latestCarbon->gt(Carbon::parse($formalSelesai, $timezone))) {
                $effectiveSelesai = $latestCarbon;
            } else {
                $effectiveSelesai = $formalSelesai ? Carbon::parse($formalSelesai, $timezone)->endOfDay() : (clone $today)->addMonths(3)->endOfDay();
            }
        } else {
            $effectiveSelesai = $formalSelesai ? Carbon::parse($formalSelesai, $timezone)->endOfDay() : (clone $today)->addMonths(3)->endOfDay();
        }

        // Window clamping based on optional $startDate and $endDate
        $windowStart = clone $effectiveMulai;
        if ($startDate !== null) {
            $filterStart = Carbon::parse($startDate, $timezone)->startOfDay();
            if ($filterStart->gt($windowStart)) {
                $windowStart = $filterStart;
            }
        }

        $windowEnd = clone $effectiveSelesai;
        if ($endDate !== null) {
            $filterEnd = Carbon::parse($endDate, $timezone)->endOfDay();
            if ($filterEnd->lt($windowEnd)) {
                $windowEnd = $filterEnd;
            }
        }

        $dudi = $penempatan->dudi;

        $hadirList = [];
        $terlambatList = [];
        $sangatTerlambatList = [];
        $izinList = [];
        $sakitList = [];
        $alphaList = [];
        $bolosDates = [];
        $byDate = [];

        // 1. First, process all actual recorded attendance in the database within window
        foreach ($absensis as $dateStr => $ab) {
            $abDate = Carbon::parse($dateStr, $timezone);
            if ($abDate->lt($windowStart) || $abDate->gt($windowEnd)) {
                continue;
            }

            $ab->setRelation('penempatanPKL', $penempatan);

            if ($ab->status === AbsensiStatus::HADIR->value) {
                $hadirList[] = $ab;
                $byDate[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'hadir',
                    'status_label' => 'Hadir',
                    'absensi' => $ab,
                    'pengajuan' => null,
                    'is_virtual' => false,
                ];
            } elseif ($ab->status === AbsensiStatus::TERLAMBAT->value) {
                if ($ab->keterangan === self::SANGAT_TERLAMBAT) {
                    $sangatTerlambatList[] = $ab;
                } else {
                    $terlambatList[] = $ab;
                }
                $byDate[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'terlambat',
                    'status_label' => 'Terlambat',
                    'absensi' => $ab,
                    'pengajuan' => null,
                    'is_virtual' => false,
                ];
            } elseif ($ab->status === AbsensiStatus::IZIN->value) {
                $izinList[] = $ab;
                $byDate[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'izin',
                    'status_label' => 'Izin',
                    'absensi' => $ab,
                    'pengajuan' => null,
                    'is_virtual' => false,
                ];
            } elseif ($ab->status === AbsensiStatus::SAKIT->value) {
                $sakitList[] = $ab;
                $byDate[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'sakit',
                    'status_label' => 'Sakit',
                    'absensi' => $ab,
                    'pengajuan' => null,
                    'is_virtual' => false,
                ];
            } elseif ($ab->status === AbsensiStatus::ALPHA->value) {
                $alphaList[] = $ab;
                if (!in_array($dateStr, $bolosDates, true)) {
                    $bolosDates[] = $dateStr;
                }
                $byDate[$dateStr] = [
                    'date' => $dateStr,
                    'status' => 'alpha',
                    'status_label' => 'Alfa',
                    'absensi' => $ab,
                    'pengajuan' => null,
                    'is_virtual' => false,
                ];
            }
        }

        // 2. Identify unrecorded past operational days (Concluded days only: <= min(yesterday, windowEnd))
        if ($windowStart->lte($windowEnd)) {
            $pastLimit = $yesterday->lt($windowEnd) ? clone $yesterday : clone $windowEnd;

            if ($windowStart->lte($pastLimit)) {
                $cur = clone $windowStart;
                while ($cur->lte($pastLimit)) {
                    $isOp = $dudi ? $dudi->isHariOperasional($cur) : $cur->isWeekday();
                    $dStr = $cur->format('Y-m-d');

                    if ($isOp && !isset($byDate[$dStr])) {
                        if ($pengajuan->has($dStr)) {
                            $p = $pengajuan->get($dStr);
                            $jenis = $p->jenis === 'sakit' ? 'sakit' : 'izin';

                            $virtualLeave = new Absensi([
                                'penempatan_pkl_id' => $penempatan->id,
                                'tanggal' => $dStr,
                                'status' => $jenis,
                                'jam_masuk' => null,
                                'jam_keluar' => null,
                                'keterangan' => 'Pengajuan Disetujui: ' . ($p->keterangan ?? ucfirst($jenis)),
                            ]);
                            $virtualLeave->setRelation('penempatanPKL', $penempatan);

                            if ($jenis === 'sakit') {
                                $sakitList[] = $virtualLeave;
                            } else {
                                $izinList[] = $virtualLeave;
                            }

                            $byDate[$dStr] = [
                                'date' => $dStr,
                                'status' => $jenis,
                                'status_label' => ucfirst($jenis),
                                'absensi' => $virtualLeave,
                                'pengajuan' => $p,
                                'is_virtual' => true,
                            ];
                        } else {
                            // Concluded operational day without check-in or approved leave => ALFA / BOLOS
                            $virtualAlfa = new Absensi([
                                'penempatan_pkl_id' => $penempatan->id,
                                'tanggal' => $dStr,
                                'status' => 'alpha',
                                'jam_masuk' => null,
                                'jam_keluar' => null,
                                'keterangan' => 'Tanpa Keterangan (Alfa)',
                            ]);
                            $virtualAlfa->setRelation('penempatanPKL', $penempatan);

                            $alphaList[] = $virtualAlfa;
                            if (!in_array($dStr, $bolosDates, true)) {
                                $bolosDates[] = $dStr;
                            }

                            $byDate[$dStr] = [
                                'date' => $dStr,
                                'status' => 'alpha',
                                'status_label' => 'Alfa',
                                'absensi' => $virtualAlfa,
                                'pengajuan' => null,
                                'is_virtual' => true,
                            ];
                        }
                    }
                    $cur->addDay();
                }
            }
        }

        // 3. Process approved leaves that fall on ongoing or future operational days in window
        foreach ($pengajuan as $dStr => $p) {
            if (!isset($byDate[$dStr])) {
                $pDate = Carbon::parse($dStr, $timezone);
                if ($pDate->gte($windowStart) && $pDate->lte($windowEnd)) {
                    $jenis = $p->jenis === 'sakit' ? 'sakit' : 'izin';
                    $virtualLeave = new Absensi([
                        'penempatan_pkl_id' => $penempatan->id,
                        'tanggal' => $dStr,
                        'status' => $jenis,
                        'jam_masuk' => null,
                        'jam_keluar' => null,
                        'keterangan' => 'Pengajuan Disetujui: ' . ($p->keterangan ?? ucfirst($jenis)),
                    ]);
                    $virtualLeave->setRelation('penempatanPKL', $penempatan);

                    if ($jenis === 'sakit') {
                        $sakitList[] = $virtualLeave;
                    } else {
                        $izinList[] = $virtualLeave;
                    }

                    $byDate[$dStr] = [
                        'date' => $dStr,
                        'status' => $jenis,
                        'status_label' => ucfirst($jenis),
                        'absensi' => $virtualLeave,
                        'pengajuan' => $p,
                        'is_virtual' => true,
                    ];
                }
            }
        }

        // 4. Calculate total operational days in window & elapsed days
        $totalHariWajib = 0;
        $hariBerjalan = 0;

        if ($windowStart->lte($windowEnd)) {
            $cur = clone $windowStart;
            while ($cur->lte($windowEnd)) {
                $isOp = $dudi ? $dudi->isHariOperasional($cur) : $cur->isWeekday();
                if ($isOp) {
                    $totalHariWajib++;
                    if ($cur->lte($today)) {
                        $hariBerjalan++;
                    }
                }
                $cur->addDay();
            }
        }

        $hadirCount = count($hadirList);
        $terlambatRegulerCount = count($terlambatList);
        $sangatTerlambatCount = count($sangatTerlambatList);
        $totalTerlambatCount = $terlambatRegulerCount + $sangatTerlambatCount;
        $izinCount = count($izinList);
        $sakitCount = count($sakitList);
        $alphaCount = count($bolosDates);
        $totalKehadiran = $hadirCount + $totalTerlambatCount;
        $totalEvaluated = $totalKehadiran + $izinCount + $sakitCount + $alphaCount;

        return [
            'status' => 'valid',
            'summary' => [
                'hadir' => $hadirCount,
                'terlambat' => $totalTerlambatCount,
                'terlambat_reguler' => $terlambatRegulerCount,
                'sangat_terlambat' => $sangatTerlambatCount,
                'izin' => $izinCount,
                'sakit' => $sakitCount,
                'alfa' => $alphaCount,
                'alpha' => $alphaCount,
                'total_hadir' => $totalKehadiran,
                'total_hari' => $totalEvaluated,
                'total_hari_wajib' => $totalHariWajib,
                'hari_berjalan' => $hariBerjalan,
                'hadir_pct' => $totalEvaluated > 0 ? round(($totalKehadiran / $totalEvaluated) * 100, 1) : 0.0,
                'sakit_pct' => $totalEvaluated > 0 ? round(($sakitCount / $totalEvaluated) * 100, 1) : 0.0,
                'izin_pct' => $totalEvaluated > 0 ? round(($izinCount / $totalEvaluated) * 100, 1) : 0.0,
                'alpha_pct' => $totalEvaluated > 0 ? round(($alphaCount / $totalEvaluated) * 100, 1) : 0.0,
            ],
            'by_date' => $byDate,
            'hadir' => $hadirList,
            'terlambat' => $terlambatList,
            'sangat_terlambat' => $sangatTerlambatList,
            'izin' => $izinList,
            'sakit' => $sakitList,
            'alpha' => $alphaList,
            'bolos' => $bolosDates,
            'total_hari' => $totalHariWajib,
            'hari_berjalan' => $hariBerjalan,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRekapPresensi(int $penempatanPklId): array
    {
        return $this->calculateAttendance($penempatanPklId);
    }

    /**
     * {@inheritDoc}
     */
    public function getRekapAbsensiData(int $penempatanPklId): array
    {
        $res = $this->calculateAttendance($penempatanPklId);
        if ($res['status'] !== 'valid') {
            return [
                'hadir' => 0,
                'terlambat' => 0,
                'sangat_terlambat' => 0,
                'sakit' => 0,
                'izin' => 0,
                'alpha' => 0,
                'total_hadir' => 0,
                'total_hari' => 0,
                'hari_berjalan' => 0,
                'total_hari_pkl' => 0,
                'periode_valid' => false,
                'hadir_pct' => 0.0,
                'sakit_pct' => 0.0,
                'izin_pct' => 0.0,
                'alpha_pct' => 0.0,
            ];
        }

        $s = $res['summary'];
        return [
            'hadir' => $s['hadir'],
            'terlambat' => $s['terlambat_reguler'] ?? $s['terlambat'],
            'sangat_terlambat' => $s['sangat_terlambat'],
            'total_terlambat' => $s['terlambat'],
            'sakit' => $s['sakit'],
            'izin' => $s['izin'],
            'alpha' => $s['alpha'],
            'total_hadir' => $s['total_hadir'],
            'total_hari' => $s['total_hari'],
            'hari_berjalan' => $s['hari_berjalan'],
            'total_hari_pkl' => $s['total_hari_wajib'],
            'periode_valid' => true,
            'hadir_pct' => $s['hadir_pct'],
            'sakit_pct' => $s['sakit_pct'],
            'izin_pct' => $s['izin_pct'],
            'alpha_pct' => $s['alpha_pct'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRekapAbsensiBulanan(PenempatanPKL|int $penempatan, int $month, int $year): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        if ($month < 1 || $month > 12) {
            $month = (int) Carbon::now($timezone)->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) Carbon::now($timezone)->year;
        }

        $startDate = Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1, $timezone)->endOfMonth()->format('Y-m-d');

        return $this->calculateAttendance($penempatan, $startDate, $endDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getLaporanAbsensiData(array $filters): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::today($timezone);
        $yesterday = (clone $today)->subDay();

        $penempatanQuery = PenempatanPKL::query()
            ->with([
                'siswa.kelas.jurusan',
                'guru',
                'dudi',
                'periodePKL',
            ])
            ->where('status', '!=', 'dibatalkan');

        if (!empty($filters['guru_id'])) {
            $penempatanQuery->where('guru_id', $filters['guru_id']);
        }
        if (!empty($filters['dudi_id'])) {
            $penempatanQuery->where('dudi_id', $filters['dudi_id']);
        }
        if (!empty($filters['periode_id'])) {
            $penempatanQuery->where('periode_pkl_id', $filters['periode_id']);
        }
        if (!empty($filters['jurusan_id'])) {
            $penempatanQuery->whereHas('siswa.kelas', function ($q) use ($filters) {
                $q->where('jurusan_id', $filters['jurusan_id']);
            });
        }
        if (!empty($filters['kelas_id'])) {
            $penempatanQuery->whereHas('siswa', function ($q) use ($filters) {
                $q->where('class_id', $filters['kelas_id']);
            });
        }

        $penempatans = $penempatanQuery->get();
        if ($penempatans->isEmpty()) {
            return [
                'records' => collect([]),
                'stats' => [
                    'total_siswa' => 0,
                    'total_absensi' => 0,
                    'hadir' => 0,
                    'terlambat' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpha' => 0,
                ],
            ];
        }

        $penempatanIds = $penempatans->pluck('id')->all();

        // 1 query for all physical absensis in filter date range
        $absensiQuery = Absensi::whereIn('penempatan_pkl_id', $penempatanIds)
            ->with(['penempatanPKL.siswa.kelas.jurusan', 'penempatanPKL.guru', 'penempatanPKL.dudi', 'penempatanPKL.periodePKL']);

        if (!empty($filters['tanggal_mulai'])) {
            $absensiQuery->whereDate('tanggal', '>=', $filters['tanggal_mulai']);
        }
        if (!empty($filters['tanggal_akhir'])) {
            $absensiQuery->whereDate('tanggal', '<=', $filters['tanggal_akhir']);
        }

        $allPhysical = $absensiQuery->get()->groupBy('penempatan_pkl_id');

        // 1 query for all approved leaves in filter date range
        $pengajuanQuery = \App\Models\PengajuanKetidakhadiran::whereIn('penempatan_pkl_id', $penempatanIds)
            ->where('status', 'disetujui');

        if (!empty($filters['tanggal_mulai'])) {
            $pengajuanQuery->whereDate('tanggal', '>=', $filters['tanggal_mulai']);
        }
        if (!empty($filters['tanggal_akhir'])) {
            $pengajuanQuery->whereDate('tanggal', '<=', $filters['tanggal_akhir']);
        }

        $allLeaves = $pengajuanQuery->get()->groupBy('penempatan_pkl_id');

        $filterStart = !empty($filters['tanggal_mulai']) ? Carbon::parse($filters['tanggal_mulai'], $timezone)->startOfDay() : null;
        $filterEnd = !empty($filters['tanggal_akhir']) ? Carbon::parse($filters['tanggal_akhir'], $timezone)->endOfDay() : null;

        $allRecords = collect([]);

        foreach ($penempatans as $penempatan) {
            $pId = $penempatan->id;
            $pPhysical = $allPhysical->get($pId, collect())->keyBy(fn ($a) => $a->tanggal?->format('Y-m-d') ?? '');
            $pLeaves = $allLeaves->get($pId, collect())->keyBy(fn ($l) => $l->tanggal?->format('Y-m-d') ?? '');

            $formalMulai = $penempatan->tanggal_mulai ?? $penempatan->periodePKL?->tanggal_mulai;
            $formalSelesai = $penempatan->tanggal_selesai ?? $penempatan->periodePKL?->tanggal_selesai;

            if (!$formalMulai && !$filterStart) {
                foreach ($pPhysical as $item) {
                    $item->setRelation('penempatanPKL', $penempatan);
                    $allRecords->push($item);
                }
                continue;
            }

            $effectiveMulai = $formalMulai ? Carbon::parse($formalMulai, $timezone)->startOfDay() : (clone $filterStart);
            $effectiveSelesai = $formalSelesai ? Carbon::parse($formalSelesai, $timezone)->endOfDay() : ($filterEnd ? (clone $filterEnd) : (clone $effectiveMulai)->addMonths(3)->endOfDay());

            $windowStart = $filterStart && $filterStart->gt($effectiveMulai) ? (clone $filterStart) : (clone $effectiveMulai);
            $windowEnd = $filterEnd && $filterEnd->lt($effectiveSelesai) ? (clone $filterEnd) : (clone $effectiveSelesai);

            $studentDateRecords = [];

            // 1. Physical records
            foreach ($pPhysical as $dStr => $ab) {
                $ab->setRelation('penempatanPKL', $penempatan);
                $studentDateRecords[$dStr] = $ab;
            }

            // 2. Concluded past operational days without records
            $pastLimit = $yesterday->lt($windowEnd) ? clone $yesterday : clone $windowEnd;
            if ($windowStart->lte($pastLimit)) {
                $cur = clone $windowStart;
                $dudi = $penempatan->dudi;
                while ($cur->lte($pastLimit)) {
                    $isOp = $dudi ? $dudi->isHariOperasional($cur) : $cur->isWeekday();
                    if ($isOp) {
                        $dStr = $cur->format('Y-m-d');
                        if (!isset($studentDateRecords[$dStr])) {
                            if ($pLeaves->has($dStr)) {
                                $leave = $pLeaves->get($dStr);
                                $virtualLeave = new Absensi([
                                    'penempatan_pkl_id' => $penempatan->id,
                                    'tanggal' => $dStr,
                                    'status' => $leave->jenis,
                                    'jam_masuk' => null,
                                    'jam_keluar' => null,
                                    'keterangan' => 'Pengajuan Disetujui: ' . ($leave->keterangan ?? ucfirst($leave->jenis)),
                                ]);
                                $virtualLeave->setRelation('penempatanPKL', $penempatan);
                                $studentDateRecords[$dStr] = $virtualLeave;
                            } else {
                                $virtualAlfa = new Absensi([
                                    'penempatan_pkl_id' => $penempatan->id,
                                    'tanggal' => $dStr,
                                    'status' => 'alpha',
                                    'jam_masuk' => null,
                                    'jam_keluar' => null,
                                    'keterangan' => 'Tanpa Keterangan (Alfa)',
                                ]);
                                $virtualAlfa->setRelation('penempatanPKL', $penempatan);
                                $studentDateRecords[$dStr] = $virtualAlfa;
                            }
                        }
                    }
                    $cur->addDay();
                }
            }

            // 3. Approved leaves on ongoing or future days in window
            foreach ($pLeaves as $dStr => $leave) {
                if (!isset($studentDateRecords[$dStr])) {
                    $leaveDate = Carbon::parse($dStr, $timezone);
                    if ($leaveDate->gte($windowStart) && $leaveDate->lte($windowEnd)) {
                        $virtualLeave = new Absensi([
                            'penempatan_pkl_id' => $penempatan->id,
                            'tanggal' => $dStr,
                            'status' => $leave->jenis,
                            'jam_masuk' => null,
                            'jam_keluar' => null,
                            'keterangan' => 'Pengajuan Disetujui: ' . ($leave->keterangan ?? ucfirst($leave->jenis)),
                        ]);
                        $virtualLeave->setRelation('penempatanPKL', $penempatan);
                        $studentDateRecords[$dStr] = $virtualLeave;
                    }
                }
            }

            foreach ($studentDateRecords as $item) {
                $allRecords->push($item);
            }
        }

        // Summary stats before status filter
        $hadirCount = $allRecords->where('status', 'hadir')->count();
        $terlambatCount = $allRecords->where('status', 'terlambat')->count();
        $izinCount = $allRecords->where('status', 'izin')->count();
        $sakitCount = $allRecords->where('status', 'sakit')->count();
        $alphaCount = $allRecords->filter(fn ($r) => in_array($r->status, ['alpha', 'alfa'], true))->count();
        $totalRecords = $allRecords->count();

        $stats = [
            'total_siswa' => $allRecords->pluck('penempatan_pkl_id')->unique()->count(),
            'total_absensi' => $totalRecords,
            'total_records' => $totalRecords,
            'hadir' => $hadirCount,
            'total_hadir' => $hadirCount,
            'terlambat' => $terlambatCount,
            'total_terlambat' => $terlambatCount,
            'izin' => $izinCount,
            'total_izin' => $izinCount,
            'sakit' => $sakitCount,
            'total_sakit' => $sakitCount,
            'alpha' => $alphaCount,
            'alfa' => $alphaCount,
            'total_alfa' => $alphaCount,
        ];

        // Apply status filter if requested
        if (!empty($filters['status'])) {
            $targetStatus = strtolower((string) $filters['status']);
            $allRecords = $allRecords->filter(function ($item) use ($targetStatus) {
                $s = strtolower((string) $item->status);
                if (in_array($targetStatus, ['alpha', 'alfa'], true)) {
                    return in_array($s, ['alpha', 'alfa'], true);
                }
                return $s === $targetStatus;
            });
        }

        // Sort by tanggal desc, penempatan_pkl_id asc
        $allRecords = $allRecords->sortBy([
            ['tanggal', 'desc'],
            ['penempatan_pkl_id', 'asc'],
        ])->values();

        return [
            'records' => $allRecords,
            'stats' => $stats,
        ];
    }
}
