<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Repositories\Laporan\Interfaces\LaporanRepositoryInterface;
use App\Services\Laporan\Interfaces\LaporanServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LaporanService implements LaporanServiceInterface
{
    private const PDF_ROW_LIMIT = 1000;

    public function __construct(
        private readonly LaporanRepositoryInterface $laporanRepository,
    ) {}

    public function getAdminSummary(array $filters): LengthAwarePaginator|Collection
    {
        return $this->laporanRepository->getPenempatanSummary($filters);
    }

    public function getGuruSummary(int $guruId, array $filters): LengthAwarePaginator|Collection
    {
        $filters['guru_id'] = $guruId;
        return $this->laporanRepository->getPenempatanSummary($filters);
    }

    public function getDudiSummary(int $dudiId, array $filters): LengthAwarePaginator|Collection
    {
        $filters['dudi_id'] = $dudiId;
        return $this->laporanRepository->getPenempatanSummary($filters);
    }

    public function getAdminAbsensiReport(array $filters): array
    {
        return [
            'data' => $this->laporanRepository->getAbsensiReport($filters),
            'stats' => $this->laporanRepository->getAbsensiSummaryStats($filters)
        ];
    }

    public function getGuruAbsensiReport(int $guruId, array $filters): array
    {
        $filters['guru_id'] = $guruId;
        return [
            'data' => $this->laporanRepository->getAbsensiReport($filters),
            'stats' => $this->laporanRepository->getAbsensiSummaryStats($filters)
        ];
    }

    public function getDudiAbsensiReport(int $dudiId, array $filters): array
    {
        $filters['dudi_id'] = $dudiId;
        return [
            'data' => $this->laporanRepository->getAbsensiReport($filters),
            'stats' => $this->laporanRepository->getAbsensiSummaryStats($filters)
        ];
    }

    public function getAdminAbsensiExport(array $filters): array
    {
        return $this->getAbsensiExport($filters);
    }

    public function getGuruAbsensiExport(int $guruId, array $filters): array
    {
        $filters['guru_id'] = $guruId;

        return $this->getAbsensiExport($filters);
    }

    public function getDudiAbsensiExport(int $dudiId, array $filters): array
    {
        $filters['dudi_id'] = $dudiId;

        return $this->getAbsensiExport($filters);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>}
     */
    private function getAbsensiExport(array $filters): array
    {
        $filters = $this->sanitizeAbsensiExportFilters($filters);

        return [
            'query' => $this->laporanRepository->getAbsensiExportQuery($filters),
            'stats' => $this->laporanRepository->getAbsensiSummaryStats($filters),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, int|string>
     */
    private function sanitizeAbsensiExportFilters(array $filters): array
    {
        $allowedFilters = [
            'periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id',
            'tanggal_mulai', 'tanggal_akhir', 'status',
        ];
        $filters = array_intersect_key($filters, array_flip($allowedFilters));
        $hasInvalidFilter = false;

        foreach (['periode_id', 'jurusan_id', 'kelas_id', 'guru_id', 'dudi_id'] as $key) {
            if (!array_key_exists($key, $filters) || $filters[$key] === '' || $filters[$key] === null) {
                unset($filters[$key]);
                continue;
            }

            $value = filter_var($filters[$key], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($value === false) {
                $hasInvalidFilter = true;
                unset($filters[$key]);
                continue;
            }

            $filters[$key] = $value;
        }

        foreach (['tanggal_mulai', 'tanggal_akhir'] as $key) {
            if (!array_key_exists($key, $filters) || $filters[$key] === '' || $filters[$key] === null) {
                unset($filters[$key]);
                continue;
            }

            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $filters[$key]);
            $errors = \DateTimeImmutable::getLastErrors();
            $isValidDate = $date !== false
                && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
                && $date->format('Y-m-d') === $filters[$key];

            if (!$isValidDate) {
                $hasInvalidFilter = true;
                unset($filters[$key]);
            }
        }

        if (
            isset($filters['tanggal_mulai'], $filters['tanggal_akhir'])
            && $filters['tanggal_mulai'] > $filters['tanggal_akhir']
        ) {
            $hasInvalidFilter = true;
        }

        $validStatuses = ['hadir', 'terlambat', 'izin', 'sakit', 'alpha'];
        if (isset($filters['status']) && $filters['status'] !== '') {
            $filters['status'] = strtolower((string) $filters['status']);

            if (!in_array($filters['status'], $validStatuses, true)) {
                $hasInvalidFilter = true;
            }
        } else {
            unset($filters['status']);
        }

        // An invalid request must never broaden an export result.
        if ($hasInvalidFilter) {
            $filters['status'] = '__invalid_filter__';
        }

        return $filters;
    }

    public function getAdminSiswaReport(array $filters): array
    {
        return [
            'data' => $this->laporanRepository->getSiswaReport($filters),
            'stats' => $this->laporanRepository->getSiswaSummaryStats($filters)
        ];
    }

    public function getGuruSiswaReport(int $guruId, array $filters): array
    {
        $filters['guru_id'] = $guruId;
        return [
            'data' => $this->laporanRepository->getSiswaReport($filters),
            'stats' => $this->laporanRepository->getSiswaSummaryStats($filters)
        ];
    }

    public function getDudiSiswaReport(int $dudiId, array $filters): array
    {
        $filters['dudi_id'] = $dudiId;
        return [
            'data' => $this->laporanRepository->getSiswaReport($filters),
            'stats' => $this->laporanRepository->getSiswaSummaryStats($filters)
        ];
    }

    public function getAdminSiswaExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        return $this->laporanRepository->getSiswaExportQuery($filters);
    }

    public function getGuruSiswaExportQuery(int $guruId, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $filters['guru_id'] = $guruId;
        return $this->laporanRepository->getSiswaExportQuery($filters);
    }

    public function getDudiSiswaExportQuery(int $dudiId, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $filters['dudi_id'] = $dudiId;
        return $this->laporanRepository->getSiswaExportQuery($filters);
    }

    public function getAdminSiswaPdfReport(array $filters): array
    {
        return $this->getSiswaPdfReport($filters);
    }

    public function getGuruSiswaPdfReport(int $guruId, array $filters): array
    {
        // Deliberately overwrite any query-string value to prevent data leaks.
        $filters['guru_id'] = $guruId;

        return $this->getSiswaPdfReport($filters);
    }

    public function getDudiSiswaPdfReport(int $dudiId, array $filters): array
    {
        // Deliberately overwrite any query-string value to prevent data leaks.
        $filters['dudi_id'] = $dudiId;

        return $this->getSiswaPdfReport($filters);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: Collection<int, \App\Models\PenempatanPKL>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int}
     */
    private function getSiswaPdfReport(array $filters): array
    {
        $records = $this->laporanRepository->getSiswaReportForPdf(
            $filters,
            self::PDF_ROW_LIMIT + 1,
        );

        return [
            'data' => $records->take(self::PDF_ROW_LIMIT)->values(),
            'applied_filters' => $this->laporanRepository->getSiswaPdfFilterSummary($filters),
            'is_over_limit' => $records->count() > self::PDF_ROW_LIMIT,
            'limit' => self::PDF_ROW_LIMIT,
        ];
    }

    public function getAdminAktivitasReport(array $filters): array
    {
        return [
            'data' => $this->laporanRepository->getAktivitasReport($filters),
            'stats' => $this->laporanRepository->getAktivitasSummaryStats($filters)
        ];
    }

    public function getGuruAktivitasReport(int $guruId, array $filters): array
    {
        $filters['guru_id'] = $guruId;
        return [
            'data' => $this->laporanRepository->getAktivitasReport($filters),
            'stats' => $this->laporanRepository->getAktivitasSummaryStats($filters)
        ];
    }

    public function getDudiAktivitasReport(int $dudiId, array $filters): array
    {
        $filters['dudi_id'] = $dudiId;
        return [
            'data' => $this->laporanRepository->getAktivitasReport($filters),
            'stats' => $this->laporanRepository->getAktivitasSummaryStats($filters)
        ];
    }

    public function getAdminPenilaianReport(array $filters): array
    {
        return [
            'data' => $this->laporanRepository->getPenilaianReport($filters),
            'stats' => $this->laporanRepository->getPenilaianSummaryStats($filters)
        ];
    }

    public function getGuruPenilaianReport(int $guruId, array $filters): array
    {
        $filters['guru_id'] = $guruId;
        return [
            'data' => $this->laporanRepository->getPenilaianReport($filters),
            'stats' => $this->laporanRepository->getPenilaianSummaryStats($filters)
        ];
    }

    public function getDudiPenilaianReport(int $dudiId, array $filters): array
    {
        $filters['dudi_id'] = $dudiId;
        return [
            'data' => $this->laporanRepository->getPenilaianReport($filters),
            'stats' => $this->laporanRepository->getPenilaianSummaryStats($filters)
        ];
    }
}
