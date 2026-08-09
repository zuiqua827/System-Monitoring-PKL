<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Service layer for the SiPintu Gateway integration.
 *
 * Orchestrates:
 *  - Fetching real student AND teacher data from the SiPintu Gateway.
 *  - Synchronizing that data into the local siswa (students) and guru
 *    (teachers) tables.
 *
 * Business rules (SAFETY-FIRST):
 *  - Students: NIS is the primary unique identifier; NISN is used as a
 *    fallback when NIS does not match locally.
 *  - Teachers: NIP is the unique identifier.
 *  - Student email is always auto-generated: {NIS}@smk1bangsri.sch.id.
 *  - Teacher email uses the API email if available, otherwise
 *    {NIP}@smk1bangsri.sch.id.
 *  - Default password for newly created accounts is "password" (hashed).
 *  - Never overwrites an existing user's password.
 *  - NO automatic deletion: local records that are absent from SiPintu are
 *    NEVER deleted/soft-deleted. They are only reported as "Tidak Ditemukan".
 *  - NO default-kelas fallback: if a SiPintu student cannot be mapped to a
 *    local kelas, they are flagged "Perlu Pemetaan" and NOT created/updated
 *    with a guessed class_id.
 *  - Each record is processed inside its own transaction so a single failure
 *    never corrupts other records.
 *  - Never touches Super Admin, DUDI, or any transactional module.
 */
class SiPintuService extends Service implements SiPintuServiceInterface
{
    public function __construct(
        private readonly SiPintuRepositoryInterface $siPintuRepository,
        private readonly SiswaRepositoryInterface $siswaRepository,
        private readonly GuruRepositoryInterface $guruRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SipintuClassroomMappingServiceInterface $classroomMappingService,
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
     */
    public function fetchTeachers(?string $nip = null, ?string $search = null): array
    {
        return $this->siPintuRepository->fetchTeachers($nip, $search);
    }

    /**
     * Read-only preview: classify remote students into 7 categories without
     * writing anything to the database.
     *
     * Categories: baru, diperbarui, tidak_berubah, konflik, perlu_pemetaan,
     * tidak_ditemukan, error.
     *
     * @return array<string, int>
     */
    public function previewStudents(): array
    {
        $students = $this->fetchStudents();

        $stats = $this->emptyPreview();

        $remoteNisSet = [];
        $remoteNisnSet = [];

        /** @var array<string, mixed> $remote */
        foreach ($students as $remote) {
            $nis = (string) ($remote['nis'] ?? '');

            if ($nis === '') {
                $stats['error']++;

                continue;
            }

            $remoteNisSet[$nis] = true;

            $nisn = (string) ($remote['nisn'] ?? '');
            if ($nisn !== '') {
                $remoteNisnSet[$nisn] = true;
            }

            $stats[$this->outcomeToPreviewLabel($this->classifyStudent($remote))]++;
        }

        $stats['total_remote'] = count($students);
        $stats['tidak_ditemukan'] = $this->countLocalStudentsMissingFrom($remoteNisSet, $remoteNisnSet);

        return $stats;
    }

    /**
     * Read-only preview: classify remote teachers into 7 categories without
     * writing anything to the database.
     *
     * @return array<string, int>
     */
    public function previewTeachers(): array
    {
        $teachers = $this->fetchTeachers();

        $stats = $this->emptyPreview();

        $remoteNipSet = [];

        /** @var array<string, mixed> $remote */
        foreach ($teachers as $remote) {
            $nip = (string) ($remote['nip'] ?? '');

            if ($nip === '') {
                $stats['error']++;

                continue;
            }

            $remoteNipSet[$nip] = true;

            $stats[$this->outcomeToPreviewLabel($this->classifyTeacher($remote))]++;
        }

        $stats['total_remote'] = count($teachers);
        $stats['tidak_ditemukan'] = $this->countLocalTeachersMissingFrom($remoteNipSet);

        return $stats;
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     deleted: int,
     *     skipped: int,
     *     unchanged: int,
     *     conflicts: int,
     *     needs_mapping: int,
     *     errors: int
     * }
     */
    public function syncStudents(): array
    {
        $students = $this->fetchStudents();

        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'conflicts' => 0,
            'needs_mapping' => 0,
            'errors' => 0,
        ];

        /** @var array<string, mixed> $remote */
        foreach ($students as $remote) {
            $nis = (string) ($remote['nis'] ?? '');

            if ($nis === '') {
                $stats['errors']++;
                $stats['skipped']++;

                continue;
            }

            try {
                $outcome = $this->syncOneStudent($remote);
            } catch (\Throwable $e) {
                $outcome = 'error';

                logger()->error("SiPintu sync siswa NIS={$nis} gagal: {$e->getMessage()}");
            }

            $this->applyOutcome($stats, $outcome);
        }

        // Penghapusan otomatis NONAKTIF: siswa lokal yang tidak ada di SiPintu
        // TIDAK pernah dihapus. Hanya dilaporkan sebagai "Tidak Ditemukan".

        return $stats;
    }

