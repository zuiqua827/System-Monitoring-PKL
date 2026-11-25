<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\SipintuClassroomMapping;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for the SiPintu classroom mapping feature.
 *
 * Retrieves the unique SiPintu classroom_ids (with student counts and class
 * names), enforces conflict-free 1-to-1 persistence of classroom_id → kelas
 * mapping, validates mapping integrity, and resolves strict unambiguous matches.
 */
interface SipintuClassroomMappingServiceInterface
{
    /**
     * Get the data needed to render the mapping page.
     *
     * @return array{
     *     classrooms: array<int, array{
     *         classroom_id: int,
     *         classroom_name: string|null,
     *         tingkat: int|null,
     *         jurusan: string|null,
     *         student_count: int,
     *         suggested_kelas_id: int|null,
     *         suggested_kelas_nama: string|null,
     *         auto_match_reason: string
     *     }>,
     *     mappings: Collection<int, SipintuClassroomMapping>,
     *     mappingHealth: array<int, array{is_valid: bool, reason: string|null, in_remote: bool}>,
     *     kelasOptions: Collection<int, \App\Models\Kelas>,
     *     summary: array{total_remote: int, mapped: int, unmapped: int, invalid: int, auto_matchable: int, total_students: int},
     *     connected: bool
     * }
     */
    public function getDashboardData(): array;

    /**
     * Save a classroom_id → kelas mapping (rejects deleted kelas and 1-to-many conflicts).
     *
     * @throws \InvalidArgumentException when classroomId/kelas is invalid or conflicts with another mapping.
     */
    public function saveMapping(int $classroomId, int $kelasId, ?int $userId): SipintuClassroomMapping;

    /**
     * Delete a classroom mapping by its ID.
     */
    public function deleteMapping(int $mappingId): bool;

    /**
     * Auto-map unmapped remote classrooms that have a strict, unambiguous exact match
     * (name, tingkat, jurusan, and no 1-to-1 conflict).
     *
     * @return array{mapped: int, skipped_unmatched: int, skipped_conflict: int, already_mapped: int}
     */
    public function autoMapExactMatches(?int $userId): array;

    /**
     * Apply all saved valid mappings to existing local students.
     *
     * Only updates siswa.class_id. Wrapped in a single database transaction.
     *
     * @return array{updated: int, skipped: int, failed: int}
     */
    public function applyMappings(): array;

    /**
     * Resolve the active local kelas_id for a given SiPintu classroom_id.
     *
     * Returns null when no active mapping exists for the classroom_id.
     */
    public function resolveKelasId(int $classroomId): ?int;

    /**
     * Get all mappings (with kelas and jurusan relations).
     *
     * @return Collection<int, SipintuClassroomMapping>
     */
    public function allMappings(): Collection;
}
