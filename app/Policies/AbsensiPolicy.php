<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Absensi;
use App\Models\PenempatanPKL;
use App\Models\User;

/**
 * Policy for Absensi authorization using Spatie Permission.
 *
 * Hak akses:
 * - Super Admin: CRUD penuh
 * - Guru: Lihat absensi siswa bimbingan, validasi absensi
 * - Siswa: Check In, Check Out, lihat absensi sendiri
 */
class AbsensiPolicy
{
    /**
     * Super Admin bypass — grant all abilities.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any absensi.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('absensi.view')
            || $user->hasRole('Guru')
            || $user->hasRole('Siswa');
    }

    /**
     * Determine whether the user can view the absensi.
     */
    public function view(User $user, Absensi $absensi): bool
    {
        if ($user->hasPermissionTo('absensi.view')) {
            return true;
        }

        // Guru can view absensi of students under their guidance
        if ($user->hasRole('Guru')) {
            $guru = $user->guru;
            if ($guru !== null) {
                return $absensi->penempatanPKL?->guru_id === $guru->id;
            }
        }

        // Siswa can only view their own absensi
        if ($user->hasRole('Siswa')) {
            $siswa = $user->siswa;
            if ($siswa !== null) {
                return $absensi->penempatanPKL?->siswa_id === $siswa->id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create absensi (admin CRUD).
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('absensi.view');
    }

    /**
     * Determine whether the user can update the absensi (admin CRUD).
     */
    public function update(User $user, Absensi $absensi): bool
    {
        return $user->hasPermissionTo('absensi.view');
    }

    /**
     * Determine whether the user can delete the absensi.
     */
    public function delete(User $user, Absensi $absensi): bool
    {
        return $user->hasPermissionTo('absensi.view');
    }

    /**
     * Determine whether the user can restore the absensi.
     */
    public function restore(User $user, Absensi $absensi): bool
    {
        return $user->hasPermissionTo('absensi.view');
    }

    /**
     * Determine whether the user can permanently delete the absensi.
     */
    public function forceDelete(User $user, Absensi $absensi): bool
    {
        return $user->hasPermissionTo('absensi.view');
    }

    /**
     * Check In ability for Siswa.
     */
    public function checkIn(User $user, PenempatanPKL $penempatanPkl): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Siswa')) {
            $siswa = $user->siswa;
            if ($siswa !== null) {
                return $penempatanPkl->siswa_id === $siswa->id;
            }
        }

        return false;
    }

    /**
     * Check Out ability for Siswa.
     */
    public function checkOut(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $this->checkIn($user, $penempatanPkl);
    }

    /**
     * Verify/validate absensi ability for Guru.
     */
    public function verify(User $user, Absensi $absensi): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Guru') && $user->hasPermissionTo('absensi.verify')) {
            $guru = $user->guru;
            if ($guru !== null) {
                return $absensi->penempatanPKL?->guru_id === $guru->id;
            }
        }

        return false;
    }
}

