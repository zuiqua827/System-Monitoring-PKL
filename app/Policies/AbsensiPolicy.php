<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Absensi;
use App\Models\PenempatanPKL;
use App\Models\User;

/**
 * Policy for Absensi authorization using Spatie Permission.
 *
 * Hak akses:
 * - Super Admin: CRUD penuh
 * - Guru: Lihat absensi siswa bimbingan, validasi absensi
 * - Siswa: Check In, Check Out, lihat absensi sendiri
 *
 * NOTE: checkIn() and checkOut() are defined in PenempatanPKLPolicy,
 * because Siswa\AbsensiController::checkIn() and ::checkOut()
 * call `authorize('checkIn', $penempatanAktif)` where $penempatanAktif
 * is a PenempatanPKL model, causing Laravel to resolve PenempatanPKLPolicy.
 */
class AbsensiPolicy
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
     * Determine whether the user can view any absensi.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('absensi.view')
            || $user->hasRole('Guru')
            || $user->hasRole('Siswa')
            || $user->hasRole('DUDI');
    }

    /**
     * Determine whether the user can view the absensi.
     */
    public function view(User $user, Absensi $absensi): bool
    {
        if ($user->hasPermissionTo('absensi.view')) {
            return true;
        }

        // Guru can view absensi of students under their guidance
        if ($user->hasRole('Guru')) {
            $guru = $user->guru;
            if ($guru !== null) {
                return $absensi->penempatanPKL->guru_id === $guru->id;
            }
        }

        // Siswa can only view their own absensi
        if ($user->hasRole('Siswa')) {
            $siswa = $user->siswa;
            if ($siswa !== null) {
                return $absensi->penempatanPKL->siswa_id === $siswa->id;
            }
        }

        // DUDI can only view absensi of students in their company
        if ($user->hasRole('DUDI')) {
            $dudi = $user->dudi;
            if ($dudi !== null) {
                return $absensi->penempatanPKL->dudi_id === $dudi->id;
            }
        }

        return false;
    }

/**
     * Determine whether the user can create absensi (admin CRUD).
     *
     * Only Super Admin (handled by before()) may create/update/delete
     * attendance records. Guru, DUDI, and Siswa are strictly view-only
     * (except Siswa's own check-in/check-out workflow, which is managed
     * through PenempatanPKLPolicy, not here).
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the absensi (admin CRUD).
     */
    public function update(User $user, Absensi $absensi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the absensi.
     */
    public function delete(User $user, Absensi $absensi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the absensi.
     */
    public function restore(User $user, Absensi $absensi): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the absensi.
     */
    public function forceDelete(User $user, Absensi $absensi): bool
    {
        return false;
    }

    /**
     * Verify/validate absensi ability for Guru.
     */
    public function verify(User $user, Absensi $absensi): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Guru') && $user->hasPermissionTo('absensi.verify')) {
            $guru = $user->guru;
            if ($guru !== null) {
                return $absensi->penempatanPKL->guru_id === $guru->id;
            }
        }

        return false;
    }
}
