<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Collection;

interface RekapBulananRepositoryInterface
{
    /**
     * Get attendance records for a student within a specific date range.
     *
     * @param int $siswaId
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return Collection<int, Absensi>
     */
    public function getAbsensiBySiswaAndDateRange(int $siswaId, string $startDate, string $endDate): Collection;

    /**
     * Get activity records for a student within a specific date range.
     *
     * @param int $siswaId
     * @param string $startDate (Y-m-d)
     * @param string $endDate (Y-m-d)
     * @return Collection<int, Aktivitas>
     */
    public function getAktivitasBySiswaAndDateRange(int $siswaId, string $startDate, string $endDate): Collection;

    /**
     * Get all students assigned to a specific teacher.
     *
     * @param int $guruId
     * @return Collection<int, Siswa>
     */
    public function getEligibleSiswaForGuru(int $guruId): Collection;

    /**
     * Get all students placed in a specific DUDI company.
     *
     * @param int $dudiId
     * @return Collection<int, Siswa>
     */
    public function getEligibleSiswaForDudi(int $dudiId): Collection;
}
