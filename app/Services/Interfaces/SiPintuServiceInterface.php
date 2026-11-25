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
     * Fetch and validate the complete remote dataset before a live database
     * transaction is opened.
     *
     * @return array{students: array<int, array<string, mixed>>, teachers: array<int, array<string, mixed>>, dudis?: array<int, array<string, mixed>>}
     */
    public function fetchSyncPayload(): array;

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
    public function previewDudi(): array;

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
     * @param  array<int, array<string, mixed>>|null  $teachers
     * @return array<string, int>
     */
    public function syncTeachers(?array $teachers = null): array;

    /**
     * Synchronize master DUDI companies from SiPintu into the local DUDI module.
     *
     * - Upserts by sipintu_id (unique stable company ID).
     * - Inserts new DUDI companies safely without automatically creating User accounts.
     * - Updates existing DUDI companies when sipintu_id or company identity matches.
     * - Never deletes local DUDI records absent from SiPintu.
     * - Never alters existing PKL placements or monitoring relations.
     *
     * @param  array<int, array<string, mixed>>|null  $dudis
     * @return array<string, int>
     */
    public function syncDudi(?array $dudis = null): array;

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
     * @return array{status: int|null, success: bool, message: string, count: int|null}
     */
    public function checkEndpointStatus(string $endpoint): array;

    /**
     * Direct credential verification against SiPintu Gateway.
     *
     * @return array{success: bool, status: int|null, data?: mixed, message?: string}
     */
    public function verifyCredentials(string $identity, string $password): array;
}
