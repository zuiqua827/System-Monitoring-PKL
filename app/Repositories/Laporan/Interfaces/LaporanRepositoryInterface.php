<?php

declare(strict_types=1);

namespace App\Repositories\Laporan\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LaporanRepositoryInterface
{
    /**
     * Get base summary of PenempatanPKL for reporting purposes.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator|Collection
     */
    public function getPenempatanSummary(array $filters): LengthAwarePaginator|Collection;
    
    public function getSiswaReport(array $filters): LengthAwarePaginator|Collection;
    
    public function getSiswaSummaryStats(array $filters): array;
    
    public function getSiswaExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder;

    /**
     * Get the eager-loaded, filtered siswa report records for PDF rendering.
     *
     * The caller supplies one additional record beyond its display limit so it
     * can safely detect when the result is too large for DomPDF.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, \App\Models\PenempatanPKL>
     */
    public function getSiswaReportForPdf(array $filters, int $limit): Collection;

    /**
     * Get display-ready descriptions of active siswa report filters.
     *
     * @param array<string, mixed> $filters
     * @return list<array{label: string, value: string}>
     */
    public function getSiswaPdfFilterSummary(array $filters): array;
    
    public function getAbsensiReport(array $filters): LengthAwarePaginator|Collection;
    
    public function getAbsensiSummaryStats(array $filters): array;

    /**
     * Get the filtered absensi query for chunked Excel export.
     *
     * @param array<string, mixed> $filters
     */
    public function getAbsensiExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder;

    /**
     * Get the eager-loaded, filtered absensi report records for PDF rendering.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, \App\Models\Absensi>
     */
    public function getAbsensiReportForPdf(array $filters, int $limit): Collection;

    /**
     * Get display-ready descriptions of active absensi report filters.
     *
     * @param array<string, mixed> $filters
     * @return list<array{label: string, value: string}>
     */
    public function getAbsensiPdfFilterSummary(array $filters): array;
    
    public function getAktivitasReport(array $filters): LengthAwarePaginator|Collection;
    
    public function getAktivitasSummaryStats(array $filters): array;

    /**
     * Get the filtered aktivitas query for chunked Excel export.
     *
     * @param array<string, mixed> $filters
     */
    public function getAktivitasExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder;

    /**
     * Get the eager-loaded, filtered aktivitas report records for PDF rendering.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, \App\Models\Aktivitas>
     */
    public function getAktivitasReportForPdf(array $filters, int $limit): Collection;

    /**
     * Get display-ready descriptions of active aktivitas report filters.
     *
     * @param array<string, mixed> $filters
     * @return list<array{label: string, value: string}>
     */
    public function getAktivitasPdfFilterSummary(array $filters): array;
    
    public function getPenilaianReport(array $filters): LengthAwarePaginator|Collection;
    
    public function getPenilaianSummaryStats(array $filters): array;
}
