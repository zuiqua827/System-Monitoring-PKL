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
}
