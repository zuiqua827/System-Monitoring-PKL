<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Service layer for the SiPintu Gateway integration.
 *
 * Orchestrates:
 *  - Fetching real student data from the SiPintu Gateway.
 *  - Synchronizing that data into the local students (siswa) table.
 *
 * Business rules:
 *  - If NIS exists locally → update the student (and its User).
 *  - If NIS is new → create a new Siswa + User (role "Siswa").
 *  - Local students that look like dummy/placeholder data and are absent
 *    from SiPintu are soft-deleted.
 *  - Never touches Super Admin, Guru, DUDI, or any transactional module
 *    (Period, Penempatan, Absensi, Aktivitas, Penilaian).
 */
class SiPintuService extends Service implements SiPintuServiceInterface
{
    public function __construct(
        private readonly SiPintuRepositoryInterface $siPintuRepository,
        private readonly SiswaRepositoryInterface $siswaRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array
    {
        return $this->siPintuRepository->fetchStudents($nis, $search);
    }

    /**
     * {@inheritDoc}
     *
     * @return array{created: int, updated: int, deleted: int, skipped: int}
     */
    public function syncStudents(): array
    {
        $students = $this->fetchStudents();

        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
        ];

        $remoteNisKeys = [];

        /** @var array<string, mixed> $remote */
        foreach ($students as $remote) {
            $nis = (string) ($remote['nis'] ?? '');

            if ($nis === '') {
                $stats['skipped']++;

                continue;
            }

            $remoteNisKeys[$nis] = true;

            $existing = $this->findSiswaByNis($nis);

            if ($existing === null) {
                $this->createStudent($remote);
                $stats['created']++;
            } else {
                $this->updateStudent($existing, $remote);
                $stats['updated']++;
            }
        }

        $stats['deleted'] = $this->softDeleteAbsentDummyStudents($remoteNisKeys);

        return $stats;
    }

