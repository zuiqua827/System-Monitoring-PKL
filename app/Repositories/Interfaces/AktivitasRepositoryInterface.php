<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Aktivitas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepositoryInterface<Aktivitas>
 */
interface AktivitasRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and filter aktivitas with eager loading.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator<int, Aktivitas>
     */
    public function search(
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        ?int $guruId = null,
        ?int $siswaId = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get paginated aktivitas for a specific siswa.
     *
     * @return LengthAwarePaginator<int, Aktivitas>
     */
    public function getBySiswaPaginated(
        int $siswaId,
        ?string $tanggal = null,
        ?string $status = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get paginated aktivitas for a specific guru's bimbingan.
     *
     * @return LengthAwarePaginator<int, Aktivitas>
     */
    public function getByGuruPaginated(
        int $guruId,
        ?string $keyword = null,
        ?string $tanggal = null,
        ?string $status = null,
        ?int $periodeId = null,
        string $sortBy = 'tanggal',
        string $sortDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Get all trashed records.
     *
     * @return Collection<int, Aktivitas>
     */
    public function allWithTrashed(): Collection;

    /**
     * Restore a soft-deleted aktivitas.
     */
    public function restore(Aktivitas $aktivitas): bool;

    /**
     * Permanently delete an aktivitas.
     */
    public function forceDelete(Aktivitas $aktivitas): bool;
}

