<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SipintuClassroomMapping;
use App\Repositories\Interfaces\SipintuClassroomMappingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<SipintuClassroomMapping>
 */
class SipintuClassroomMappingRepository extends EloquentRepository implements SipintuClassroomMappingRepositoryInterface
{
    public function __construct(SipintuClassroomMapping $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findByClassroomId(int $classroomId): ?SipintuClassroomMapping
    {
        /** @var SipintuClassroomMapping|null $mapping */
        $mapping = $this->newQuery()
            ->where('classroom_id', $classroomId)
            ->first();

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function findByKelasIdExcludingClassroom(int $kelasId, int $excludeClassroomId): ?SipintuClassroomMapping
    {
        /** @var SipintuClassroomMapping|null $mapping */
        $mapping = $this->newQuery()
            ->where('kelas_id', $kelasId)
            ->where('classroom_id', '!=', $excludeClassroomId)
            ->first();

        return $mapping;
    }

    /**
     * {@inheritDoc}
     */
    public function mappedClassroomIds(): array
    {
        /** @var array<int, int> $result */
        $result = $this->newQuery()
            ->pluck('kelas_id', 'classroom_id')
            ->mapWithKeys(static fn (mixed $kelasId, mixed $classroomId): array => [(int) $classroomId => (int) $kelasId])
            ->all();

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function allWithKelas(): Collection
    {
        /** @var Collection<int, SipintuClassroomMapping> $mappings */
        $mappings = $this->newQuery()
            ->with(['kelas' => static fn (mixed $query): mixed => $query->withTrashed()->with('jurusan')])
            ->orderBy('classroom_id')
            ->get();

        return $mappings;
    }

    /**
     * {@inheritDoc}
     */
    public function upsertMapping(int $classroomId, int $kelasId, ?int $userId): SipintuClassroomMapping
    {
        $attributes = ['kelas_id' => $kelasId];

        if ($userId !== null) {
            $attributes['updated_by'] = $userId;
        }

        $mapping = $this->findByClassroomId($classroomId);

        if ($mapping === null) {
            return $this->create([
                'classroom_id' => $classroomId,
                'kelas_id' => $kelasId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        return $this->update($mapping, $attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMappingById(int $mappingId): bool
    {
        $mapping = $this->newQuery()->find($mappingId);
        if ($mapping === null) {
            return false;
        }

        return (bool) $mapping->delete();
    }
}
