<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use Closure;

interface TransactionalServiceInterface
{
    public function transaction(Closure $callback, int $attempts = 1): mixed;
}
