<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kelas;
use App\Models\SipintuClassroomMapping;
use App\Models\Siswa;
use App\Repositories\Interfaces\SipintuClassroomMappingRepositoryInterface;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use App\Services\Interfaces\SiPintuServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service layer for the SiPintu classroom mapping feature.
 *
 * Responsibilities:
 *  - Retrieve the unique SiPintu classroom_ids (with student counts) so the
 *    Super Admin can map each one to a local kelas exactly once.
 *  - Persist the classroom_id → kelas mapping in sipintu_classroom_mappings.
 *  - Apply all saved mappings to local students (only updating kelas_id).
 *  - Provide a resolver used by future SiPintu synchronizations so the
 *    mapping is reused automatically.
 */
class SipintuClassroomMappingService extends Service implements SipintuClassroomMappingServiceInterface
{
    public function __construct(
        private readonly SipintuClassroomMappingRepositoryInterface $mappingRepository,
        private readonly SiPintuServiceInterface $siPintuService,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getDashboardData(): array
    {
        $classrooms = [];
        $connected = true;

        try {
            $students = $this->siPintuService->fetchStudents();

            $counts = [];
            foreach ($students as $student) {
                $classroomId = (int) ($student['classroom_id'] ?? 0);
                if ($classroomId <= 0) {
                    continue;
                }
                $counts[$classroomId] = ($counts[$classroomId] ?? 0) + 1;
            }

            ksort($counts);

            foreach ($counts as $classroomId => $count) {
                $classrooms[] = [
                    'classroom_id' => $classroomId,
                    'student_count' => $count,
                ];
            }
        } catch (\Throwable) {
            $connected = false;
        }

        $mappings = $this->mappingRepository->allWithKelas();

        /** @var Collection<int, Kelas> $kelasOptions */
        $kelasOptions = Kelas::query()
            ->with('jurusan')
            ->orderBy('nama')
            ->get();

        return [
            'classrooms' => $classrooms,
            'mappings' => $mappings,
            'kelasOptions' => $kelasOptions,
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

        $kelas = Kelas::query()->find($kelasId);
        if ($kelas === null) {
            throw new \InvalidArgumentException('Kelas tidak ditemukan.');
        }

        return $this->mappingRepository->upsertMapping($classroomId, $kelasId, $userId);
    }

    /**
     * {@inheritDoc}
     */
    public function applyMappings(): array
    {
        $mapped = $this->mappingRepository->mappedClassroomIds();

        if ($mapped === []) {
            return ['updated' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'failed' => 0];

        DB::transaction(function () use ($mapped, &$stats): void {
            // Fetch SiPintu students to know which classroom each NIS belongs to.
            try {
                $students = $this->siPintuService->fetchStudents();
            } catch (\Throwable $e) {
                $stats['failed'] = 1;
                throw $e;
            }

            // Build nis → classroom_id map (only for mapped classroom ids).
            $nisClassroom = [];
            foreach ($students as $student) {
                $nis = (string) ($student['nis'] ?? '');
                $classroomId = (int) ($student['classroom_id'] ?? 0);
                if ($nis === '' || $classroomId <= 0 || ! isset($mapped[$classroomId])) {
                    continue;
                }
                $nisClassroom[$nis] = $classroomId;
            }

            if ($nisClassroom === []) {
                return;
            }

            // Only update students that are present in SiPintu with a mapped classroom.
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

        return $mapping?->kelas_id;
    }

    /**
     * {@inheritDoc}
     */
    public function allMappings(): Collection
    {
        return $this->mappingRepository->allWithKelas();
    }
}
