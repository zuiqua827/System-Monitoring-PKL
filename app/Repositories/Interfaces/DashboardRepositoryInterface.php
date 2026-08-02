<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

/**
 * Interface for Dashboard data aggregation queries.
 *
 * All dashboard statistics are read-only queries.
 * No create/update/delete operations.
 */
interface DashboardRepositoryInterface
{
    public function countGuru(): int;
    public function countSiswa(): int;
    public function countDudi(): int;
    public function countJurusan(): int;
    public function countKelas(): int;
    public function countPeriodePkl(): int;
    public function countPenempatanPkl(): int;
    public function countAbsensiHariIni(): int;
    public function countAktivitasHariIni(): int;
    public function countPenilaian(): int;
    public function countPklAktif(): int;
    public function countPklSelesai(): int;
    public function getKehadiran7Hari(): array;
    public function getStatusAbsensi(): array;
    public function getPenempatanPerDudi(): array;
    public function getPredikatPenilaian(): array;
    public function getAktivitasMingguan(): array;
    public function getBelumCheckIn(): int;
    public function getBelumCheckOut(): int;
    public function getAktivitasMenungguValidasi(): int;
    public function getPenilaianDraft(): int;
    public function getPklAkanBerakhir(): int;
    public function getPklTerlambatMulai(): int;
    public function getRecentActivity(int $limit = 10): array;
    public function countSiswaBimbingan(int $guruId): int;
    public function countAbsensiHariIniByGuru(int $guruId): int;
    public function countAktivitasMenungguValidasiByGuru(int $guruId): int;
    public function countPenilaianDraftByGuru(int $guruId): int;
    public function getKehadiran7HariByGuru(int $guruId): array;
    public function getStatusAktivitasByGuru(int $guruId): array;
    public function getNilaiSiswaByGuru(int $guruId): array;
    public function countSiswaPklByDudi(int $dudiId): int;
    public function countAbsensiHariIniByDudi(int $dudiId): int;
    public function countAktivitasHariIniByDudi(int $dudiId): int;
    public function getAbsensi7HariByDudi(int $dudiId): array;
    public function getAktivitas7HariByDudi(int $dudiId): array;
    public function getSiswaDashboardData(int $siswaId): array;
}
