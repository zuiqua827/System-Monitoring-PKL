<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Interfaces\TransactionalServiceInterface;
use App\Traits\ExecutesDatabaseTransactions;

abstract class Service implements TransactionalServiceInterface
{
    use ExecutesDatabaseTransactions;
}
