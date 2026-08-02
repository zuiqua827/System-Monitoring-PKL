<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Penilaian;
use App\Models\User;

/**
 * Policy for Penilaian authorization using Spatie Permission.
 *
 * Uses permission-based checks via Spatie's hasPermissionTo().
 * Super Admin bypasses all permission checks via before().
 */
class PenilaianPolicy
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
     * Determine whether the user can view any penilaian.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('penilaian.view');
    }

    /**
     * Determine whether the user can view the penilaian.
     * Siswa can only view their own penilaian.
     */
    public function view(User $user, Penilaian $penilaian): bool
    {
        if ($user->hasPermissionTo('penilaian.view')) {
            // Guru can view penilaian of students under their guidance
            if ($user->hasRole('Guru')) {
                $guru = $user->guru;
                if ($guru !== null) {
                    return $penilaian->penempatanPKL->guru_id === $guru->id;
                }
                return false;
            }

            // Siswa can only view their own penilaian
            if ($user->hasRole('Siswa')) {
                $siswa = $user->siswa;
                if ($siswa !== null) {
                    return $penilaian->penempatanPKL->siswa_id === $siswa->id;
                }
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create penilaian.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('penilaian.create');
    }

    /**
     * Determine whether the user can update the penilaian.
     * Guru can only update if status is draft.
     * Super Admin can always update (handled by before()).
     */
    public function update(User $user, Penilaian $penilaian): bool
    {
        if ($user->hasRole('Guru')) {
            // Guru can only edit if status is draft
            if ($penilaian->status === 'final') {
                return false;
            }

            // Guru can only edit penilaian of their own students
            $guru = $user->guru;
            if ($guru !== null) {
                return $penilaian->penempatanPKL->guru_id === $guru->id;
            }

            return false;
        }

        return $user->hasPermissionTo('penilaian.update');
    }

    /**
     * Determine whether the user can delete the penilaian.
     */
    public function delete(User $user, Penilaian $penilaian): bool
    {
        return $user->hasPermissionTo('penilaian.delete');
    }

    /**
     * Determine whether the user can restore the penilaian.
     */
    public function restore(User $user, Penilaian $penilaian): bool
    {
        return $user->hasPermissionTo('penilaian.restore');
    }

    /**
     * Determine whether the user can permanently delete the penilaian.
     */
    public function forceDelete(User $user, Penilaian $penilaian): bool
    {
        return $user->hasPermissionTo('penilaian.forceDelete');
    }

    /**
     * Determine whether the user can finalize the penilaian.
     * Guru can finalize their own students' penilaian.
     */
    public function finalize(User $user, Penilaian $penilaian): bool
    {
        if ($user->hasRole('Guru')) {
            if ($penilaian->status === 'final') {
                return false;
            }

            $guru = $user->guru;
            if ($guru !== null) {
                return $penilaian->penempatanPKL->guru_id === $guru->id;
            }

            return false;
        }

        return $user->hasPermissionTo('penilaian.update');
    }
}
