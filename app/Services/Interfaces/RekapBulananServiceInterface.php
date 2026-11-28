<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Siswa;
use Illuminate\Database\Eloquent\Collection;

interface RekapBulananServiceInterface
{
    /**
     * Get monthly recap combining attendance and activity data for a student.
     *
     * @param Siswa $siswa
     * @param int $month (1-12)
     * @param int $year
     * @return array<string, mixed>
     */
    public function getMonthlyRecap(Siswa $siswa, int $month, int $year): array;

    /**
     * Get list of eligible students for a specific teacher.
     *
     * @param int $guruId
     * @return Collection<int, Siswa>
     */
    public function getEligibleSiswaForGuru(int $guruId): Collection;

    /**
     * Get list of eligible students for a specific DUDI company.
     *
     * @param int $dudiId
     * @return Collection<int, Siswa>
     */
    public function getEligibleSiswaForDudi(int $dudiId): Collection;
}
