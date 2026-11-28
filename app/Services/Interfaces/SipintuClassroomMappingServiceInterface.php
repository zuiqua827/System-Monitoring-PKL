<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\SipintuClassroomMapping;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for the SiPintu classroom mapping feature.
 *
 * Retrieves the unique SiPintu classroom_ids (with student counts), manages
 * the persisted classroom_id → kelas mapping, and applies the mapping to
 * update local students' kelas.
 */
interface SipintuClassroomMappingServiceInterface
{
    /**
     * Get the data needed to render the mapping page.
     *
     * Returns the unique classroom_ids from SiPintu (with student counts),
     * the existing mappings, and the list of local kelas for the dropdowns.
     *
     * @return array{
     *     classrooms: array<int, array{classroom_id: int, student_count: int}>,
     *     mappings: \Illuminate\Database\Eloquent\Collection<int, \App\Models\SipintuClassroomMapping>,
     *     kelasOptions: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Kelas>,
     *     connected: bool
     * }
     */
    public function getDashboardData(): array;

    /**
     * Save a classroom_id → kelas mapping.
     *
     * @throws \InvalidArgumentException when the kelas does not exist.
     */
    public function saveMapping(int $classroomId, int $kelasId, ?int $userId): SipintuClassroomMapping;

    /**
     * Apply all saved mappings to local students.
     *
     * Only updates the siswa.class_id (kelas relation). Never touches NIS,
     * name, user, password, email, role, PKL, attendance, activities, grades.
     *
     * Wrapped in a single database transaction.
     *
     * @return array{updated: int, skipped: int, failed: int}
     */
    public function applyMappings(): array;

    /**
     * Resolve the local kelas_id for a given SiPintu classroom_id.
     *
     * Returns null when no mapping exists for the classroom_id.
     */
    public function resolveKelasId(int $classroomId): ?int;

    /**
     * Get all mappings (with kelas relation).
     *
     * @return Collection<int, SipintuClassroomMapping>
     */
    public function allMappings(): Collection;
}
