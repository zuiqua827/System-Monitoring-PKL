<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
use App\Models\Siswa;
use App\Models\User;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service layer for the SiPintu Gateway integration.
 *
 * Orchestrates:
 *  - Fetching real student AND teacher data from the SiPintu Gateway.
 *  - Synchronizing that data into the local siswa (students) and guru
 *    (teachers) tables.
 *
 * Business rules (SAFETY-FIRST):
 *  - Students: NIS is the primary unique identifier; a NISN collision is
 *    reported as a conflict and never rewrites the local NIS.
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

    private ?Collection $localStudentsCache = null;

    private ?Collection $localTeachersCache = null;

    /** @var array<string, Siswa>|null NIS → Siswa hashmap for O(1) lookups */
    private ?array $siswaByNis = null;

    /** @var array<string, Siswa>|null NISN → Siswa hashmap for O(1) lookups */
    private ?array $siswaByNisn = null;

    /** @var array<string, Guru>|null NIP → Guru hashmap for O(1) lookups */
    private ?array $guruByNip = null;

    /** @var array<int, User>|null user_id → User hashmap for O(1) lookups */
    private ?array $usersById = null;

    /** @var array<string, User>|null email → User hashmap for O(1) lookups */
    private ?array $usersByEmail = null;

    /** @var array<int, bool>|null user_id → isProtected bool hashmap for O(1) lookups */
    private ?array $protectedUsersCache = null;

    /** @var array<int, Kelas>|null id → Kelas hashmap */
    private ?array $kelasById = null;

    /** @var array<int, int>|null SiPintu classroom_id → local kelas_id */
    private ?array $kelasIdBySipintuClassroomId = null;

    /**
     * Preload all reference data into O(1) lookup hashmaps.
     *
     * This eliminates N+1 queries by loading all students, teachers, users, roles,
     * and kelas upfront and building associative arrays for instant lookups.
     */
    public function preloadCaches(): void
    {
        if ($this->siswaByNis !== null) {
            return; // Already preloaded
        }

        // Load all students with their user and roles eager-loaded
        $this->localStudentsCache = Siswa::query()->withTrashed()->with('user.roles')->get();

        // Build NIS → Siswa hashmap
        $this->siswaByNis = [];
        $this->siswaByNisn = [];
        foreach ($this->localStudentsCache as $siswa) {
            $nis = trim((string) $siswa->nis);
            if ($nis !== '') {
                $this->siswaByNis[$nis] = $siswa;
            }
            $nisn = trim((string) $siswa->nisn);
            if ($nisn !== '') {
                $this->siswaByNisn[$nisn] = $siswa;
            }
        }

        $this->localTeachersCache = Guru::query()->withTrashed()->with('user.roles')->get();
        $this->guruByNip = [];
        foreach ($this->localTeachersCache as $guru) {
            $nip = trim((string) $guru->nip);
            if ($nip !== '') {
                $this->guruByNip[$nip] = $guru;
            }
        }

        // Build user lookups and protected user status from the eager-loaded relations
        $this->usersById = [];
        $this->usersByEmail = [];
        $this->protectedUsersCache = [];

        foreach ($this->localStudentsCache as $siswa) {
            if ($siswa->relationLoaded('user') && $siswa->user !== null) {
                $user = $siswa->user;
                $this->usersById[$user->id] = $user;
                $this->usersByEmail[strtolower($user->email)] = $user;
                $this->protectedUsersCache[$user->id] = $user->hasRole(UserRole::SUPER_ADMIN->value)
                    || $user->hasRole(UserRole::DUDI->value);
            }
        }
        foreach ($this->localTeachersCache as $guru) {
            if ($guru->relationLoaded('user') && $guru->user !== null) {
                $user = $guru->user;
                $this->usersById[$user->id] = $user;
                $this->usersByEmail[strtolower($user->email)] = $user;
                $this->protectedUsersCache[$user->id] = $user->hasRole(UserRole::SUPER_ADMIN->value)
                    || $user->hasRole(UserRole::DUDI->value);
            }
        }

        // Load all kelas and build normalized name → Kelas hashmap
        $localKelas = Kelas::query()->get();
        $this->kelasById = [];
        foreach ($localKelas as $kelas) {
            $this->kelasById[$kelas->id] = $kelas;
        }

        $this->kelasIdBySipintuClassroomId = SipintuClassroomMapping::query()
            ->pluck('kelas_id', 'classroom_id')
            ->mapWithKeys(static fn (mixed $kelasId, mixed $classroomId): array => [(int) $classroomId => (int) $kelasId])
            ->all();
    }

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
     * {@inheritDoc}
     */
    public function fetchSyncPayload(): array
    {
        $students = $this->fetchStudents();
        Log::info('SiPintu students fetched', ['count' => count($students)]);

        $teachers = $this->fetchTeachers();
        Log::info('SiPintu teachers fetched', ['count' => count($teachers)]);

        return [
            'students' => $students,
            'teachers' => $teachers,
        ];
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
        $this->preloadCaches();

        return $this->processStudentRecords($this->fetchStudents(), [
            'dry_run' => true,
            'stats' => $this->emptyPreview(),
        ]);
    }

    /**
     * Read-only preview: classify remote teachers into 7 categories without
     * writing anything to the database.
     *
     * @return array<string, int>
     */
    public function previewTeachers(): array
    {
        $this->preloadCaches();

        return $this->processTeacherRecords($this->fetchTeachers(), [
            'dry_run' => true,
            'stats' => $this->emptyPreview(),
        ]);
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
    public function syncStudents(?array $students = null): array
    {
        $this->preloadCaches();

        return $this->processStudentRecords($students ?? $this->fetchStudents(), [
            'dry_run' => false,
            'stats' => $this->emptySyncStats(),
        ]);
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
    public function syncTeachers(?array $teachers = null): array
    {
        $this->preloadCaches();

        return $this->processTeacherRecords($teachers ?? $this->fetchTeachers(), [
            'dry_run' => false,
            'stats' => $this->emptySyncStats(),
        ]);
    }

    /**
     * Process student records for both preview and live synchronization.
     * The structured context keeps the dry-run flag and accumulator named,
     * avoiding fragile positional argument lists.
     *
     * @param  array<int, mixed>  $students
     * @param  array{dry_run: bool, stats: array<string, int>}  $context
     * @return array<string, int>
     */
    private function processStudentRecords(array $students, array $context): array
    {
        $dryRun = $context['dry_run'];
        $stats = $context['stats'];
        $seenNis = [];
        $remoteNisSet = [];
        $remoteNisnSet = [];

        foreach ($students as $index => $remote) {
            if (! is_array($remote)) {
                $this->recordInvalidRemote($stats, $dryRun, 'student', $index, null, 'record is not an object');

                continue;
            }

            $nis = $this->identifier($remote['nis'] ?? null);
            if ($nis === null) {
                $this->recordInvalidRemote($stats, $dryRun, 'student', $index, null, 'NIS is missing');

                continue;
            }

            if (isset($seenNis[$nis])) {
                Log::warning('Student skipped during SiPintu sync', [
                    'reason' => 'duplicate_nis_in_response',
                    'nis' => $nis,
                    'record_index' => $index,
                    'dry_run' => $dryRun,
                ]);
                $this->applyRecordOutcome($stats, $dryRun, 'conflict');

                continue;
            }

            $seenNis[$nis] = true;
            $remote['nis'] = $nis;
            $remoteNisSet[$nis] = true;

            $nisn = $this->identifier($remote['nisn'] ?? null);
            if ($nisn !== null) {
                $remote['nisn'] = $nisn;
                $remoteNisnSet[$nisn] = true;
            }

            $outcome = $dryRun
                ? $this->classifyStudent($remote)
                : $this->syncOneStudent($remote);

            if ($outcome === 'needs_mapping') {
                Log::warning('Student skipped during SiPintu sync', [
                    'reason' => 'missing_classroom_mapping',
                    'nis' => $nis,
                    'classroom_id' => $this->classroomId($remote),
                    'dry_run' => $dryRun,
                ]);
            }

            $this->applyRecordOutcome($stats, $dryRun, $outcome);
        }

        if ($dryRun) {
            $stats['total_remote'] = count($students);
            $stats['tidak_ditemukan'] = $this->countLocalStudentsMissingFrom($remoteNisSet, $remoteNisnSet);
        }

        return $stats;
    }

    /**
     * @param  array<int, mixed>  $teachers
     * @param  array{dry_run: bool, stats: array<string, int>}  $context
     * @return array<string, int>
     */
    private function processTeacherRecords(array $teachers, array $context): array
    {
        $dryRun = $context['dry_run'];
        $stats = $context['stats'];
        $seenNip = [];
        $remoteNipSet = [];

        foreach ($teachers as $index => $remote) {
            if (! is_array($remote)) {
                $this->recordInvalidRemote($stats, $dryRun, 'teacher', $index, null, 'record is not an object');

                continue;
            }

            $nip = $this->identifier($remote['nip'] ?? null);
            if ($nip === null) {
                $this->recordInvalidRemote($stats, $dryRun, 'teacher', $index, null, 'NIP is missing');

                continue;
            }

            if (isset($seenNip[$nip])) {
                Log::warning('Teacher skipped during SiPintu sync', [
                    'reason' => 'duplicate_nip_in_response',
                    'nip' => $nip,
                    'record_index' => $index,
                    'dry_run' => $dryRun,
                ]);
                $this->applyRecordOutcome($stats, $dryRun, 'conflict');

                continue;
            }

            $seenNip[$nip] = true;
            $remote['nip'] = $nip;
            $remoteNipSet[$nip] = true;

            $this->applyRecordOutcome(
                $stats,
                $dryRun,
                $dryRun ? $this->classifyTeacher($remote) : $this->syncOneTeacher($remote),
            );
        }

        if ($dryRun) {
            $stats['total_remote'] = count($teachers);
            $stats['tidak_ditemukan'] = $this->countLocalTeachersMissingFrom($remoteNipSet);
        }

        return $stats;
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function recordInvalidRemote(array &$stats, bool $dryRun, string $entity, int|string $index, ?string $identifier, string $reason): void
    {
        Log::warning(ucfirst($entity).' skipped during SiPintu sync', [
            'reason' => $reason,
            'record_index' => $index,
            'identifier' => $identifier,
            'dry_run' => $dryRun,
        ]);

        $this->applyRecordOutcome($stats, $dryRun, 'error');
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function applyRecordOutcome(array &$stats, bool $dryRun, string $outcome): void
    {
        if ($dryRun) {
            $stats[$this->outcomeToPreviewLabel($outcome)]++;

            return;
        }

        $this->applyOutcome($stats, $outcome);
    }

    private function identifier(mixed $value): ?string
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        $identifier = trim((string) $value);

        return $identifier === '' ? null : $identifier;
    }

    /**
     * Find a local siswa by NIS (including trashed). O(1) via hashmap.
     */
    private function findSiswaByNis(string $nis): ?Siswa
    {
        if ($this->siswaByNis !== null) {
            return $this->siswaByNis[trim($nis)] ?? null;
        }

        /** @var Siswa|null $siswa */
        $siswa = Siswa::query()->withTrashed()->where('nis', $nis)->first();

        return $siswa;
    }

    /**
     * Find a local guru by NIP (including trashed). O(1) via hashmap.
     */
    private function findGuruByNip(string $nip): ?Guru
    {
        if ($this->guruByNip !== null) {
            return $this->guruByNip[trim($nip)] ?? null;
        }

        return $this->guruRepository->findByNip($nip);
    }

    /**
     * Resolve the best local match for a remote student, with NISN fallback.
     * Uses O(1) hashmap lookups when caches are preloaded.
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

        // O(1) NISN lookup via hashmap
        if ($this->siswaByNisn !== null) {
            $byNisn = $this->siswaByNisn[trim($nisn)] ?? null;
            // Ensure NISN match is actually a different student (not same NIS)
            if ($byNisn !== null && trim((string) $byNisn->nis) === trim($nis)) {
                $byNisn = null;
            }
        } else {
            /** @var Siswa|null $byNisn */
            $byNisn = Siswa::query()
                ->withTrashed()
                ->where('nisn', $nisn)
                ->where('nis', '!=', $nis)
                ->first();
        }

        // Ambiguous: NIS and NISN point to two different local records.
        if ($byNis !== null && $byNisn !== null && (int) $byNisn->id !== (int) $byNis->id) {
            return ['status' => 'conflict', 'siswa' => null];
        }

        if ($byNisn !== null) {
            // NIS remains the primary identity. A matching NISN with another
            // NIS is not enough to rewrite a local student's identity.
            return ['status' => 'conflict', 'siswa' => null];
        }

        if ($byNis !== null) {
            return ['status' => 'matched', 'siswa' => $byNis];
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

        $classroomId = $this->classroomId($remote);

        // If classroomId is 0 / null, the student is an ALUMNI (must be skipped).
        if ($classroomId <= 0) {
            return 'skipped';
        }

        $kelas = $this->resolveKelas($remote);

        // If remote student has a non-zero classroomId but no local mapping exists,
        // flag as needs_mapping.
        if ($kelas === null) {
            return 'needs_mapping';
        }

        if ($resolved['siswa'] === null) {
            if ($this->personName($remote) === '') {
                return 'error';
            }

            return 'created';
        }

        if ($this->hasProtectedUser($resolved['siswa']->user_id)) {
            return 'conflict';
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

        $classroomId = $this->classroomId($remote);

        // If classroomId is 0 / null, the student is an ALUMNI (must be skipped).
        if ($classroomId <= 0) {
            return 'skipped';
        }

        $kelas = $this->resolveKelas($remote);

        if ($kelas === null) {
            return 'needs_mapping';
        }

        $existing = $resolved['siswa'];

        if ($existing === null) {
            if ($this->personName($remote) === '') {
                return 'error';
            }

            return $this->createStudent($remote, $kelas);
        }

        if ($this->hasProtectedUser($existing->user_id)
            || $this->userEmailBelongsToAnotherAccount(Siswa::generateEmail((string) $remote['nis']), $existing->user_id)) {
            return 'conflict';
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
    private function createStudent(array $remote, ?Kelas $kelas): string
    {
        $nama = $this->personName($remote);
        $nis = (string) $remote['nis'];

        $tanggalLahir = $this->parseTanggalLahir($remote);
        $email = Siswa::generateEmail($nis);

        // Idempotency guard: if a User with this generated email already exists
        // (e.g. concurrent sync runs), update the existing student instead.
        $existingByEmail = User::query()->withTrashed()->where('email', $email)->first();
        if ($existingByEmail !== null) {
            $existingSiswa = Siswa::query()->withTrashed()->where('user_id', $existingByEmail->id)->first();
            if ($existingSiswa !== null) {
                if ($this->hasProtectedUser($existingSiswa->user_id)) {
                    return 'conflict';
                }

                $this->updateStudent($existingSiswa, $remote, $kelas);

                return 'updated';
            }

            return 'conflict';
        }

        /** @var Siswa $siswa */
        $siswa = $this->transaction(function () use ($remote, $nama, $nis, $tanggalLahir, $email, $kelas): Siswa {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $nama,
                'email' => $email,
                'password' => $this->defaultPassword(),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::SISWA->value);

            /** @var Siswa $siswa */
            $siswa = $this->siswaRepository->create([
                'user_id' => $user->id,
                'class_id' => $kelas?->id,
                'nis' => $nis,
                'nisn' => $remote['nisn'] ?? null,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote),
                'tanggal_lahir' => $tanggalLahir,
                'no_telepon' => $remote['no_telepon'] ?? $remote['hp'] ?? null,
                'alamat' => $remote['alamat'] ?? null,
            ]);

            return $siswa;
        });

        $this->rememberSiswa($siswa);

        return 'created';
    }

    /**
     * Update an existing Siswa + User from a SiPintu student record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function updateStudent(Siswa $siswa, array $remote, ?Kelas $kelas): void
    {
        $nama = $this->personName($remote, $siswa->nama);
        $nis = (string) $remote['nis'];
        $tanggalLahir = $this->parseTanggalLahir($remote);
        $email = Siswa::generateEmail($nis);

        $this->transaction(function () use ($siswa, $remote, $nama, $nis, $tanggalLahir, $email, $kelas): void {
            if ($siswa->trashed()) {
                $siswa->restore();
            }

            $user = null;
            if ($this->usersById !== null && $siswa->user_id !== null) {
                $user = $this->usersById[$siswa->user_id] ?? null;
            }

            if ($user === null) {
                $user = $siswa->user()->withTrashed()->first();
                if ($user !== null && $this->usersById !== null) {
                    $this->usersById[$user->id] = $user;
                }
            }

            if ($user === null) {
                $user = $this->userRepository->create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => $this->defaultPassword(),
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(UserRole::SISWA->value);

                if ($this->usersById !== null) {
                    $this->usersById[$user->id] = $user;
                }

                $siswa->user_id = $user->id;
            } elseif ($user->trashed()) {
                $user->restore();
            }

            if (! $user instanceof User) {
                throw new \RuntimeException(
                    "Gagal memperoleh akun User untuk NIS={$nis} saat sinkronisasi."
                );
            }

            // O(1) update with dirty checking to prevent unnecessary queries
            $user->forceFill([
                'name' => $nama,
                'email' => $email,
            ]);
            if ($user->isDirty()) {
                $user->save();
            }

            $siswa->forceFill([
                'nis' => $nis,
                'nisn' => $remote['nisn'] ?? $siswa->nisn,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote) ?? $siswa->jenis_kelamin,
                'tanggal_lahir' => $tanggalLahir ?? $siswa->tanggal_lahir,
                'no_telepon' => $remote['no_telepon'] ?? $remote['hp'] ?? $siswa->no_telepon,
                'alamat' => $remote['alamat'] ?? $siswa->alamat,
                'class_id' => $kelas?->id,
            ]);
            if ($siswa->isDirty()) {
                $siswa->save();
            }
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

        $existing = $this->findGuruByNip($nip);

        if ($existing === null) {
            return $this->personName($remote) === '' ? 'error' : 'created';
        }

        if ($this->hasProtectedUser($existing->user_id)
            || $this->userEmailBelongsToAnotherAccount($this->teacherEmail($remote, $nip), $existing->user_id)) {
            return 'conflict';
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

        $existing = $this->findGuruByNip($nip);

        if ($existing === null) {
            if ($this->personName($remote) === '') {
                return 'error';
            }

            return $this->createTeacher($remote);
        }

        if ($this->hasProtectedUser($existing->user_id)
            || $this->userEmailBelongsToAnotherAccount($this->teacherEmail($remote, $nip), $existing->user_id)) {
            return 'conflict';
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
    private function createTeacher(array $remote): string
    {
        $nama = $this->personName($remote);
        $nip = (string) $remote['nip'];
        $email = $this->teacherEmail($remote, $nip);

        $existingByEmail = User::query()->withTrashed()->where('email', $email)->first();
        if ($existingByEmail !== null) {
            $existingGuru = Guru::query()->withTrashed()->where('user_id', $existingByEmail->id)->first();
            if ($existingGuru !== null) {
                if ($this->hasProtectedUser($existingGuru->user_id)) {
                    return 'conflict';
                }

                $this->updateTeacher($existingGuru, $remote);

                return 'updated';
            }

            return 'conflict';
        }

        /** @var Guru $guru */
        $guru = $this->transaction(function () use ($remote, $nama, $nip, $email): Guru {
            /** @var User $user */
            $user = $this->userRepository->create([
                'name' => $nama,
                'email' => $email,
                'password' => $this->defaultPassword(),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::GURU->value);

            /** @var Guru $guru */
            $guru = $this->guruRepository->create([
                'user_id' => $user->id,
                'nip' => $nip,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote),
                'no_hp' => $remote['no_hp'] ?? $remote['hp'] ?? null,
                'alamat' => $remote['alamat'] ?? null,
            ]);

            return $guru;
        });

        $this->rememberGuru($guru);

        return 'created';
    }

    /**
     * Update an existing Guru + User from a SiPintu teacher record.
     *
     * @param  array<string, mixed>  $remote
     */
    private function updateTeacher(Guru $guru, array $remote): void
    {
        $nama = $this->personName($remote, $guru->nama);
        $nip = (string) $remote['nip'];
        $email = $this->teacherEmail($remote, $nip);

        $this->transaction(function () use ($guru, $remote, $nama, $nip, $email): void {
            if ($guru->trashed()) {
                $guru->restore();
            }

            $user = null;
            if ($this->usersById !== null && $guru->user_id !== null) {
                $user = $this->usersById[$guru->user_id] ?? null;
            }

            if ($user === null) {
                $user = $guru->user()->withTrashed()->first();
                if ($user !== null && $this->usersById !== null) {
                    $this->usersById[$user->id] = $user;
                    $this->usersByEmail[strtolower($user->email)] = $user;
                }
            }

            if ($user === null) {
                $user = $this->userRepository->create([
                    'name' => $nama,
                    'email' => $email,
                    'password' => $this->defaultPassword(),
                    'must_change_password' => false,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole(UserRole::GURU->value);

                $guru->user_id = $user->id;
            } elseif ($user->trashed()) {
                $user->restore();
            }

            // Defensive guard against a null/invalid User (prevents TypeError → HTTP 500).
            if (! $user instanceof User) {
                throw new \RuntimeException(
                    "Gagal memperoleh akun User untuk NIP={$nip} saat sinkronisasi."
                );
            }

            $user->forceFill([
                'name' => $nama,
                'email' => $email,
            ]);
            if ($user->isDirty()) {
                $user->save();
            }

            $guru->forceFill([
                'nip' => $nip,
                'nama' => $nama,
                'jenis_kelamin' => $this->mapJenisKelamin($remote) ?? $guru->jenis_kelamin,
                'no_hp' => $remote['no_hp'] ?? $remote['hp'] ?? $guru->no_hp,
                'alamat' => $remote['alamat'] ?? $guru->alamat,
            ]);
            if ($guru->isDirty()) {
                $guru->save();
            }
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
        $expectedClass = $kelas?->id;

        return (string) $siswa->nis === (string) $remote['nis']
            && (string) $siswa->nisn === $expectedNisn
            && (string) $siswa->nama === $expectedName
            && (string) ($siswa->jenis_kelamin ?? '') === (string) $expectedJk
            && ($siswa->tanggal_lahir ? $siswa->tanggal_lahir->format('Y-m-d') : '') === $expectedTgl
            && (string) ($siswa->no_telepon ?? '') === $expectedPhone
            && (string) ($siswa->alamat ?? '') === $expectedAlamat
            && $siswa->class_id === $expectedClass;
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

    private static ?string $cachedDefaultPasswordHash = null;

    /**
     * Default password for newly created synchronized accounts.
     */
    private function defaultPassword(): string
    {
        return self::$cachedDefaultPasswordHash ??= bcrypt('password');
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
        $classroomId = $this->classroomId($remote);

        if ($classroomId <= 0) {
            return null;
        }

        $kelasId = $this->kelasIdBySipintuClassroomId !== null
            ? $this->kelasIdBySipintuClassroomId[$classroomId] ?? null
            : $this->classroomMappingService->resolveKelasId($classroomId);

        if ($kelasId === null) {
            return null;
        }

        if ($this->kelasById !== null) {
            return $this->kelasById[$kelasId] ?? null;
        }

        /** @var Kelas|null $kelas */
        $kelas = Kelas::query()->find($kelasId);

        return $kelas;
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function classroomId(array $remote): int
    {
        $value = $remote['classroom_id']
            ?? $remote['class_id']
            ?? $remote['kelas_id']
            ?? (is_array($remote['classroom'] ?? null) ? ($remote['classroom']['id'] ?? null) : null);

        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            return 0;
        }

        return max(0, (int) $value);
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function personName(array $remote, string $fallback = ''): string
    {
        $value = $remote['nama'] ?? $remote['nama_panggilan'] ?? $fallback;

        return is_string($value) ? trim($value) : $fallback;
    }

    private function hasProtectedUser(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if ($this->protectedUsersCache !== null && isset($this->protectedUsersCache[$userId])) {
            return $this->protectedUsersCache[$userId];
        }

        $user = $this->usersById[$userId] ?? User::query()->withTrashed()->with('roles')->find($userId);

        $isProtected = $user instanceof User
            && ($user->hasRole(UserRole::SUPER_ADMIN->value) || $user->hasRole(UserRole::DUDI->value));

        if ($this->protectedUsersCache !== null) {
            $this->protectedUsersCache[$userId] = $isProtected;
        }

        return $isProtected;
    }

    private function userEmailBelongsToAnotherAccount(string $email, ?int $userId): bool
    {
        $normalizedEmail = strtolower(trim($email));
        $user = $this->usersByEmail[$normalizedEmail] ?? User::query()
            ->withTrashed()
            ->with('roles')
            ->where('email', $normalizedEmail)
            ->first();

        return $user instanceof User && (int) $user->id !== (int) $userId;
    }

    private function rememberSiswa(Siswa $siswa): void
    {
        if ($this->siswaByNis !== null) {
            $this->siswaByNis[(string) $siswa->nis] = $siswa;
        }

        if ($this->siswaByNisn !== null && $siswa->nisn !== null && $siswa->nisn !== '') {
            $this->siswaByNisn[$siswa->nisn] = $siswa;
        }

        if ($siswa->relationLoaded('user') && $siswa->user !== null) {
            $user = $siswa->user;
            if ($this->usersById !== null) {
                $this->usersById[$user->id] = $user;
            }
            if ($this->usersByEmail !== null) {
                $this->usersByEmail[strtolower($user->email)] = $user;
            }
            if ($this->protectedUsersCache !== null) {
                $this->protectedUsersCache[$user->id] = $user->hasRole(UserRole::SUPER_ADMIN->value)
                    || $user->hasRole(UserRole::DUDI->value);
            }
        }
    }

    private function rememberGuru(Guru $guru): void
    {
        if ($this->guruByNip !== null && $guru->nip !== null && $guru->nip !== '') {
            $this->guruByNip[$guru->nip] = $guru;
        }

        if ($guru->relationLoaded('user') && $guru->user !== null) {
            $user = $guru->user;
            if ($this->usersById !== null) {
                $this->usersById[$user->id] = $user;
            }
            if ($this->usersByEmail !== null) {
                $this->usersByEmail[strtolower($user->email)] = $user;
            }
            if ($this->protectedUsersCache !== null) {
                $this->protectedUsersCache[$user->id] = $user->hasRole(UserRole::SUPER_ADMIN->value)
                    || $user->hasRole(UserRole::DUDI->value);
            }
        }
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

        $locals = $this->localStudentsCache !== null
            ? $this->localStudentsCache->whereNull('deleted_at')
            : Siswa::query()->withoutTrashed()->get(['id', 'nis', 'nisn']);

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

        $locals = $this->localTeachersCache !== null
            ? $this->localTeachersCache->whereNull('deleted_at')
            : Guru::query()->withoutTrashed()->get(['id', 'nip']);

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
            'skipped' => $stats['skipped']++,
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

    /**
     * @return array<string, int>
     */
    private function emptySyncStats(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'unchanged' => 0,
            'conflicts' => 0,
            'needs_mapping' => 0,
            'errors' => 0,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function testConnection(): array
    {
        return $this->siPintuRepository->testConnection();
    }
}
