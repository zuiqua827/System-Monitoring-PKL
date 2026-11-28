<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

/**
 * Policy for Kelas authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * No manual role checks — all authorization goes through permissions.
 */
class KelasPolicy
{
    /**
     * Determine whether the user can view any kelas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('kelas.view');
    }

    /**
     * Determine whether the user can view the kelas.
     */
    public function view(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('kelas.view');
    }

    /**
     * Determine whether the user can create kelas.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('kelas.create');
    }

    /**
     * Determine whether the user can update the kelas.
     */
    public function update(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('kelas.update');
    }

    /**
     * Determine whether the user can delete the kelas.
     */
    public function delete(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('kelas.delete');
    }

    /**
     * Determine whether the user can restore the kelas.
     */
    public function restore(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('kelas.restore');
    }

    /**
     * Determine whether the user can permanently delete the kelas.
     */
    public function forceDelete(User $user, Kelas $kelas): bool
    {
        return $user->hasPermissionTo('kelas.forceDelete');
    }
}
