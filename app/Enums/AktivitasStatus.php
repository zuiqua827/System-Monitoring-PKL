<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for Aktivitas (Daily Activity) statuses.
 *
 * Status flow:
 * - Draft: Siswa is still editing
 * - Menunggu Validasi: Submitted for guru validation
 * - Disetujui: Approved by guru
 * - Ditolak: Rejected by guru
 */
enum AktivitasStatus: string
{
    case DRAFT = 'draft';
    case MENUNGGU_VALIDASI = 'menunggu_validasi';
    case DISETUJUI = 'disetujui';
    case DITOLAK = 'ditolak';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::MENUNGGU_VALIDASI => 'Menunggu Validasi',
            self::DISETUJUI => 'Disetujui',
            self::DITOLAK => 'Ditolak',
        };
    }

    /**
     * Get the color class for badge display.
     */
    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::MENUNGGU_VALIDASI => 'yellow',
            self::DISETUJUI => 'green',
            self::DITOLAK => 'red',
        };
    }

    /**
     * Get all available status values.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}

