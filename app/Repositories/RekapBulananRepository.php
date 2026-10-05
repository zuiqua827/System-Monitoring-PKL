<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\PenempatanPKL;
use App\Models\Siswa;
use App\Repositories\Interfaces\RekapBulananRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RekapBulananRepository implements RekapBulananRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAbsensiBySiswaAndDateRange(int $siswaId, string $startDate, string $endDate): Collection
    {
        return Absensi::query()
            ->with(['penempatanPKL'])
            ->whereHas('penempatanPKL', function ($query) use ($siswaId): void {
                $query->where('siswa_id', $siswaId);
            })
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAktivitasBySiswaAndDateRange(int $siswaId, string $startDate, string $endDate): Collection
    {
        return Aktivitas::query()
            ->with([
                'approvedBy:id,name',
                'validatedBy:id,name',
                'penempatanPKL.guru:id,nama',
                'penempatanPKL.dudi:id,nama_perusahaan',
                'penempatanPKL.siswa:id,nama',
            ])
            ->whereHas('penempatanPKL', function ($query) use ($siswaId): void {
                $query->where('siswa_id', $siswaId);
            })
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getEligibleSiswaForGuru(int $guruId): Collection
    {
        $penempatans = PenempatanPKL::query()
            ->with(['siswa.kelas.jurusan'])
            ->where('guru_id', $guruId)
            ->whereNotNull('siswa_id')
            ->get();

        /** @var Collection<int, Siswa> $siswaList */
        $siswaList = $penempatans
            ->pluck('siswa')
            ->filter()
            ->unique('id')
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return new Collection($siswaList->all());
    }

    /**
     * {@inheritDoc}
     */
    public function getEligibleSiswaForDudi(int $dudiId): Collection
    {
        $penempatans = PenempatanPKL::query()
            ->with(['siswa.kelas.jurusan'])
            ->where('dudi_id', $dudiId)
            ->whereNotNull('siswa_id')
            ->get();

        /** @var Collection<int, Siswa> $siswaList */
        $siswaList = $penempatans
            ->pluck('siswa')
            ->filter()
            ->unique('id')
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return new Collection($siswaList->all());
    }
}
