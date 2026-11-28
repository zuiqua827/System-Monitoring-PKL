<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

/**
 * Service for business logic around the SiPintu Gateway integration.
 *
 * Handles fetching and synchronizing real student AND teacher data from
 * SiPintu into the local database. Student data belongs ONLY in the Siswa
 * module; teacher data belongs ONLY in the Guru module. They are never mixed.
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
     * Fetch teachers from the SiPintu Gateway.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchTeachers(?string $nip = null, ?string $search = null): array;

    /**
     * Fetch DUDI partners from the SiPintu Gateway.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchDudis(?string $search = null): array;

    /**
     * Fetch and validate the complete remote dataset before a live database
     * transaction is opened.
     *
     * @return array{students: array<int, array<string, mixed>>, teachers: array<int, array<string, mixed>>, dudis: array<int, array<string, mixed>>}
     */
    public function fetchSyncPayload(): array;

    /**
     * Read-only preview of a student sync. Classifies remote students into
     * categories WITHOUT writing/inserting/updating/deleting anything.
     *
     * Categories: baru, diperbarui, tidak_berubah, konflik, perlu_pemetaan,
     * tidak_ditemukan, error, total_remote.
     *
     * @return array<string, int>
     */
    public function previewStudents(): array;

    /**
     * Read-only preview of a teacher sync. Classifies remote teachers into
     * categories WITHOUT writing/inserting/updating/deleting anything.
     *
     * @return array<string, int>
     */
    public function previewTeachers(): array;

    /**
     * Read-only preview of a DUDI sync. Classifies remote DUDI accounts into
     * categories WITHOUT writing/inserting/updating/deleting anything.
     *
     * @return array<string, int>
     */
    public function previewDudis(): array;

    /**
     * Synchronize real students from SiPintu into the local database.
     *
     * - Creates a new Siswa + User when the NIS/NISN does not exist locally
     *   AND a valid kelas mapping exists.
     * - Updates an existing Siswa + User when an NIS/NISN match is found.
     * - NEVER deletes/soft-deletes local students absent from SiPintu.
     * - Students without a valid kelas mapping are reported as "Perlu Pemetaan"
     *   and are NOT created with a guessed class_id.
     *
     * @return array{
     *     created: int, updated: int, deleted: int, skipped: int,
     *     unchanged: int, conflicts: int, needs_mapping: int, errors: int
     * }
     */
    public function syncStudents(?array $students = null): array;

    /**
     * Synchronize real teachers from SiPintu into the local Guru module.
     *
     * - Upserts by NIP (unique identifier).
     * - Creates a new Guru + User (role "Guru") when the NIP does not exist.
     * - Updates an existing Guru + User when the NIP already exists.
     * - Never creates duplicates.
     * - Never overwrites passwords the user has already changed.
     *
     * @return array{
     *     created: int, updated: int, deleted: int, skipped: int,
     *     unchanged: int, conflicts: int, needs_mapping: int, errors: int
     * }
     */
    public function syncTeachers(?array $teachers = null): array;

    /**
     * Synchronize real DUDI partners from SiPintu into the local Dudi module.
     *
     * - Upserts by email or company name (nama_perusahaan).
     * - Creates a new Dudi + User (role "DUDI") when no match exists locally.
     * - Updates an existing Dudi + User when a match is found.
     * - Never creates duplicates and never overwrites existing passwords.
     *
     * @return array{
     *     created: int, updated: int, deleted: int, skipped: int,
     *     unchanged: int, conflicts: int, needs_mapping: int, errors: int
     * }
     */
    public function syncDudis(?array $dudis = null): array;

    /**
     * Test connection to the SiPintu API.
     *
     * @return array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null}
     */
    public function testConnection(): array;
}
