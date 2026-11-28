<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Aktivitas;
use App\Models\User;

/**
 * Policy for Aktivitas (Daily Activity) authorization using Spatie Permission.
 *
 * Super Admin: full access via before()
 * Guru: view and validate aktivitas of students under their guidance
 * Siswa: manage own aktivitas (create, edit draft, delete draft, view own)
 */
class AktivitasPolicy
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
     * Determine whether the user can view any aktivitas.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('aktivitas.view')
            || $user->hasRole('Guru')
            || $user->hasRole('Siswa')
            || $user->hasRole('DUDI');
    }

    /**
     * Determine whether the user can view the aktivitas.
     */
    public function view(User $user, Aktivitas $aktivitas): bool
    {
        if ($user->hasPermissionTo('aktivitas.view')) {
            return true;
        }

        // Guru can only view aktivitas of students under their guidance
        if ($user->hasRole('Guru') && $user->guru) {
            return $aktivitas->penempatanPKL->guru_id === $user->guru->id;
        }

        // Siswa can only view own aktivitas
        if ($user->hasRole('Siswa') && $user->siswa) {
            return $aktivitas->penempatanPKL->siswa_id === $user->siswa->id;
        }

        // DUDI can only view aktivitas of students in their company
        if ($user->hasRole('DUDI') && $user->dudi) {
            return $aktivitas->penempatanPKL->dudi_id === $user->dudi->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create aktivitas.
     */
    public function create(User $user): bool
    {
        if ($user->hasPermissionTo('aktivitas.create')) {
            return true;
        }

        // Siswa can create aktivitas
        return $user->hasRole('Siswa') && $user->siswa !== null;
    }

    /**
     * Determine whether the user can update the aktivitas.
     */
    public function update(User $user, Aktivitas $aktivitas): bool
    {
        if ($user->hasPermissionTo('aktivitas.update')) {
            return true;
        }

        // Siswa can only edit own aktivitas that are still in draft
        if ($user->hasRole('Siswa') && $user->siswa) {
            return $aktivitas->penempatanPKL->siswa_id === $user->siswa->id
                && $aktivitas->status === 'draft';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the aktivitas.
     */
    public function delete(User $user, Aktivitas $aktivitas): bool
    {
        if ($user->hasPermissionTo('aktivitas.delete')) {
            return true;
        }

        // Siswa can only delete own aktivitas that are still in draft
        if ($user->hasRole('Siswa') && $user->siswa) {
            return $aktivitas->penempatanPKL->siswa_id === $user->siswa->id
                && $aktivitas->status === 'draft';
        }

        return false;
    }

    /**
     * Determine whether the user can restore the aktivitas.
     */
    public function restore(User $user, Aktivitas $aktivitas): bool
    {
        return $user->hasPermissionTo('aktivitas.restore');
    }

    /**
     * Determine whether the user can permanently delete the aktivitas.
     */
    public function forceDelete(User $user, Aktivitas $aktivitas): bool
    {
        return $user->hasPermissionTo('aktivitas.forceDelete');
    }

    /**
     * Determine whether the user can submit aktivitas for validation.
     */
    public function submit(User $user, Aktivitas $aktivitas): bool
    {
        // Siswa can submit own draft aktivitas
        if ($user->hasRole('Siswa') && $user->siswa) {
            return $aktivitas->penempatanPKL->siswa_id === $user->siswa->id
                && $aktivitas->status === 'draft';
        }

        return false;
    }

    /**
     * Determine whether the user can validate (approve/reject) aktivitas.
     */
    public function validate(User $user, Aktivitas $aktivitas): bool
    {
        // Guru can validate aktivitas of students under their guidance
        if ($user->hasRole('Guru') && $user->guru) {
            return $aktivitas->penempatanPKL->guru_id === $user->guru->id
                && $aktivitas->status === 'menunggu_validasi';
        }

        return false;
    }
}

