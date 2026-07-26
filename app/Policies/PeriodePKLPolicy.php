<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PeriodePKL;
use App\Models\User;

/**
 * Policy for PeriodePKL authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * No manual role checks — all authorization goes through permissions.
 */
class PeriodePKLPolicy
{
    /**
     * Determine whether the user can view any periode PKL.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('periode.view');
    }

    /**
     * Determine whether the user can view the periode PKL.
     */
    public function view(User $user, PeriodePKL $periodePkl): bool
    {
        return $user->hasPermissionTo('periode.view');
    }

    /**
     * Determine whether the user can create periode PKL.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('periode.create');
    }

    /**
     * Determine whether the user can update the periode PKL.
     */
    public function update(User $user, PeriodePKL $periodePkl): bool
    {
        return $user->hasPermissionTo('periode.update');
    }

    /**
     * Determine whether the user can delete the periode PKL.
     */
    public function delete(User $user, PeriodePKL $periodePkl): bool
    {
        return $user->hasPermissionTo('periode.delete');
    }

    /**
     * Determine whether the user can restore the periode PKL.
     */
    public function restore(User $user, PeriodePKL $periodePkl): bool
    {
        return $user->hasPermissionTo('periode.restore');
    }

    /**
     * Determine whether the user can permanently delete the periode PKL.
     */
    public function forceDelete(User $user, PeriodePKL $periodePkl): bool
    {
        return $user->hasPermissionTo('periode.forceDelete');
    }
}