    /**
     * {@inheritDoc}
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     deleted: int,
     *     skipped: int,
     *     unchanged: int,
     *     conflicts: int,
     *     needs_mapping: int,
     *     errors: int
     * }
     */
    public function syncTeachers(): array
    {
        $teachers = $this->fetchTeachers();

        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'conflicts' => 0,
            'needs_mapping' => 0,
            'errors' => 0,
        ];

        /** @var array<string, mixed> $remote */
        foreach ($teachers as $remote) {
            $nip = (string) ($remote['nip'] ?? '');

            if ($nip === '') {
                $stats['errors']++;
                $stats['skipped']++;

                continue;
            }

            try {
                $outcome = $this->syncOneTeacher($remote);
            } catch (\Throwable $e) {
                $outcome = 'error';

                logger()->error("SiPintu sync guru NIP={$nip} gagal: {$e->getMessage()}");
            }

            $this->applyOutcome($stats, $outcome);
        }

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
     * Resolve the best local match for a remote student, with NISN fallback.
     *
     * @param  array<string, mixed>  $remote
     * @return array{status: string, siswa: Siswa|null}
     */
    private function resolveExisting(array $remote): array
    {
        $nis = (string) $remote['nis'];
        $nisn = (string) ($remote['nisn'] ?? '');

        $byNis = $this->findSiswaByNis($nis);

        if ($nisn === '') {
            return ['status' => $byNis === null ? 'new' : 'matched', 'siswa' => $byNis];
        }

        /** @var Siswa|null $byNisn */
        $byNisn = Siswa::query()
            ->withTrashed()
            ->where('nisn', $nisn)
            ->where('nis', '!=', $nis)
            ->first();

        // Ambiguous: NIS and NISN point to two different local records.
        if ($byNis !== null && $byNisn !== null && (int) $byNisn->id !== (int) $byNis->id) {
            return ['status' => 'conflict', 'siswa' => null];
        }

        if ($byNis !== null) {
            return ['status' => 'matched', 'siswa' => $byNis];
        }

        if ($byNisn !== null) {
            return ['status' => 'matched', 'siswa' => $byNisn];
        }

        return ['status' => 'new', 'siswa' => null];
    }

    /**
     * Pure read-only classification for a remote student.
     *
     * @param  array<string, mixed>  $remote
     */
    private function classifyStudent(array $remote): string
    {
        $resolved = $this->resolveExisting($remote);

        if ($resolved['status'] === 'conflict') {
            return 'conflict';
        }

        $kelas = $this->resolveKelas($remote);

        if ($resolved['siswa'] === null) {
            // New student cannot be created without a valid kelas.
            return $kelas === null ? 'needs_mapping' : 'created';
        }

        // Existing student: if the remote carries a classroom that cannot be
        // mapped, we must NOT change anything (avoid guessing class_id).
        $hasClassroom = (int) ($remote['classroom_id'] ?? 0) > 0;
        if ($hasClassroom && $kelas === null) {
            return 'needs_mapping';
        }

        return $this->studentUnchanged($resolved['siswa'], $remote, $kelas)
            ? 'unchanged'
            : 'updated';
    }

