<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

/**
 * Policy for Siswa authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * No manual role checks — all authorization goes through permissions.
 */
class SiswaPolicy
{
    /**
     * Determine whether the user can view any siswa.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('siswa.view');
    }

    /**
     * Determine whether the user can view the siswa.
     */
    public function view(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('siswa.view');
    }

    /**
     * Determine whether the user can create siswa.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('siswa.create');
    }

    /**
     * Determine whether the user can update the siswa.
     */
    public function update(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('siswa.update');
    }

    /**
     * Determine whether the user can delete the siswa.
     */
    public function delete(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('siswa.delete');
    }

    /**
     * Determine whether the user can restore the siswa.
     */
    public function restore(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('siswa.restore');
    }

    /**
     * Determine whether the user can permanently delete the siswa.
     */
    public function forceDelete(User $user, Siswa $siswa): bool
    {
        return $user->hasPermissionTo('siswa.forceDelete');
    }
}
