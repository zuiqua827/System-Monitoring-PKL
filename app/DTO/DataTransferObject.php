<?php

declare(strict_types=1);

namespace App\DTO;

abstract readonly class DataTransferObject
{
    /**
     * Convert the DTO into a plain array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