    /**
     * Find a local siswa by NIS (including trashed).
     */
    private function findSiswaByNis(string $nis): ?Siswa
    {
        /** @var Siswa|null $siswa */
        $siswa = Siswa::query()->withTrashed()->where('nis', $nis)->first();

        return $siswa;
    }

/**
     * Create a new Siswa + User from a SiPintu student record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function createStudent(array $remote): void
    {
        $nama = (string) ($remote['nama'] ?? '');
        $nis = (string) $remote['nis'];

        $tanggalLahir = $this->parseTanggalLahir($remote);
        $email = Siswa::generateEmail($nis);
        $kelas = $this->defaultKelas();

        // Idempotency guard: if a User with this generated email already exists
        // (e.g. concurrent sync runs), update the existing student instead of
        // attempting a duplicate insert.
        $existingByEmail = User::query()->withTrashed()->where('email', $email)->first();
        if ($existingByEmail !== null) {
            $existingSiswa = Siswa::query()->withTrashed()->where('user_id', $existingByEmail->id)->first();
            if ($existingSiswa !== null) {
                $this->updateStudent($existingSiswa, $remote);
            }

            return;
        }

        $this->transaction(function () use ($remote, $nama, $nis, $tanggalLahir, $email, $kelas): void {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $nama,
                'email' => $email,
                'password' => $this->initialPassword($tanggalLahir),
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::SISWA->value);

            $this->siswaRepository->create([
                'user_id' => $user->id,
                'class_id' => $kelas?->id,
                'nis' => $nis,
                'nisn' => $remote['nisn'] ?? null,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote),
                'tanggal_lahir' => $tanggalLahir,
                'no_telepon' => $remote['no_telepon'] ?? null,
                'alamat' => $remote['alamat'] ?? null,
            ]);
        });
    }

    /**
     * Update an existing Siswa + User from a SiPintu student record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function updateStudent(Siswa $siswa, array $remote): void
    {
        $nama = (string) ($remote['nama'] ?? $siswa->nama);
        $nis = (string) $remote['nis'];
        $tanggalLahir = $this->parseTanggalLahir($remote);
        $email = Siswa::generateEmail($nis);

        $this->transaction(function () use ($siswa, $remote, $nama, $nis, $tanggalLahir, $email): void {
            // Re-activate if it was soft-deleted.
            if ($siswa->trashed()) {
                $siswa->restore();
            }

            $this->userRepository->update($siswa->user, [
                'name' => $nama,
                'email' => $email,
            ]);

            $this->siswaRepository->update($siswa, [
                'nis' => $nis,
                'nisn' => $remote['nisn'] ?? $siswa->nisn,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote) ?? $siswa->jenis_kelamin,
                'tanggal_lahir' => $tanggalLahir ?? $siswa->tanggal_lahir,
                'no_telepon' => $remote['no_telepon'] ?? $siswa->no_telepon,
                'alamat' => $remote['alamat'] ?? $siswa->alamat,
            ]);
        });
    }

    /**
     * Soft-delete local dummy students that are absent from SiPintu.
     *
     * Only soft-deletes students whose data looks like placeholder/dummy
     * data (e.g. NIS starting with "0", names like "Dummy", "Tes", "Siswa
     * Dummy", etc.). Real students are never deleted.
     *
     * @param  array<string, bool>  $remoteNisKeys
     */
    private function softDeleteAbsentDummyStudents(array $remoteNisKeys): int
    {
        $deleted = 0;

        /** @var Collection<int, Siswa> $localStudents */
        $localStudents = Siswa::query()->withoutTrashed()->get(['id', 'nis', 'nama']);

        foreach ($localStudents as $siswa) {
            if (isset($remoteNisKeys[$siswa->nis])) {
                continue;
            }

            if ($this->isDummyStudent($siswa)) {
                $this->siswaRepository->delete($siswa);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Heuristic to detect dummy/placeholder student records.
     */
    private function isDummyStudent(Siswa $siswa): bool
    {
        $nis = (string) $siswa->nis;
        $nama = strtolower((string) $siswa->nama);

        $dummyNamePatterns = ['dummy', 'contoh', 'testing', 'test ', 'tes', 'placeholder', 'siswa dummy'];

        $startsWithZero = Str::startsWith($nis, ['0', '000']);
        $isDummyName = Str::contains($nama, $dummyNamePatterns);
        $isNumericEven = false;

        // Very conservative extra guard: only delete when clearly placeholder.
        if ($startsWithZero && $isDummyName) {
            return true;
        }

        return $isDummyName && $this->hexLooksFake($nis);
    }

    private function hexLooksFake(string $nis): bool
    {
        return Str::startsWith($nis, ['1', '2', '3', '4', '5', '6', '7', '8', '9']) === false;
    }

    /**
     * Parse tanggal_lahir from a SiPintu record (supports Y-m-d and d/m/Y).
     */
    private function parseTanggalLahir(array $remote): ?string
    {
        $value = (string) ($remote['tanggal_lahir'] ?? $remote['tgl_lahir'] ?? '');

        if ($value === '') {
            return null;
        }

        try {
            if (str_contains($value, '/')) {
                return Carbon::createFromFormat('d/m/Y', $value)?->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapJenisKelamin(array $remote): ?string
    {
        $value = strtoupper((string) ($remote['jenis_kelamin'] ?? $remote['jk'] ?? ''));

        if ($value === 'L' || $value === 'P') {
            return $value;
        }

        if ($value === 'LAKI' || $value === 'LAKI-LAKI' || $value === 'MALE' || $value === 'L') {
            return 'L';
        }

        if ($value === 'PEREMPUAN' || $value === 'FEMALE' || $value === 'P') {
            return 'P';
        }

        return null;
    }

    private function initialPassword(?string $tanggalLahir): string
    {
        $date = $tanggalLahir !== null
            ? Carbon::parse($tanggalLahir)->format('Ymd')
            : now()->format('Ymd');

        return bcrypt($date);
    }

    private function defaultKelas(): ?Kelas
    {
        /** @var Kelas|null $kelas */
        $kelas = Kelas::query()->first();

        return $kelas;
    }
}
