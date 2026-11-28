<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PenempatanPKL;
use App\Models\User;

/**
 * Policy for PenempatanPKL authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * Super Admin bypasses all permission checks via before().
 */
class PenempatanPKLPolicy
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
     * Determine whether the user can view any penempatan PKL.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('penempatan.view');
    }

    /**
     * Determine whether the user can view the penempatan PKL.
     */
    public function view(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $user->hasPermissionTo('penempatan.view');
    }

    /**
     * Determine whether the user can create penempatan PKL.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('penempatan.create');
    }

    /**
     * Determine whether the user can update the penempatan PKL.
     */
    public function update(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $user->hasPermissionTo('penempatan.update');
    }

    /**
     * Determine whether the user can delete the penempatan PKL.
     */
    public function delete(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $user->hasPermissionTo('penempatan.delete');
    }

    /**
     * Determine whether the user can restore the penempatan PKL.
     */
    public function restore(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $user->hasPermissionTo('penempatan.restore');
    }

    /**
     * Determine whether the user can permanently delete the penempatan PKL.
     */
    public function forceDelete(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $user->hasPermissionTo('penempatan.forceDelete');
    }

    /**
     * Check In ability for Siswa on their active PenempatanPKL.
     *
     * This ability is defined here (not on AbsensiPolicy) because
     * the authorize() call in Siswa\AbsensiController passes a
     * PenempatanPKL model instance as the second argument, which
     * causes Laravel to resolve PenempatanPKLPolicy, not AbsensiPolicy.
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
     * Check Out ability for Siswa on their active PenempatanPKL.
     *
     * Delegates to checkIn since ownership check is identical.
     */
    public function checkOut(User $user, PenempatanPKL $penempatanPkl): bool
    {
        return $this->checkIn($user, $penempatanPkl);
    }
}
