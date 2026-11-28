<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Exceptions\SiPintuApiException;

/**
 * Repository for communicating with the SiPintu Identity & API Gateway.
 *
 * This repository encapsulates all HTTP calls to the SiPintu Gateway
 * (Server-to-Server method). It reads the client credentials from config
 * (backed by .env) and never hardcodes them.
 */
interface SiPintuRepositoryInterface
{
    public const ENDPOINT_HEALTH = '/api/v1/health';

    public const ENDPOINT_USER = '/api/v1/user';

    public const ENDPOINT_STUDENTS = '/api/v1/sijuna/students';

    public const ENDPOINT_TEACHERS = '/api/v1/sijuna/teachers';

    public const ENDPOINT_DUDI = '/api/v1/sijuna/dudi';

    /**
     * Fetch the list of students from the SiPintu SIJUNA endpoint.
     *
     * @return array<int, mixed> The decoded JSON "data" array of students.
     *
     * @throws SiPintuApiException On connection error, invalid credentials, timeout,
     *                             or any non-2xx API response.
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array;

    /**
     * Fetch the list of teachers from the SiPintu SIJUNA endpoint.
     *
     * @return array<int, mixed> The decoded JSON "data" array of teachers.
     *
     * @throws SiPintuApiException On connection error, invalid credentials, timeout,
     *                             or any non-2xx API response.
     */
    public function fetchTeachers(?string $nip = null, ?string $search = null): array;

    /**
     * Fetch the list of DUDI companies from the SiPintu SIJUNA endpoint.
     *
     * @return array<int, mixed> The decoded JSON "data" array of DUDI companies.
     *
     * @throws SiPintuApiException On connection error, invalid credentials, timeout,
     *                             or any non-2xx API response.
     */
    public function fetchDudi(?string $search = null): array;

    /**
     * Test connection to the SiPintu API.
     *
     * @return array{success: bool, status: bool, connection: bool, http_status: int|null, message: string, error_type: string|null, detail: string|null, troubleshooting: string|null}
     */
    public function testConnection(): array;

    /**
     * Check HTTP status of an endpoint on SiPintu Gateway (lightweight probe).
     *
     * @return array{status: int|null, http_status: int|null, success: bool, error_type: string|null, message: string, count: int|null}
     */
    public function checkEndpointStatus(string $endpoint): array;

    /**
     * Check HTTP status of multiple SiPintu endpoints concurrently via HTTP pool.
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
