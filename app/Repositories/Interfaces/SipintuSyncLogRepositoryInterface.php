<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\SiPintuSyncLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepositoryInterface<SiPintuSyncLog>
 */
interface SipintuSyncLogRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Paginate synchronization history, newest first.
     *
     * @return LengthAwarePaginator<int, SiPintuSyncLog>
     */
    public function paginateHistory(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get the most recent sync log.
     */
    public function latestLog(): ?SiPintuSyncLog;
}
