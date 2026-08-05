<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Services\Interfaces\DashboardServiceInterface;

/**
 * Service layer for Dashboard business logic.
 *
 * Delegates all data aggregation to DashboardRepository.
 * No business logic modification - read-only queries only.
 */
class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getSuperAdminStats(): array
    {
        return [
            'total_guru' => $this->dashboardRepository->countGuru(),
            'total_siswa' => $this->dashboardRepository->countSiswa(),
            'total_dudi' => $this->dashboardRepository->countDudi(),
            'total_jurusan' => $this->dashboardRepository->countJurusan(),
            'total_kelas' => $this->dashboardRepository->countKelas(),
            'total_periode_pkl' => $this->dashboardRepository->countPeriodePkl(),
            'total_penempatan' => $this->dashboardRepository->countPenempatanPkl(),
            'total_pkl_aktif' => $this->dashboardRepository->countPklAktif(),
            'total_pkl_selesai' => $this->dashboardRepository->countPklSelesai(),
            'total_absensi_hari_ini' => $this->dashboardRepository->countAbsensiHariIni(),
            'total_aktivitas_hari_ini' => $this->dashboardRepository->countAktivitasHariIni(),
            'total_penilaian' => $this->dashboardRepository->countPenilaian(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSuperAdminCharts(): array
    {
        return [
            'attendance_trend' => $this->dashboardRepository->getKehadiran7Hari(),
            'attendance_status' => $this->dashboardRepository->getStatusAbsensi(),
            'students_per_dudi' => $this->dashboardRepository->getPenempatanPerDudi(),
            'grade_distribution' => $this->dashboardRepository->getPredikatPenilaian(),
            'activity_trend' => $this->dashboardRepository->getAktivitasMingguan(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSuperAdminMonitoring(): array
    {
        return [
            'belum_check_in' => $this->dashboardRepository->getBelumCheckIn(),
            'belum_check_out' => $this->dashboardRepository->getBelumCheckOut(),
            'aktivitas_menunggu_validasi' => $this->dashboardRepository->getAktivitasMenungguValidasi(),
            'penilaian_draft' => $this->dashboardRepository->getPenilaianDraft(),
            'pkl_akan_berakhir' => $this->dashboardRepository->getPklAkanBerakhir(),
            'pkl_terlambat_mulai' => $this->dashboardRepository->getPklTerlambatMulai(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSuperAdminRecentActivity(): array
    {
        return $this->dashboardRepository->getRecentActivity(10);
    }

    /**
     * {@inheritDoc}
     */
    public function getGuruStats(int $guruId): array
    {
        return [
            'total_siswa_bimbingan' => $this->dashboardRepository->countSiswaBimbingan($guruId),
            'absensi_hari_ini' => $this->dashboardRepository->countAbsensiHariIniByGuru($guruId),
            'aktivitas_menunggu_validasi' => $this->dashboardRepository->countAktivitasMenungguValidasiByGuru($guruId),
            'penilaian_draft' => $this->dashboardRepository->countPenilaianDraftByGuru($guruId),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getGuruCharts(int $guruId): array
    {
        return [
            'attendance_7_hari' => $this->dashboardRepository->getKehadiran7HariByGuru($guruId),
            'status_aktivitas' => $this->dashboardRepository->getStatusAktivitasByGuru($guruId),
            'nilai_siswa' => $this->dashboardRepository->getNilaiSiswaByGuru($guruId),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiStats(int $dudiId): array
    {
        return [
            'total_siswa_pkl' => $this->dashboardRepository->countSiswaPklByDudi($dudiId),
            'absensi_hari_ini' => $this->dashboardRepository->countAbsensiHariIniByDudi($dudiId),
            'aktivitas_hari_ini' => $this->dashboardRepository->countAktivitasHariIniByDudi($dudiId),
            'aktivitas_menunggu_validasi' => $this->dashboardRepository->countAktivitasMenungguValidasiByDudi($dudiId),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiRecentActivity(int $dudiId): array
    {
        return $this->dashboardRepository->getRecentActivityByDudi($dudiId, 10);
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiRecentSiswa(int $dudiId): array
    {
        return $this->dashboardRepository->getRecentSiswaByDudi($dudiId, 5);
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiCharts(int $dudiId): array
    {
        return [
            'absensi_7_hari' => $this->dashboardRepository->getAbsensi7HariByDudi($dudiId),
            'aktivitas_7_hari' => $this->dashboardRepository->getAktivitas7HariByDudi($dudiId),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSiswaStats(int $siswaId): array
    {
        return $this->dashboardRepository->getSiswaDashboardData($siswaId);
    }
}
