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
    public function mappedClassroomIds(): array
    {
        /** @var array<int, int> $result */
        $result = $this->newQuery()
            ->pluck('kelas_id', 'classroom_id')
            ->toArray();

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function allWithKelas(): Collection
    {
        /** @var Collection<int, SipintuClassroomMapping> $mappings */
        $mappings = $this->newQuery()
            ->with('kelas')
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
            $created = $this->create([
                'classroom_id' => $classroomId,
                'kelas_id' => $kelasId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $created;
        }

        return $this->update($mapping, $attributes);
    }
}
