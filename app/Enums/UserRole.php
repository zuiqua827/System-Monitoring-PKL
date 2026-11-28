<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'Super Admin';
    case GURU = 'Guru';
    case DUDI = 'DUDI';
    case SISWA = 'Siswa';

    public function label(): string
    {
        return $this->value;
    }
}
