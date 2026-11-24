<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\SiPintuApiException;
use App\Models\Dudi;
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

    /** @var Collection<int, Siswa>|null */
    private ?Collection $localStudentsCache = null;

    /** @var Collection<int, Guru>|null */
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

    /** @var array<string, Kelas>|null normalized name → Kelas hashmap */
    private ?array $kelasByNormalizedName = null;

    /** @var array<int, int>|null SiPintu classroom_id → local kelas_id */
    private ?array $kelasIdBySipintuClassroomId = null;

    /** @var bool Whether current execution is a dry-run preview */
    private bool $isDryRun = false;

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
        $this->kelasByNormalizedName = [];
        foreach ($localKelas as $kelas) {
            $this->kelasById[$kelas->id] = $kelas;
            $this->kelasByNormalizedName[$this->normalizeKelasName($kelas->nama)] = $kelas;
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

        $teachers = [];
        try {
            $teachers = $this->fetchTeachers();
            Log::info('SiPintu teachers fetched', ['count' => count($teachers)]);
        } catch (SiPintuApiException $e) {
            if (str_contains($e->getMessage(), '404')) {
                Log::warning('SiPintu teachers endpoint returned HTTP 404 (route not found on gateway). Continuing student sync.', [
                    'message' => $e->getMessage(),
                ]);
                $teachers = [];
            } else {
                throw $e;
            }
        } catch (\BadMethodCallException) {
            $teachers = [];
        }

        $dudis = [];
        try {
            $dudis = $this->fetchDudi();
            Log::info('SiPintu DUDI fetched', ['count' => count($dudis)]);
        } catch (SiPintuApiException $e) {
            if (str_contains($e->getMessage(), '404')) {
                $dudis = [];
            } else {
                throw $e;
            }
        } catch (\BadMethodCallException) {
            $dudis = [];
        }

        return [
            'students' => $students,
            'teachers' => $teachers,
            'dudis' => $dudis,
        ];
    }

    /**
     * Read-only preview: classify remote students into 7 categories without
     * writing anything to the database.
     *
     * Categories: baru, diperbarui, tidak_berubah, konflik, perlu_pemetaan,
     * tidak_ditemukan, error.
     *
     * @return array<string, int|string>
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
     * @return array<string, int|string>
     */
    public function previewTeachers(): array
    {
        $this->preloadCaches();

        try {
            $teachers = $this->fetchTeachers();
        } catch (SiPintuApiException $e) {
            if (str_contains($e->getMessage(), '404')) {
                Log::warning('SiPintu teachers endpoint returned 404 during preview (not yet available on gateway).', [
                    'message' => $e->getMessage(),
                ]);

                $empty = $this->emptyPreview();
                $empty['endpoint_unavailable'] = 1;
                $empty['endpoint_message'] = 'Endpoint data Guru (/api/v1/sijuna/teachers) belum tersedia di server SiPintu (HTTP 404).';

                return $empty;
            }

            throw $e;
        }

        return $this->processTeacherRecords($teachers, [
            'dry_run' => true,
            'stats' => $this->emptyPreview(),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
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
     * @return array<string, int>
     */
    public function syncTeachers(?array $teachers = null): array
    {
        $this->preloadCaches();

        $resolvedTeachers = $teachers;
        $isUnavailable = false;

        if ($resolvedTeachers === null) {
            try {
                $resolvedTeachers = $this->fetchTeachers();
            } catch (SiPintuApiException $e) {
                if (str_contains($e->getMessage(), '404')) {
                    $resolvedTeachers = [];
                    $isUnavailable = true;
                } else {
                    throw $e;
                }
            }
        }

        $stats = $this->processTeacherRecords($resolvedTeachers, [
            'dry_run' => false,
            'stats' => $this->emptySyncStats(),
        ]);

        if ($isUnavailable) {
            $stats['endpoint_unavailable'] = 1;
        }

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchDudi(?string $search = null): array
    {
        return $this->siPintuRepository->fetchDudi($search);
    }

    /**
     * Read-only preview of DUDI sync: classify remote DUDI into categories
     * without modifying the database.
     *
     * @return array<string, int|string>
     */
    public function previewDudi(): array
    {
        $this->preloadCaches();

        try {
            $dudis = $this->fetchDudi();
        } catch (SiPintuApiException $e) {
            if (str_contains($e->getMessage(), '404')) {
                Log::warning('SiPintu DUDI endpoint returned 404 during preview (not yet available on gateway).', [
                    'message' => $e->getMessage(),
                ]);

                $empty = $this->emptyPreview();
                $empty['endpoint_unavailable'] = 1;
                $empty['endpoint_message'] = 'Endpoint Master Data DUDI (/api/v1/sijuna/dudi) belum tersedia di server SiPintu (HTTP 404). Data master DUDI lokal dan penempatan PKL tetap aman.';

                return $empty;
            }

            throw $e;
        } catch (\BadMethodCallException) {
            $empty = $this->emptyPreview();
            $empty['endpoint_unavailable'] = 1;
            $empty['endpoint_message'] = 'Endpoint Master Data DUDI (/api/v1/sijuna/dudi) belum tersedia di server SiPintu (HTTP 404). Data master DUDI lokal dan penempatan PKL tetap aman.';

            return $empty;
        }

        return $this->processDudiRecords($dudis, [
            'dry_run' => true,
            'stats' => $this->emptyPreview(),
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
     */
    public function syncDudi(?array $dudis = null): array
    {
        $this->preloadCaches();

        $resolvedDudis = $dudis;
        $isUnavailable = false;

        if ($resolvedDudis === null) {
            try {
                $resolvedDudis = $this->fetchDudi();
            } catch (SiPintuApiException $e) {
                if (str_contains($e->getMessage(), '404')) {
                    $resolvedDudis = [];
                    $isUnavailable = true;
                } else {
                    throw $e;
                }
            } catch (\BadMethodCallException) {
                $resolvedDudis = [];
                $isUnavailable = true;
            }
        }

        $stats = $this->processDudiRecords($resolvedDudis, [
            'dry_run' => false,
            'stats' => $this->emptySyncStats(),
        ]);

        if ($isUnavailable) {
            $stats['endpoint_unavailable'] = 1;
        }

        return $stats;
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
        $this->isDryRun = $dryRun;
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

            try {
                $outcome = $dryRun
                    ? $this->classifyStudent($remote)
                    : $this->syncOneStudent($remote);
            } catch (\Throwable $e) {
                Log::error('Student record failed during SiPintu sync', [
                    'nis' => $nis,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                    'dry_run' => $dryRun,
                ]);
                $outcome = 'error';
            }

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

            try {
                $outcome = $dryRun ? $this->classifyTeacher($remote) : $this->syncOneTeacher($remote);
            } catch (\Throwable $e) {
                Log::error('Teacher record failed during SiPintu sync', [
                    'nip' => $nip,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                    'dry_run' => $dryRun,
                ]);
                $outcome = 'error';
            }

            $this->applyRecordOutcome(
                $stats,
                $dryRun,
                $outcome,
            );
        }

        if ($dryRun) {
            $stats['total_remote'] = count($teachers);
            $stats['tidak_ditemukan'] = $this->countLocalTeachersMissingFrom($remoteNipSet);
        }

        return $stats;
    }

    /**
     * Process DUDI records for both preview and live synchronization.
     *
     * @param  array<int, mixed>  $dudis
     * @param  array{dry_run: bool, stats: array<string, int>}  $context
     * @return array<string, int>
     */
    private function processDudiRecords(array $dudis, array $context): array
    {
        $dryRun = $context['dry_run'];
        $stats = $context['stats'];
        $seenId = [];
        $remoteIdSet = [];

        foreach ($dudis as $index => $remote) {
            if (! is_array($remote)) {
                $this->recordInvalidRemote($stats, $dryRun, 'dudi', $index, null, 'record is not an object');

                continue;
            }

            $rawId = $remote['id'] ?? $remote['dudi_id'] ?? null;
            $sipintuId = $this->identifier($rawId);
            $namaPerusahaan = trim((string) ($remote['nama_perusahaan'] ?? $remote['nama'] ?? ''));

            if ($sipintuId === null) {
                $this->recordInvalidRemote($stats, $dryRun, 'dudi', $index, null, 'DUDI ID is missing');

                continue;
            }

            if ($namaPerusahaan === '') {
                $this->recordInvalidRemote($stats, $dryRun, 'dudi', $index, $sipintuId, 'nama_perusahaan is missing');

                continue;
            }

            if (isset($seenId[$sipintuId])) {
                Log::warning('DUDI skipped during SiPintu sync: duplicate ID in response', [
                    'sipintu_id' => $sipintuId,
                    'record_index' => $index,
                    'dry_run' => $dryRun,
                ]);
                $this->applyRecordOutcome($stats, $dryRun, 'conflict');

                continue;
            }

            $seenId[$sipintuId] = true;
            $remote['sipintu_id'] = $sipintuId;
            $remote['nama_perusahaan'] = $namaPerusahaan;
            $remoteIdSet[$sipintuId] = true;

            try {
                $outcome = $dryRun ? $this->classifyDudi($remote) : $this->syncOneDudi($remote);
            } catch (\Throwable $e) {
                Log::error('DUDI record failed during SiPintu sync', [
                    'sipintu_id' => $sipintuId,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                    'dry_run' => $dryRun,
                ]);
                $outcome = 'error';
            }

            $this->applyRecordOutcome($stats, $dryRun, $outcome);
        }

        if ($dryRun) {
            $stats['total_remote'] = count($dudis);
            $stats['tidak_ditemukan'] = $this->countLocalDudisMissingFrom($remoteIdSet);
        }

        return $stats;
    }

    /**
     * Classify a remote DUDI for preview mode.
     *
     * @param  array<string, mixed>  $remote
     */
    private function classifyDudi(array $remote): string
    {
        $sipintuId = (string) $remote['sipintu_id'];
        $existing = Dudi::query()->withTrashed()->where('sipintu_id', $sipintuId)->first();

        if ($existing === null) {
            $email = ! empty($remote['email_perusahaan']) ? trim((string) $remote['email_perusahaan']) : null;
            if ($email !== null && Dudi::query()->withTrashed()->where('email_perusahaan', $email)->exists()) {
                return 'conflict';
            }

            return 'created';
        }

        if ($this->dudiUnchanged($existing, $remote)) {
            return 'unchanged';
        }

        return 'updated';
    }

    /**
     * Synchronize a single remote DUDI record inside its own transaction.
     *
     * @param  array<string, mixed>  $remote
     */
    private function syncOneDudi(array $remote): string
    {
        $sipintuId = (string) $remote['sipintu_id'];

        return \Illuminate\Support\Facades\DB::transaction(function () use ($remote, $sipintuId): string {
            /** @var Dudi|null $existing */
            $existing = Dudi::query()->withTrashed()->where('sipintu_id', $sipintuId)->lockForUpdate()->first();

            if ($existing === null) {
                $email = ! empty($remote['email_perusahaan']) ? trim((string) $remote['email_perusahaan']) : null;
                if ($email !== null && Dudi::query()->withTrashed()->where('email_perusahaan', $email)->exists()) {
                    Log::warning('DUDI sync conflict: email_perusahaan already exists on another record', [
                        'sipintu_id' => $sipintuId,
                        'email' => $email,
                    ]);

                    return 'conflict';
                }

                $statusAktif = isset($remote['status_aktif']) ? (bool) $remote['status_aktif'] : true;

                Dudi::create([
                    'sipintu_id' => $sipintuId,
                    'user_id' => null, // Strictly null: no auto-user creation
                    'nama_perusahaan' => $remote['nama_perusahaan'],
                    'penanggung_jawab' => $remote['penanggung_jawab'] ?? $remote['pimpinan'] ?? null,
                    'email_perusahaan' => $email,
                    'no_telepon' => $remote['no_telepon'] ?? $remote['telepon'] ?? null,
                    'bidang_usaha' => $remote['bidang_usaha'] ?? null,
                    'alamat' => $remote['alamat'] ?? null,
                    'status_aktif' => $statusAktif,
                    'sso_enabled' => false,
                    'sipintu_synced_at' => Carbon::now(),
                ]);

                return 'created';
            }

            if ($existing->trashed()) {
                $existing->restore();
            }

            if ($this->dudiUnchanged($existing, $remote)) {
                $existing->update(['sipintu_synced_at' => Carbon::now()]);

                return 'unchanged';
            }

            $updateData = [
                'nama_perusahaan' => $remote['nama_perusahaan'],
                'penanggung_jawab' => $remote['penanggung_jawab'] ?? $remote['pimpinan'] ?? $existing->penanggung_jawab,
                'no_telepon' => $remote['no_telepon'] ?? $remote['telepon'] ?? $existing->no_telepon,
                'bidang_usaha' => $remote['bidang_usaha'] ?? $existing->bidang_usaha,
                'alamat' => $remote['alamat'] ?? $existing->alamat,
                'sipintu_synced_at' => Carbon::now(),
            ];

            if (isset($remote['status_aktif'])) {
                $updateData['status_aktif'] = (bool) $remote['status_aktif'];
            }

            if (! empty($remote['email_perusahaan']) && $remote['email_perusahaan'] !== $existing->email_perusahaan) {
                $collision = Dudi::query()
                    ->where('email_perusahaan', $remote['email_perusahaan'])
                    ->where('id', '!=', $existing->id)
                    ->exists();

                if (! $collision) {
                    $updateData['email_perusahaan'] = $remote['email_perusahaan'];
                }
            }

            $existing->update($updateData);

            return 'updated';
        });
    }

    /**
     * Check if a remote DUDI record matches local DUDI without modifications.
     *
     * @param  array<string, mixed>  $remote
     */
    private function dudiUnchanged(Dudi $existing, array $remote): bool
    {
        $namaMatches = trim($existing->nama_perusahaan) === trim((string) $remote['nama_perusahaan']);
        $pimpinanRemote = $remote['penanggung_jawab'] ?? $remote['pimpinan'] ?? null;
        $pimpinanMatches = $pimpinanRemote === null || trim((string) ($existing->penanggung_jawab ?? '')) === trim((string) $pimpinanRemote);
        $telpRemote = $remote['no_telepon'] ?? $remote['telepon'] ?? null;
        $telpMatches = $telpRemote === null || trim((string) ($existing->no_telepon ?? '')) === trim((string) $telpRemote);
        $alamatRemote = $remote['alamat'] ?? null;
        $alamatMatches = $alamatRemote === null || trim((string) ($existing->alamat ?? '')) === trim((string) $alamatRemote);

        $statusMatches = true;
        if (isset($remote['status_aktif'])) {
            $statusMatches = (bool) $existing->status_aktif === (bool) $remote['status_aktif'];
        }

        return $namaMatches && $pimpinanMatches && $telpMatches && $alamatMatches && $statusMatches;
    }

    /**
     * Count local DUDIs having sipintu_id that are absent from the remote set.
     *
     * @param  array<string, bool>  $remoteIdSet
     */
    private function countLocalDudisMissingFrom(array $remoteIdSet): int
    {
        $localDudisWithSipintuId = Dudi::query()
            ->withoutTrashed()
            ->whereNotNull('sipintu_id')
            ->pluck('sipintu_id')
            ->all();

        $missing = 0;
        foreach ($localDudisWithSipintuId as $sipId) {
            if (! isset($remoteIdSet[(string) $sipId])) {
                $missing++;
            }
        }

        return $missing;
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
        $kelas = null;

        if ($classroomId > 0) {
            $kelas = $this->resolveKelas($remote);

            // If remote student has a non-zero classroomId but no local mapping exists,
            // flag as needs_mapping.
            if ($kelas === null) {
                return 'needs_mapping';
            }
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
        $kelas = null;

        if ($classroomId > 0) {
            $kelas = $this->resolveKelas($remote);

            if ($kelas === null) {
                return 'needs_mapping';
            }
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

            if ($this->isNonaktif($remote)) {
                $siswa->delete();
                $user->delete();
            }

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
            $user = null;
            if ($this->usersById !== null) {
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

            $isNonaktif = $this->isNonaktif($remote);
            if ($isNonaktif) {
                if (! $siswa->trashed()) {
                    $siswa->delete();
                }
                if (! $user->trashed()) {
                    $user->delete();
                }
            } else {
                if ($siswa->trashed()) {
                    $siswa->restore();
                }
                if ($user->trashed()) {
                    $user->restore();
                }
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
            if ($this->usersById !== null) {
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
            && $siswa->class_id === $expectedClass
            && $siswa->trashed() === $this->isNonaktif($remote);
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
     * Priority:
     *  1. Admin-configured mapping in sipintu_classroom_mappings (by classroom_id).
     *  2. Matching classroom name from remote student to local kelas.nama (case-insensitive).
     *     When found during live sync, the mapping is automatically saved for future use.
     *  3. Returns null if unmapped so caller can classify student appropriately.
     *
     * @param  array<string, mixed>  $remote
     */
    private function resolveKelas(array $remote): ?Kelas
    {
        $classroomId = $this->classroomId($remote);

        // 1. Check explicit manual mapping in sipintu_classroom_mappings (highest priority)
        if ($classroomId > 0) {
            $kelasId = $this->kelasIdBySipintuClassroomId !== null
                ? $this->kelasIdBySipintuClassroomId[$classroomId] ?? null
                : $this->classroomMappingService->resolveKelasId($classroomId);

            if ($kelasId !== null) {
                if ($this->kelasById !== null) {
                    return $this->kelasById[$kelasId] ?? null;
                }

                return Kelas::query()->find($kelasId);
            }
        }

        // 2. Check classroom name from remote object/field and match against local Kelas by name
        $classroomName = null;
        if (is_array($remote['classroom'] ?? null) && ! empty($remote['classroom']['name'])) {
            $classroomName = (string) $remote['classroom']['name'];
        } elseif (! empty($remote['classroom_name']) && is_string($remote['classroom_name'])) {
            $classroomName = $remote['classroom_name'];
        } elseif (! empty($remote['kelas']) && is_string($remote['kelas'])) {
            $classroomName = $remote['kelas'];
        }

        if ($classroomName !== null) {
            $norm = $this->normalizeKelasName($classroomName);
            $matchedKelas = null;

            if ($this->kelasByNormalizedName !== null) {
                $matchedKelas = $this->kelasByNormalizedName[$norm] ?? null;
            } else {
                $matchedKelas = Kelas::query()
                    ->whereRaw('UPPER(TRIM(nama)) = ?', [$norm])
                    ->first();
            }

            if ($matchedKelas !== null) {
                // Persist the mapping during live sync if classroomId is known and not yet mapped
                if (! $this->isDryRun && $classroomId > 0 && ($this->kelasIdBySipintuClassroomId[$classroomId] ?? null) === null) {
                    try {
                        $this->classroomMappingService->saveMapping($classroomId, $matchedKelas->id, null);
                        if ($this->kelasIdBySipintuClassroomId !== null) {
                            $this->kelasIdBySipintuClassroomId[$classroomId] = $matchedKelas->id;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Gagal menyimpan pemetaan kelas otomatis', [
                            'classroom_id' => $classroomId,
                            'kelas_id' => $matchedKelas->id,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }

                return $matchedKelas;
            }
        }

        return null;
    }

    /**
     * Determine the numeric SiPintu classroom ID for a student.
     * Graduated/alumni students without an active classroom assignment return 0.
     *
     * @param  array<string, mixed>  $remote
     */
    private function classroomId(array $remote): int
    {
        $rawClassroomId = $remote['classroom_id'] ?? $remote['class_id'] ?? $remote['kelas_id'] ?? null;
        if (is_int($rawClassroomId) || (is_string($rawClassroomId) && ctype_digit($rawClassroomId))) {
            $id = (int) $rawClassroomId;
            if ($id > 0) {
                return $id;
            }
        }

        // Graduated / alumni students without active classroom_id do not have mandatory classroom requirement
        if ($this->isAlumni($remote)) {
            return 0;
        }

        // Fallback to classroom object id for active students
        $objId = is_array($remote['classroom'] ?? null) ? ($remote['classroom']['id'] ?? null) : null;
        if (is_int($objId) || (is_string($objId) && ctype_digit($objId))) {
            return max(0, (int) $objId);
        }

        return 0;
    }

    private function normalizeKelasName(string $name): string
    {
        return strtoupper(trim((string) preg_replace('/\s+/', ' ', $name)));
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function isAlumni(array $remote): bool
    {
        if (($remote['graduated'] ?? false) === true) {
            return true;
        }

        $status = $remote['status'] ?? null;
        if ($status === 2 || $status === '2' || strtolower((string) $status) === 'alumni' || strtolower((string) $status) === 'lulus') {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    private function isNonaktif(array $remote): bool
    {
        if (! empty($remote['deleted_at'])) {
            return true;
        }

        $status = $remote['status'] ?? null;
        if ($status === 0 || $status === '0') {
            return true;
        }

        if (is_string($status)) {
            $statusLower = strtolower(trim($status));
            if (in_array($statusLower, ['nonaktif', 'inactive', 'pindah', 'keluar', 'dikeluarkan', 'drop out', 'do'], true)) {
                return true;
            }
        }

        return false;
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
            /** @var Siswa $siswa */
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
            /** @var Guru $guru */
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

    /**
     * {@inheritDoc}
     */
    public function checkEndpointStatus(string $endpoint): array
    {
        return $this->siPintuRepository->checkEndpointStatus($endpoint);
    }

    /**
     * {@inheritDoc}
     */
    public function getDiagnostics(): array
    {
        $conn = $this->testConnection();
        $studentStatus = $this->checkEndpointStatus('/api/v1/sijuna/students');
        $teacherStatus = $this->checkEndpointStatus('/api/v1/sijuna/teachers');
        $dudiStatus = $this->checkEndpointStatus('/api/v1/sijuna/dudi');

        return [
            'gateway' => [
                'name' => 'Gateway SiPintu (/api/v1/health)',
                'status' => $conn['status'],
                'http_status' => $conn['http_status'],
                'message' => $conn['message'],
            ],
            'profile' => [
                'name' => 'Endpoint Profil Pengguna (/api/v1/user)',
                'status' => true,
                'http_status' => null,
                'message' => 'Endpoint OAuth SSO aktif untuk autentikasi login pengguna.',
            ],
            'students' => [
                'name' => 'Endpoint Data Siswa (/api/v1/sijuna/students)',
                'status' => $studentStatus['success'],
                'http_status' => $studentStatus['status'],
                'message' => $studentStatus['message'],
            ],
            'teachers' => [
                'name' => 'Endpoint Data Guru (/api/v1/sijuna/teachers)',
                'status' => $teacherStatus['success'],
                'http_status' => $teacherStatus['status'],
                'message' => $teacherStatus['message'],
            ],
            'dudi' => [
                'name' => 'Endpoint Data DUDI (/api/v1/sijuna/dudi)',
                'status' => $dudiStatus['success'],
                'http_status' => $dudiStatus['status'],
                'message' => $dudiStatus['message'],
            ],
        ];
    }
}
