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
     * Fetch DUDI companies from the SiPintu Gateway.
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchDudi(?string $search = null): array;

    /**
     * Fetch and validate the remote dataset for the given entity scope
     * (`all`, `siswa`, `guru`, `dudi`) before a live database transaction opens.
     *
     * Never silently turns HTTP 404 into an empty list; unavailable endpoints are
     * flagged explicitly via `endpoint_unavailable`.
     *
     * @return array{
     *     students: array<int, array<string, mixed>>|null,
     *     teachers: array<int, array<string, mixed>>|null,
     *     dudis: array<int, array<string, mixed>>|null,
     *     endpoint_unavailable: array{students: bool, teachers: bool, dudis: bool},
     *     endpoint_errors: array<string, array{http_status: int|null, message: string}>
     * }
     */
    public function fetchSyncPayload(string $entityScope = 'all'): array;

    /**
     * Read-only preview of a student sync. Classifies remote students into
     * categories WITHOUT writing/inserting/updating/deleting anything.
     *
     * Categories: baru, diperbarui, tidak_berubah, perlu_pemetaan, duplikat,
     * konflik, tidak_valid, gagal, tidak_ditemukan, error, total_remote.
     *
     * @return array<string, int|string>
     */
    public function previewStudents(): array;

    /**
     * Read-only preview of a teacher sync. Classifies remote teachers into
     * categories WITHOUT writing/inserting/updating/deleting anything.
     *
     * @return array<string, int|string>
     */
    public function previewTeachers(): array;

    /**
     * Read-only preview of a DUDI sync. Classifies remote DUDI into
     * categories WITHOUT writing/inserting/updating/deleting anything.
     *
     * @return array<string, int|string>
     */
    public function previewDudi(bool $allowCreate = false): array;

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
     * @param  array<int, array<string, mixed>>|null  $students
     * @return array<string, int>
     */
    public function syncStudents(?array $students = null, bool $endpointUnavailable = false): array;

    /**
     * Synchronize real teachers from SiPintu into the local Guru module.
     *
     * - Upserts by NIP (unique identifier).
     * - Creates a new Guru + User (role "Guru") when the NIP does not exist.
     * - Updates an existing Guru + User when the NIP already exists.
     * - Never creates duplicates.
     * - Never overwrites passwords the user has already changed.
     *
     * @param  array<int, array<string, mixed>>|null  $teachers
     * @return array<string, int>
     */
    public function syncTeachers(?array $teachers = null, bool $endpointUnavailable = false): array;

    /**
     * Synchronize master DUDI companies from SiPintu into the local DUDI module.
     *
     * - Default policy is link-only/safe (`$allowCreate = false`): only links
     *   (`sipintu_id`/`sipintu_user_id`/`email_perusahaan`) and updates identity
     *   fields for DUDI companies already created locally by Admin.
     * - New DUDI creation occurs ONLY when `$allowCreate = true` is explicitly confirmed.
     * - Never overwrites local passwords, Super Admin roles, geofence coordinates,
     *   working hours, or existing PKL placements.
     *
     * @param  array<int, array<string, mixed>>|null  $dudis
     * @return array<string, int>
     */
    public function syncDudi(?array $dudis = null, bool $allowCreate = false, bool $endpointUnavailable = false): array;

    /**
     * Get aggregate live endpoint diagnostics for SiPintu.
     *
     * @return array<string, mixed>
     */
    public function getDiagnostics(): array;

    /**
     * Test connection to the SiPintu API.
     *
     * @return array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null, detail: string|null, troubleshooting: string|null}
     */
    public function testConnection(): array;

    /**
     * Check status of a specific endpoint on the SiPintu Gateway.
     *
     * @return array{status: int|null, http_status: int|null, success: bool, error_type: string|null, message: string, count: int|null}
     */
    public function checkEndpointStatus(string $endpoint): array;

    /**
     * Check status of multiple SiPintu endpoints concurrently via HTTP pool.
     *
     * @param  list<string>  $endpoints
     * @return array<string, array{status: int|null, http_status: int|null, success: bool, error_type: string|null, message: string, count: int|null}>
     */
    public function checkEndpointsStatus(array $endpoints): array;

    /**
     * Direct credential verification against SiPintu Gateway.
     *
     * @return array{success: bool, status: int|null, data?: mixed, message?: string}
     */
    public function verifyCredentials(string $identity, string $password): array;
}
