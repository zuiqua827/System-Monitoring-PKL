<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

/**
 * Interface for Dashboard Service.
 *
 * All dashboard statistics are read-only queries.
 * Delegates data aggregation to DashboardRepository.
 */
interface DashboardServiceInterface
{
    /** Super Admin Dashboard */
    public function getSuperAdminStats(): array;
    public function getSuperAdminCharts(): array;
    public function getSuperAdminMonitoring(): array;
    public function getSuperAdminRecentActivity(): array;

    /** Guru Dashboard */
    public function getGuruStats(int $guruId): array;
    public function getGuruCharts(int $guruId): array;

    /** DUDI Dashboard */
    public function getDudiStats(int $dudiId): array;
    public function getDudiCharts(int $dudiId): array;
    public function getDudiRecentActivity(int $dudiId): array;
    public function getDudiRecentSiswa(int $dudiId): array;

    /** Siswa Dashboard */
    public function getSiswaStats(int $siswaId): array;
}