    /**
     * Actual sync for one remote student (with transaction per record).
     *
     * @param  array<string, mixed>  $remote
     */
    private function syncOneStudent(array $remote): string
    {
        $resolved = $this->resolveExisting($remote);

        if ($resolved['status'] === 'conflict') {
            return 'conflict';
        }

        $kelas = $this->resolveKelas($remote);
        $existing = $resolved['siswa'];

        if ($existing === null) {
            // New student requires a mapped kelas before it can be created.
            if ($kelas === null) {
                return 'needs_mapping';
            }

            $this->createStudent($remote, $kelas);

            return 'created';
        }

        $hasClassroom = (int) ($remote['classroom_id'] ?? 0) > 0;
        if ($hasClassroom && $kelas === null) {
            return 'needs_mapping';
        }

        if ($this->studentUnchanged($existing, $remote, $kelas)) {
            return 'unchanged';
        }

        $this->updateStudent($existing, $remote, $kelas);

        return 'updated';
    }

    /**
     * Create a new Siswa + User from a SiPintu student record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function createStudent(array $remote, Kelas $kelas): void
    {
        $nama = (string) ($remote['nama'] ?? '');
        $nis = (string) $remote['nis'];

        $tanggalLahir = $this->parseTanggalLahir($remote);
        $email = Siswa::generateEmail($nis);

        // Idempotency guard: if a User with this generated email already exists
        // (e.g. concurrent sync runs), update the existing student instead.
        $existingByEmail = User::query()->withTrashed()->where('email', $email)->first();
        if ($existingByEmail !== null) {
            $existingSiswa = Siswa::query()->withTrashed()->where('user_id', $existingByEmail->id)->first();
            if ($existingSiswa !== null) {
                $this->updateStudent($existingSiswa, $remote, $kelas);
            }

            return;
        }

        $this->transaction(function () use ($remote, $nama, $nis, $tanggalLahir, $email, $kelas): void {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $nama,
                'email' => $email,
                'password' => $this->defaultPassword(),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::SISWA->value);

            $this->siswaRepository->create([
                'user_id' => $user->id,
                'class_id' => $kelas->id,
                'nis' => $nis,
                'nisn' => $remote['nisn'] ?? null,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote),
                'tanggal_lahir' => $tanggalLahir,
                'no_telepon' => $remote['no_telepon'] ?? $remote['hp'] ?? null,
                'alamat' => $remote['alamat'] ?? null,
            ]);
        });
    }

    /**
     * Update an existing Siswa + User from a SiPintu student record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function updateStudent(Siswa $siswa, array $remote, ?Kelas $kelas): void
    {
        $nama = (string) ($remote['nama'] ?? $siswa->nama);
        $nis = (string) $remote['nis'];
        $tanggalLahir = $this->parseTanggalLahir($remote);
        $email = Siswa::generateEmail($nis);

        $this->transaction(function () use ($siswa, $remote, $nama, $nis, $tanggalLahir, $email, $kelas): void {
            if ($siswa->trashed()) {
                $siswa->restore();
            }

            $user = $siswa->user()->withTrashed()->first();

            if ($user === null) {
                $user = $this->userRepository->create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => $this->defaultPassword(),
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(UserRole::SISWA->value);

                $this->siswaRepository->update($siswa, ['user_id' => $user->id]);
            } elseif ($user->trashed()) {
                $user->restore();
            }

            if (! $user instanceof User) {
                throw new \RuntimeException(
                    "Gagal memperoleh akun User untuk NIS={$nis} saat sinkronisasi."
                );
            }

            $this->userRepository->update($user, [
                'name' => $nama,
                'email' => $email,
            ]);

            $this->siswaRepository->update($siswa, [
                'nis' => $nis,
                'nisn' => $remote['nisn'] ?? $siswa->nisn,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote) ?? $siswa->jenis_kelamin,
                'tanggal_lahir' => $tanggalLahir ?? $siswa->tanggal_lahir,
                'no_telepon' => $remote['no_telepon'] ?? $remote['hp'] ?? $siswa->no_telepon,
                'alamat' => $remote['alamat'] ?? $siswa->alamat,
                'class_id' => $kelas?->id ?? $siswa->class_id,
            ]);
        });
    }

    /**
     * Pure read-only classification for a remote teacher (NIP based).
     *
     * @param  array<string, mixed>  $remote
     */
    private function classifyTeacher(array $remote): string
    {
        $nip = (string) $remote['nip'];

        $existing = $this->guruRepository->findByNip($nip);

        if ($existing === null) {
            return 'created';
        }

        return $this->teacherUnchanged($existing, $remote) ? 'unchanged' : 'updated';
    }

