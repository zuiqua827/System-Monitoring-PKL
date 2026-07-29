<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Absensi (Attendance) statuses.
 *
 * Matches the required statuses:
 * - Hadir (Present)
 * - Terlambat (Late)
 * - Izin (Permit)
 * - Sakit (Sick)
 * - Alpha (Absent without reason)
 */
enum AbsensiStatus: string
{
    case HADIR = 'hadir';
    case TERLAMBAT = 'terlambat';
    case IZIN = 'izin';
    case SAKIT = 'sakit';
    case ALPHA = 'alpha';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::HADIR => 'Hadir',
            self::TERLAMBAT => 'Terlambat',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::ALPHA => 'Alpha',
        };
    }

    /**
     * Get the color class for badge display.
     */
    public function color(): string
    {
        return match ($this) {
            self::HADIR => 'green',
            self::TERLAMBAT => 'yellow',
            self::IZIN => 'blue',
            self::SAKIT => 'orange',
            self::ALPHA => 'red',
        };
    }

    /**
     * Get all available status values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}

