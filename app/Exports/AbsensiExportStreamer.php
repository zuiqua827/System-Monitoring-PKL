<?php

declare(strict_types=1);

namespace App\Exports;

use Illuminate\Support\Collection;
use IteratorAggregate;
use Countable;
use Traversable;

final class AbsensiExportStreamer implements IteratorAggregate, Countable
{
    /**
     * @param Collection<int, \App\Models\Absensi> $records
     */
    public function __construct(
        private readonly Collection $records,
    ) {}

    public function exists(): bool
    {
        return $this->records->isNotEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->records->isNotEmpty();
    }

    public function isEmpty(): bool
    {
        return $this->records->isEmpty();
    }

    public function count(): int
    {
        return $this->records->count();
    }

    public function getIterator(): Traversable
    {
        return $this->records->getIterator();
    }

    /**
     * Chunk the records collection to match Eloquent Builder chunk signature.
     *
     * @param int $count
     * @param callable(Collection<int, \App\Models\Absensi>): (bool|void) $callback
     * @return bool
     */
    public function chunk(int $count, callable $callback): bool
    {
        foreach ($this->records->chunk($count) as $chunk) {
            if ($callback($chunk) === false) {
                return false;
            }
        }

        return true;
    }
}