    /**
     * Actual sync for one remote teacher (with transaction per record).
     *
     * @param  array<string, mixed>  $remote
     */
    private function syncOneTeacher(array $remote): string
    {
        $nip = (string) $remote['nip'];

        $existing = $this->guruRepository->findByNip($nip);

        if ($existing === null) {
            $this->createTeacher($remote);

            return 'created';
        }

        if ($this->teacherUnchanged($existing, $remote)) {
            return 'unchanged';
        }

        $this->updateTeacher($existing, $remote);

        return 'updated';
    }

    /**
     * Create a new Guru + User from a SiPintu teacher record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function createTeacher(array $remote): void
    {
        $nama = (string) ($remote['nama'] ?? $remote['nama_panggilan'] ?? '');
        $nip = (string) $remote['nip'];
        $email = $this->teacherEmail($remote, $nip);

        $existingByEmail = User::query()->withTrashed()->where('email', $email)->first();
        if ($existingByEmail !== null) {
            $existingGuru = Guru::query()->withTrashed()->where('user_id', $existingByEmail->id)->first();
            if ($existingGuru !== null) {
                $this->updateTeacher($existingGuru, $remote);
            }

            return;
        }

        $this->transaction(function () use ($remote, $nama, $nip, $email): void {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $nama,
                'email' => $email,
                'password' => $this->defaultPassword(),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::GURU->value);

            $this->guruRepository->create([
                'user_id' => $user->id,
                'nip' => $nip,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote),
                'no_hp' => $remote['no_hp'] ?? $remote['hp'] ?? null,
                'alamat' => $remote['alamat'] ?? null,
            ]);
        });
    }

    /**
     * Update an existing Guru + User from a SiPintu teacher record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function updateTeacher(Guru $guru, array $remote): void
    {
        $nama = (string) ($remote['nama'] ?? $guru->nama);
        $nip = (string) $remote['nip'];
        $email = $this->teacherEmail($remote, $nip);

        $this->transaction(function () use ($guru, $remote, $nama, $nip, $email): void {
            if ($guru->trashed()) {
                $guru->restore();
            }

            $user = $guru->user()->withTrashed()->first();

            if ($user === null) {
                $user = $this->userRepository->create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => $this->defaultPassword(),
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(UserRole::GURU->value);

                $this->guruRepository->update($guru, ['user_id' => $user->id]);
            } elseif ($user->trashed()) {
                $user->restore();
            }

            // Defensive guard against a null/invalid User (prevents TypeError → HTTP 500).
            if (! $user instanceof User) {
                throw new \RuntimeException(
                    "Gagal memperoleh akun User untuk NIP={$nip} saat sinkronisasi."
                );
            }

            $this->userRepository->update($user, [
                'name' => $nama,
                'email' => $email,
            ]);

            $this->guruRepository->update($guru, [
                'nip' => $nip,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote) ?? $guru->jenis_kelamin,
                'no_hp' => $remote['no_hp'] ?? $remote['hp'] ?? $guru->no_hp,
                'alamat' => $remote['alamat'] ?? $guru->alamat,
            ]);
        });
    }

    /**
     * Determine the teacher login email.
     *
     * Uses the API "email" (from user.email) if available, otherwise generates
     * {NIP}@smk1bangsri.sch.id.
     *
     * @param  array<string, mixed>  $remote
     */
    private function teacherEmail(array $remote, string $nip): string
    {
        $email = (string) ($remote['email'] ?? $remote['user']['email'] ?? '');

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return strtolower(trim($email));
        }

