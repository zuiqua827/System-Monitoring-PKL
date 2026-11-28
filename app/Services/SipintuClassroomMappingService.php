<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
use App\Models\Siswa;
use App\Repositories\Interfaces\SipintuClassroomMappingRepositoryInterface;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use App\Services\Support\SipintuClassroomMatcher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service layer for the SiPintu classroom mapping feature.
 *
 * Responsibilities:
 *  - Retrieve unique SiPintu classroom_ids used by students (with student counts and class metadata).
 *  - Enforce 1-to-1 conflict-free classroom_id → kelas persistence.
 *  - Detect broken/invalid mappings (trashed/missing kelas or duplicate target conflicts).
 *  - Provide strict auto-match only when name, tingkat, and jurusan match unambiguously.
 *  - Apply saved valid mappings to existing local students.
 */
class SipintuClassroomMappingService extends Service implements SipintuClassroomMappingServiceInterface
{
    public function __construct(
        private readonly SipintuClassroomMappingRepositoryInterface $mappingRepository,
        private readonly SiPintuRepositoryInterface $siPintuRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getDashboardData(): array
    {
        $remoteSamples = [];
        $counts = [];
        $connected = true;

        try {
            $students = $this->siPintuRepository->fetchStudents();

            foreach ($students as $student) {
                if (! is_array($student)) {
                    continue;
                }

                $classroomId = SipintuClassroomMatcher::extractClassroomId($student);
                if ($classroomId <= 0) {
                    continue;
                }

                $counts[$classroomId] = ($counts[$classroomId] ?? 0) + 1;

                if (! isset($remoteSamples[$classroomId])) {
                    $remoteSamples[$classroomId] = $student;
                }
            }

            ksort($counts);
        } catch (\Throwable) {
            $connected = false;
        }

        $mappings = $this->mappingRepository->allWithKelas();

        /** @var Collection<int, Kelas> $kelasOptions */
        $kelasOptions = Kelas::query()
            ->with('jurusan')
            ->orderBy('nama')
            ->get();

        // Build kelas_id => count & map for conflict/validity checks.
        $kelasUsage = [];
        $kelasIdToClassroomId = [];
        foreach ($mappings as $mapping) {
            $kelasUsage[$mapping->kelas_id][] = $mapping->classroom_id;
            if (! isset($kelasIdToClassroomId[$mapping->kelas_id])) {
                $kelasIdToClassroomId[$mapping->kelas_id] = $mapping->classroom_id;
            }
        }

        $mappingByClassroomId = [];
        $mappingHealth = [];
        $invalidCount = 0;

        foreach ($mappings as $mapping) {
            $mappingByClassroomId[$mapping->classroom_id] = $mapping;
            $inRemote = isset($counts[$mapping->classroom_id]);

            $isValid = true;
            $reason = null;

            if ($mapping->kelas === null) {
                $isValid = false;
                $reason = 'Kelas lokal tidak ditemukan (sudah dihapus permanen).';
            } elseif ($mapping->kelas->trashed()) {
                $isValid = false;
                $reason = "Kelas lokal '{$mapping->kelas->nama}' berstatus nonaktif (soft-deleted).";
            } elseif (count($kelasUsage[$mapping->kelas_id] ?? []) > 1) {
                $isValid = false;
                $others = array_values(array_filter(
                    $kelasUsage[$mapping->kelas_id],
                    static fn (int $id): bool => $id !== $mapping->classroom_id
                ));
                $reason = 'Konflik 1-ke-banyak: kelas lokal juga dipetakan ke Classroom ID '.implode(', ', $others).'.';
            }

            if (! $isValid) {
                $invalidCount++;
            }

            $mappingHealth[$mapping->id] = [
                'is_valid' => $isValid,
                'reason' => $reason,
                'in_remote' => $inRemote,
            ];
        }

        $classrooms = [];
        $mappedCount = 0;
        $unmappedCount = 0;
        $autoMatchableCount = 0;
        $totalStudents = 0;

        foreach ($counts as $classroomId => $count) {
            $totalStudents += $count;
            $sample = $remoteSamples[$classroomId];
            $classroomName = SipintuClassroomMatcher::extractClassroomName($sample);
            $tingkat = SipintuClassroomMatcher::extractRemoteTingkat($sample, $classroomName);
            $jurusan = SipintuClassroomMatcher::extractRemoteJurusan($sample, $classroomName);

            $existing = $mappingByClassroomId[$classroomId] ?? null;
            $existingHealthy = $existing !== null && ($mappingHealth[$existing->id]['is_valid'] ?? false);

            $suggestedKelasId = null;
            $suggestedKelasNama = null;
            $autoMatchReason = $existingHealthy ? 'already_mapped' : 'unmapped';

            if ($existingHealthy) {
                $mappedCount++;
            } else {
                $unmappedCount++;
                $match = SipintuClassroomMatcher::findUniqueStrictMatch($sample, $kelasOptions, $kelasIdToClassroomId);
                $autoMatchReason = $match['reason'];
                if ($match['kelas'] !== null) {
                    $suggestedKelasId = $match['kelas']->id;
                    $suggestedKelasNama = $match['kelas']->nama;
                    $autoMatchableCount++;
                }
            }

            $classrooms[] = [
                'classroom_id' => $classroomId,
                'classroom_name' => $classroomName,
                'tingkat' => $tingkat,
                'jurusan' => $jurusan,
                'student_count' => $count,
                'suggested_kelas_id' => $suggestedKelasId,
                'suggested_kelas_nama' => $suggestedKelasNama,
                'auto_match_reason' => $autoMatchReason,
            ];
        }

        return [
            'classrooms' => $classrooms,
            'mappings' => $mappings,
            'mappingHealth' => $mappingHealth,
            'kelasOptions' => $kelasOptions,
            'summary' => [
                'total_remote' => count($classrooms),
                'mapped' => $mappedCount,
                'unmapped' => $unmappedCount,
                'invalid' => $invalidCount,
                'auto_matchable' => $autoMatchableCount,
                'total_students' => $totalStudents,
            ],
            'connected' => $connected,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function saveMapping(int $classroomId, int $kelasId, ?int $userId): SipintuClassroomMapping
    {
        if ($classroomId <= 0) {
            throw new \InvalidArgumentException('Classroom ID tidak valid.');
        }

        /** @var Kelas|null $kelas */
        $kelas = Kelas::query()->withoutTrashed()->find($kelasId);
        if ($kelas === null) {
            throw new \InvalidArgumentException('Kelas lokal tidak ditemukan atau sudah dinonaktifkan.');
        }

        $conflict = $this->mappingRepository->findByKelasIdExcludingClassroom($kelasId, $classroomId);
        if ($conflict !== null) {
            throw new \InvalidArgumentException(
                "Konflik pemetaan: kelas {$kelas->nama} sudah dipetakan ke Classroom ID {$conflict->classroom_id}. Satu kelas lokal hanya dapat dipetakan ke satu Classroom ID SiPintu."
            );
        }

        $mapping = $this->mappingRepository->upsertMapping($classroomId, $kelasId, $userId);
        Cache::forget('sipintu_dashboard_remote_counts');

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMapping(int $mappingId): bool
    {
        $deleted = $this->mappingRepository->deleteMappingById($mappingId);
        if ($deleted) {
            Cache::forget('sipintu_dashboard_remote_counts');
        }

        return $deleted;
    }

    /**
     * {@inheritDoc}
     */
    public function autoMapExactMatches(?int $userId): array
    {
        $students = $this->siPintuRepository->fetchStudents();

        $remoteSamples = [];
        foreach ($students as $student) {
            if (! is_array($student)) {
                continue;
            }
            $classroomId = SipintuClassroomMatcher::extractClassroomId($student);
            if ($classroomId > 0 && ! isset($remoteSamples[$classroomId])) {
                $remoteSamples[$classroomId] = $student;
            }
        }

        ksort($remoteSamples);

        $existingByClassroom = $this->mappingRepository->mappedClassroomIds();
        $kelasOptions = Kelas::query()->with('jurusan')->orderBy('nama')->get();
        $activeKelasIds = $kelasOptions->pluck('id')->all();

        $kelasIdToClassroomId = [];
        foreach ($existingByClassroom as $cId => $kId) {
            if (in_array($kId, $activeKelasIds, true)) {
                $kelasIdToClassroomId[$kId] = $cId;
            }
        }

        $stats = [
            'mapped' => 0,
            'skipped_unmatched' => 0,
            'skipped_conflict' => 0,
            'already_mapped' => 0,
        ];

        DB::transaction(function () use ($remoteSamples, $existingByClassroom, $activeKelasIds, $kelasOptions, &$kelasIdToClassroomId, $userId, &$stats): void {
            foreach ($remoteSamples as $classroomId => $sample) {
                $existingKelasId = $existingByClassroom[$classroomId] ?? null;
                if ($existingKelasId !== null && in_array($existingKelasId, $activeKelasIds, true)) {
                    $stats['already_mapped']++;

                    continue;
                }

                $match = SipintuClassroomMatcher::findUniqueStrictMatch($sample, $kelasOptions, $kelasIdToClassroomId);
                if ($match['kelas'] === null) {
                    if ($match['reason'] === 'kelas_already_mapped_to_another_classroom' || $match['reason'] === 'ambiguous_multiple_matches') {
                        $stats['skipped_conflict']++;
                    } else {
                        $stats['skipped_unmatched']++;
                    }

                    continue;
                }

                $this->mappingRepository->upsertMapping($classroomId, $match['kelas']->id, $userId);
                $kelasIdToClassroomId[$match['kelas']->id] = $classroomId;
                $stats['mapped']++;
            }
        });

        Cache::forget('sipintu_dashboard_remote_counts');

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function applyMappings(): array
    {
        $rawMapped = $this->mappingRepository->mappedClassroomIds();

        if ($rawMapped === []) {
            return ['updated' => 0, 'skipped' => 0, 'failed' => 0];
        }

        // Only apply mappings pointing to existing active (non-trashed) local Kelas.
        $activeKelasIds = array_flip(Kelas::query()->withoutTrashed()->pluck('id')->all());
        $mapped = [];
        $invalidMappingCount = 0;

        foreach ($rawMapped as $classroomId => $kelasId) {
            if (isset($activeKelasIds[$kelasId])) {
                $mapped[$classroomId] = $kelasId;
            } else {
                $invalidMappingCount++;
            }
        }

        if ($mapped === []) {
            return ['updated' => 0, 'skipped' => 0, 'failed' => $invalidMappingCount];
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'failed' => $invalidMappingCount];

        DB::transaction(function () use ($mapped, &$stats): void {
            try {
                $students = $this->siPintuRepository->fetchStudents();
            } catch (\Throwable $e) {
                $stats['failed']++;
                throw $e;
            }

            $nisClassroom = [];
            foreach ($students as $student) {
                if (! is_array($student)) {
                    continue;
                }

                $nis = trim((string) ($student['nis'] ?? ''));
                $classroomId = SipintuClassroomMatcher::extractClassroomId($student);
                if ($nis === '' || $classroomId <= 0 || ! isset($mapped[$classroomId])) {
                    continue;
                }
                $nisClassroom[$nis] = $classroomId;
            }

            if ($nisClassroom === []) {
                return;
            }

            $localStudents = Siswa::query()
                ->withoutTrashed()
                ->whereIn('nis', array_keys($nisClassroom))
                ->get(['id', 'nis', 'class_id']);

            foreach ($localStudents as $siswa) {
                $classroomId = $nisClassroom[$siswa->nis];
                $kelasId = $mapped[$classroomId];

                if ((int) $siswa->class_id === $kelasId) {
                    $stats['skipped']++;

                    continue;
                }

                try {
                    $siswa->forceFill(['class_id' => $kelasId])->save();
                    $stats['updated']++;
                } catch (\Throwable) {
                    $stats['failed']++;
                }
            }
        });

        return $stats;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveKelasId(int $classroomId): ?int
    {
        if ($classroomId <= 0) {
            return null;
        }

        $mapping = $this->mappingRepository->findByClassroomId($classroomId);
        if ($mapping === null) {
            return null;
        }

        $kelasActive = Kelas::query()->withoutTrashed()->whereKey($mapping->kelas_id)->exists();

        return $kelasActive ? $mapping->kelas_id : null;
    }

    /**
     * {@inheritDoc}
     */
    public function allMappings(): Collection
    {
        return $this->mappingRepository->allWithKelas();
    }
}
