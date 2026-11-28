<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Dudi;
use App\Models\User;

/**
 * Policy for DUDI authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * No manual role checks — all authorization goes through permissions.
 */
class DudiPolicy
{
    /**
     * Determine whether the user can view any DUDI.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('dudi.view');
    }

    /**
     * Determine whether the user can view the DUDI.
     */
    public function view(User $user, Dudi $dudi): bool
    {
        return $user->hasPermissionTo('dudi.view');
    }

    /**
     * Determine whether the user can create DUDI.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('dudi.create');
    }

    /**
     * Determine whether the user can update the DUDI.
     */
    public function update(User $user, Dudi $dudi): bool
    {
        return $user->hasPermissionTo('dudi.update');
    }

    /**
     * Determine whether the user can delete the DUDI.
     */
    public function delete(User $user, Dudi $dudi): bool
    {
        return $user->hasPermissionTo('dudi.delete');
    }

    /**
     * Determine whether the user can restore the DUDI.
     */
    public function restore(User $user, Dudi $dudi): bool
    {
        return $user->hasPermissionTo('dudi.restore');
    }

    /**
     * Determine whether the user can permanently delete the DUDI.
     */
    public function forceDelete(User $user, Dudi $dudi): bool
    {
        return $user->hasPermissionTo('dudi.forceDelete');
    }
}
