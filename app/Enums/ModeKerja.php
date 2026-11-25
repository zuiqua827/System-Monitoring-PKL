<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Attendance location work mode for PKL placements.
 */
enum ModeKerja: string
{
    case WFO = 'WFO';
    case WFH = 'WFH';

    public function label(): string
    {
        return match ($this) {
            self::WFO => 'WFO (Lokasi DUDI)',
            self::WFH => 'WFH (Work From Home)',
        };
    }

    public function shortLabel(): string
    {
        return $this->value;
    }
}
