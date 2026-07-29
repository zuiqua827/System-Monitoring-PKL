<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guru;
use App\Models\User;

/**
 * Policy for Guru authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * No manual role checks — all authorization goes through permissions.
 */
class GuruPolicy
{
    /**
     * Determine whether the user can view any guru.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('guru.view');
    }

    /**
     * Determine whether the user can view the guru.
     */
    public function view(User $user, Guru $guru): bool
    {
        return $user->hasPermissionTo('guru.view');
    }

    /**
     * Determine whether the user can create guru.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('guru.create');
    }

    /**
     * Determine whether the user can update the guru.
     */
    public function update(User $user, Guru $guru): bool
    {
        return $user->hasPermissionTo('guru.update');
    }

    /**
     * Determine whether the user can delete the guru.
     */
    public function delete(User $user, Guru $guru): bool
    {
        return $user->hasPermissionTo('guru.delete');
    }

    /**
     * Determine whether the user can restore the guru.
     */
    public function restore(User $user, Guru $guru): bool
    {
        return $user->hasPermissionTo('guru.restore');
    }

    /**
     * Determine whether the user can permanently delete the guru.
     */
    public function forceDelete(User $user, Guru $guru): bool
    {
        return $user->hasPermissionTo('guru.forceDelete');
    }
}

