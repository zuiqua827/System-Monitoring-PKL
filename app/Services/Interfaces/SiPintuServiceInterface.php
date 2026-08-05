<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

/**
 * Service for business logic around the SiPintu Gateway integration.
 *
 * Handles fetching student data and orchestrating the sync of real students
 * from SiPintu into the local students table.
 */
interface SiPintuServiceInterface
{
    /**
     * Fetch students from the SiPintu Gateway.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array;

    /**
     * Synchronize real students from SiPintu into the local database.
     *
     * - Creates a new Siswa + User when the NIS does not exist locally.
     * - Updates an existing Siswa + User when the NIS already exists.
     * - Soft-deletes local students (Siswa) that are NOT present in SiPintu,
     *   but ONLY if they appear to be dummy/placeholder students.
     *
     * @return array{created: int, updated: int, deleted: int, skipped: int}
     */
    public function syncStudents(): array;
}
