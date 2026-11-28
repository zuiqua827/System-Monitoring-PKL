<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SiPintuSyncLog;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @extends EloquentRepository<SiPintuSyncLog>
 */
class SipintuSyncLogRepository extends EloquentRepository implements SipintuSyncLogRepositoryInterface
{
    public function __construct(SiPintuSyncLog $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function paginateHistory(int $perPage = 15): LengthAwarePaginator
    {
        return $this->newQuery()
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function latestLog(): ?SiPintuSyncLog
    {
        /** @var SiPintuSyncLog|null $log */
        $log = $this->newQuery()->latest()->first();

        return $log;
    }
}
