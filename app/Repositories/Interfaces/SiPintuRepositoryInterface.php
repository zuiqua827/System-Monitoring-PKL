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
    /**
     * Fetch the list of students from the SiPintu SIJUNA endpoint.
     *
     * @return array<int, array<string, mixed>> The decoded JSON "data" array of students.
     *
     * @throws SiPintuApiException On connection error, invalid credentials, timeout,
     *                             or any non-2xx API response.
     */
    public function fetchStudents(?string $nis = null, ?string $search = null): array;
}
