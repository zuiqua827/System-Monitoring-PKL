<?php

declare(strict_types=1);

namespace App\Services\Laporan\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LaporanServiceInterface
{
    public function getAdminSummary(array $filters): LengthAwarePaginator|Collection;
    
    public function getGuruSummary(int $guruId, array $filters): LengthAwarePaginator|Collection;
    
    public function getDudiSummary(int $dudiId, array $filters): LengthAwarePaginator|Collection;
    
    public function getAdminSiswaReport(array $filters): array;
    
    public function getGuruSiswaReport(int $guruId, array $filters): array;
    
    public function getDudiSiswaReport(int $dudiId, array $filters): array;
    
    public function getAdminSiswaExportQuery(array $filters): \Illuminate\Database\Eloquent\Builder;
    
    public function getGuruSiswaExportQuery(int $guruId, array $filters): \Illuminate\Database\Eloquent\Builder;
    
    public function getDudiSiswaExportQuery(int $dudiId, array $filters): \Illuminate\Database\Eloquent\Builder;

    /** @return array{data: Collection<int, \App\Models\PenempatanPKL>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getAdminSiswaPdfReport(array $filters): array;

    /** @return array{data: Collection<int, \App\Models\PenempatanPKL>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getGuruSiswaPdfReport(int $guruId, array $filters): array;

    /** @return array{data: Collection<int, \App\Models\PenempatanPKL>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getDudiSiswaPdfReport(int $dudiId, array $filters): array;

    public function getAdminAbsensiReport(array $filters): array;
    
    public function getGuruAbsensiReport(int $guruId, array $filters): array;
    
    public function getDudiAbsensiReport(int $dudiId, array $filters): array;

    /** @return array{data: Collection<int, \App\Models\Absensi>, stats: array<string, int>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getAdminAbsensiPdfReport(array $filters): array;

    /** @return array{data: Collection<int, \App\Models\Absensi>, stats: array<string, int>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getGuruAbsensiPdfReport(int $guruId, array $filters): array;

    /** @return array{data: Collection<int, \App\Models\Absensi>, stats: array<string, int>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getDudiAbsensiPdfReport(int $dudiId, array $filters): array;

    /** @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>} */
    public function getAdminAbsensiExport(array $filters): array;

    /** @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>} */
    public function getGuruAbsensiExport(int $guruId, array $filters): array;

    /** @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>} */
    public function getDudiAbsensiExport(int $dudiId, array $filters): array;
    
    public function getAdminAktivitasReport(array $filters): array;
    
    public function getGuruAktivitasReport(int $guruId, array $filters): array;
    
    public function getDudiAktivitasReport(int $dudiId, array $filters): array;

    /** @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>} */
    public function getAdminAktivitasExport(array $filters): array;

    /** @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>} */
    public function getGuruAktivitasExport(int $guruId, array $filters): array;

    /** @return array{query: \Illuminate\Database\Eloquent\Builder, stats: array<string, int>} */
    public function getDudiAktivitasExport(int $dudiId, array $filters): array;

    /** @return array{data: Collection<int, \App\Models\Aktivitas>, stats: array<string, int>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getAdminAktivitasPdfReport(array $filters): array;

    /** @return array{data: Collection<int, \App\Models\Aktivitas>, stats: array<string, int>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getGuruAktivitasPdfReport(int $guruId, array $filters): array;

    /** @return array{data: Collection<int, \App\Models\Aktivitas>, stats: array<string, int>, applied_filters: list<array{label: string, value: string}>, is_over_limit: bool, limit: int} */
    public function getDudiAktivitasPdfReport(int $dudiId, array $filters): array;
    
    public function getAdminPenilaianReport(array $filters): array;
    
    public function getGuruPenilaianReport(int $guruId, array $filters): array;
    
    public function getDudiPenilaianReport(int $dudiId, array $filters): array;
}
