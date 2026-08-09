<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\SipintuClassroomMapping;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<SipintuClassroomMapping>
 */
interface SipintuClassroomMappingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a mapping by its SiPintu classroom_id.
     */
    public function findByClassroomId(int $classroomId): ?SipintuClassroomMapping;

    /**
     * Get all classroom_ids that have a mapping.
     *
     * @return array<int, int>  [classroom_id => kelas_id]
     */
    public function mappedClassroomIds(): array;

    /**
     * Get all mappings with their kelas relationship eager-loaded.
     *
     * @return Collection<int, SipintuClassroomMapping>
     */
    public function allWithKelas(): Collection;

    /**
     * Upsert a mapping for the given classroom_id.
     */
    public function upsertMapping(int $classroomId, int $kelasId, ?int $userId): SipintuClassroomMapping;
}