        return strtolower(trim($nip)).'@'.Siswa::emailDomain();
    }

    /**
     * Compare all updatable student fields to decide unchanged vs updated.
     *
     * @param  array<string, mixed>  $remote
     */
    private function studentUnchanged(Siswa $siswa, array $remote, ?Kelas $kelas): bool
    {
        $expectedName = (string) ($remote['nama'] ?? $siswa->nama ?? '');
        $expectedNisn = (string) ($remote['nisn'] ?? $siswa->nisn ?? '');
        $expectedJk = $this->mapJenisKelamin($remote) ?? $siswa->jenis_kelamin ?? null;
        $expectedTgl = (string) ($this->parseTanggalLahir($remote) ?? ($siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : ''));
        $expectedPhone = (string) ($remote['no_telepon'] ?? $remote['hp'] ?? $siswa->no_telepon ?? '');
        $expectedAlamat = (string) ($remote['alamat'] ?? $siswa->alamat ?? '');
        $expectedClass = $kelas?->id ?? $siswa->class_id;

        return (string) $siswa->nis === (string) $remote['nis']
            && (string) $siswa->nisn === $expectedNisn
            && (string) $siswa->nama === $expectedName
            && (string) ($siswa->jenis_kelamin ?? '') === (string) $expectedJk
            && ($siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : '') === $expectedTgl
            && (string) ($siswa->no_telepon ?? '') === $expectedPhone
            && (string) ($siswa->alamat ?? '') === $expectedAlamat
            && (int) ($siswa->class_id ?? 0) === (int) $expectedClass;
    }

    /**
     * Compare all updatable teacher fields to decide unchanged vs updated.
     *
     * @param  array<string, mixed>  $remote
     */
    private function teacherUnchanged(Guru $guru, array $remote): bool
    {
        $expectedName = (string) ($remote['nama'] ?? $guru->nama ?? '');
        $expectedJk = $this->mapJenisKelamin($remote) ?? $guru->jenis_kelamin ?? null;
        $expectedPhone = (string) ($remote['no_hp'] ?? $remote['hp'] ?? $guru->no_hp ?? '');
        $expectedAlamat = (string) ($remote['alamat'] ?? $guru->alamat ?? '');

        return (string) $guru->nip === (string) $remote['nip']
            && (string) $guru->nama === $expectedName
            && (string) ($guru->jenis_kelamin ?? '') === (string) $expectedJk
            && (string) ($guru->no_hp ?? '') === $expectedPhone
            && (string) ($guru->alamat ?? '') === $expectedAlamat;
    }

    /**
     * Parse tanggal_lahir from a SiPintu record (supports Y-m-d and d/m/Y).
     *
     * @param  array<string, mixed>  $remote
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

    /**
     * @param  array<string, mixed>  $remote
     */
    private function mapJenisKelamin(array $remote): ?string
    {
        $value = strtoupper((string) ($remote['jenis_kelamin'] ?? $remote['jk'] ?? ''));

        if ($value === 'L' || $value === 'P') {
            return $value;
        }

        if ($value === 'LAKI' || $value === 'LAKI-LAKI' || $value === 'MALE') {
            return 'L';
        }

        if ($value === 'PEREMPUAN' || $value === 'FEMALE') {
            return 'P';
        }

        return null;
    }

    /**
     * Default password for newly created synchronized accounts.
     */
    private function defaultPassword(): string
    {
        return bcrypt('password');
    }

    /**
     * Resolve the local kelas for a SiPintu student record.
     *
     * Uses ONLY the Admin-configured classroom mapping. There is NO fallback
     * to the first kelas (safety requirement). Returns null when the mapping
     * is missing so the caller can flag the student as "Perlu Pemetaan".
     *
     * @param  array<string, mixed>  $remote
     */
    private function resolveKelas(array $remote): ?Kelas
    {
        $classroomId = (int) ($remote['classroom_id'] ?? 0);

        if ($classroomId <= 0) {
            return null;
        }

        $kelasId = $this->classroomMappingService->resolveKelasId($classroomId);

        if ($kelasId === null) {
            return null;
        }

        /** @var Kelas|null $kelas */
        $kelas = Kelas::query()->find($kelasId);

        return $kelas;
    }

    /**
     * Count local active students (siswa) that are absent from the SiPintu
     * remote dataset. They are only counted/reported, never deleted.
     *
     * @param  array<string, bool>  $remoteNisSet
     * @param  array<string, bool>  $remoteNisnSet
     */
    private function countLocalStudentsMissingFrom(array $remoteNisSet, array $remoteNisnSet): int
    {
        $missing = 0;

        /** @var \Illuminate\Database\Eloquent\Collection<int, Siswa> $locals */
        $locals = Siswa::query()->withoutTrashed()->get(['id', 'nis', 'nisn']);

        foreach ($locals as $siswa) {
            if (! isset($remoteNisSet[$siswa->nis]) && ! isset($remoteNisnSet[(string) $siswa->nisn])) {
                $missing++;
            }
        }

        return $missing;
    }

    /**
     * Count local active teachers (guru) that are absent from the SiPintu
     * remote dataset. They are only counted/reported, never deleted.
     *
     * @param  array<string, bool>  $remoteNipSet
     */
    private function countLocalTeachersMissingFrom(array $remoteNipSet): int
    {
        $missing = 0;

        /** @var \Illuminate\Database\Eloquent\Collection<int, Guru> $locals */
        $locals = Guru::query()->withoutTrashed()->get(['id', 'nip']);

        foreach ($locals as $guru) {
            if (! isset($remoteNipSet[$guru->nip])) {
                $missing++;
            }
        }

        return $missing;
    }

    /**
     * Map a raw outcome string to the preview category label.
     */
    private function outcomeToPreviewLabel(string $outcome): string
    {
        return match ($outcome) {
            'created' => 'baru',
            'updated' => 'diperbarui',
            'unchanged' => 'tidak_berubah',
            'conflict' => 'konflik',
            'needs_mapping' => 'perlu_pemetaan',
            default => 'error',
        };
    }

    /**
     * Update a sync-stats accumulator with an outcome.
     *
     * @param  array<string, int>  $stats
     */
    private function applyOutcome(array &$stats, string $outcome): void
    {
        match ($outcome) {
            'created' => $stats['created']++,
            'updated' => $stats['updated']++,
            'unchanged' => $stats['unchanged']++,
            'conflict' => $this->incrementSkipped($stats, 'conflicts'),
            'needs_mapping' => $this->incrementSkipped($stats, 'needs_mapping'),
            default => $this->incrementSkipped($stats, 'errors'),
        };
    }

    /**
     * Increment a specific skipped-type counter and the global skipped counter.
     *
     * @param  array<string, int>  $stats
     */
    private function incrementSkipped(array &$stats, string $key): void
    {
        $stats['skipped']++;
        $stats[$key]++;
    }

    /**
     * Empty preview accumulator.
     *
     * @return array<string, int>
     */
    private function emptyPreview(): array
    {
        return [
            'baru' => 0,
            'diperbarui' => 0,
            'tidak_berubah' => 0,
            'konflik' => 0,
            'perlu_pemetaan' => 0,
            'tidak_ditemukan' => 0,
            'error' => 0,
            'total_remote' => 0,
        ];
    }
}

