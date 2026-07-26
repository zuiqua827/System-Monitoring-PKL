<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Determines the appropriate redirect URL based on user role.
 *
 * Each role has a dedicated dashboard route.
 * Falls back to generic dashboard if role is unknown.
 */
final class RoleRedirectHelper
{
    /**
     * @var array<string, string>
     */
    private const ROLE_DASHBOARD_MAP = [
        'Super Admin' => '/admin/dashboard',
        'Guru' => '/guru/dashboard',
        'DUDI' => '/dudi/dashboard',
        'Siswa' => '/siswa/dashboard',
    ];

    /**
     * Get the dashboard URL for the given user based on their role.
     */
    public static function getDashboardUrl(User $user): string
    {
        $roleName = self::getUserRoleName($user);

        return self::ROLE_DASHBOARD_MAP[$roleName] ?? '/dashboard';
    }

    /**
     * Get the named route for the user's dashboard.
     */
    public static function getDashboardRouteName(User $user): string
    {
        $roleName = self::getUserRoleName($user);

        return match ($roleName) {
            UserRole::SUPER_ADMIN->value => 'admin.dashboard',
            UserRole::GURU->value => 'guru.dashboard',
            UserRole::DUDI->value => 'dudi.dashboard',
            UserRole::SISWA->value => 'siswa.dashboard',
            default => 'dashboard',
        };
    }

    /**
     * Get the primary role name for a user.
     */
    private static function getUserRoleName(User $user): ?string
    {
        /** @var \Spatie\Permission\Models\Role|null $role */
        $role = $user->roles->first();

        return $role?->name;
    }
}
