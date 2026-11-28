<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Jurusan;
use App\Models\User;

/**
 * Policy for Jurusan authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * No manual role checks — all authorization goes through permissions.
 */
class JurusanPolicy
{
    /**
     * Determine whether the user can view any jurusan.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('jurusan.view');
    }

    /**
     * Determine whether the user can view the jurusan.
     */
    public function view(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('jurusan.view');
    }

    /**
     * Determine whether the user can create jurusan.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('jurusan.create');
    }

    /**
     * Determine whether the user can update the jurusan.
     */
    public function update(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('jurusan.update');
    }

    /**
     * Determine whether the user can delete the jurusan.
     */
    public function delete(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('jurusan.delete');
    }

    /**
     * Determine whether the user can restore the jurusan.
     */
    public function restore(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('jurusan.restore');
    }

    /**
     * Determine whether the user can permanently delete the jurusan.
     */
    public function forceDelete(User $user, Jurusan $jurusan): bool
    {
        return $user->hasPermissionTo('jurusan.forceDelete');
    }
}
