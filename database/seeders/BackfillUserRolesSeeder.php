<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Backfills Spatie `model_has_roles` pivot rows for users who only have a
 * legacy `role_id` column value (or a related profile) but no actual Spatie
 * role assignment.
 *
 * This seeder is IDEMPOTENT:
 * - Users that already have at least one Spatie role are skipped entirely.
 * - No roles are ever deleted or removed.
 * - Existing pivot rows are never modified.
 *
 * Mapping source order (first match wins):
 *   1. The legacy `users.role_id` column (if it references a known role).
 *   2. The related profile table (guru/dudi/siswa) if the user is linked.
 *
 * After this seeder runs, `model_has_roles` is the single source of truth for
 * authorization (Spatie), exactly like freshly-created users from the factories.
 */
class BackfillUserRolesSeeder extends Seeder
{
    /**
     * Map a legacy role_id / role name to the Spatie role name.
     *
     * @var array<int, string>
     */
    private const ROLE_ID_MAP = [
        1 => UserRole::SUPER_ADMIN->value,
        2 => UserRole::SUPER_ADMIN->value,
        3 => UserRole::GURU->value,
        4 => UserRole::DUDI->value,
        5 => UserRole::SISWA->value,
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure the four roles exist before assigning.
        $roles = [];
        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::firstOrCreate([
                'name' => $roleEnum->value,
                'guard_name' => 'web',
            ]);
            $roles[$role->id] = $role;
        }

        $assigned = 0;
        $skippedWithRole = 0;
        $skippedUnknown = 0;

        User::with('guru', 'dudi', 'siswa')->orderBy('id')->chunk(100, function ($users) use ($roles, &$assigned, &$skippedWithRole, &$skippedUnknown) {
            foreach ($users as $user) {
                // Already has a Spatie role → leave untouched.
                if ($user->roles()->exists()) {
                    $skippedWithRole++;

                    continue;
                }

                $roleName = $this->resolveRoleName($user);

                if ($roleName === null) {
                    $skippedUnknown++;

                    continue;
                }

                $role = $roles[$roleName] ?? Role::findByName($roleName, 'web');

                if (! $role) {
                    $skippedUnknown++;

                    continue;
                }

                $user->assignRole($role);
                $assigned++;
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            sprintf(
                'BackfillUserRolesSeeder: assigned=%d, already_had_role=%d, skipped_unknown=%d',
                $assigned,
                $skippedWithRole,
                $skippedUnknown
            )
        );
    }

    /**
     * Resolve the Spatie role name for a user without roles.
     */
    private function resolveRoleName(User $user): ?string
    {
        // 1) Legacy role_id column.
        if (! empty($user->role_id)) {
            $roleName = self::ROLE_ID_MAP[(int) $user->role_id] ?? null;

            if ($roleName !== null) {
                return $roleName;
            }
        }

        // 2) Related profile tables.
        if ($user->relationLoaded('guru') && $user->guru !== null) {
            return UserRole::GURU->value;
        }

        if ($user->relationLoaded('dudi') && $user->dudi !== null) {
            return UserRole::DUDI->value;
        }

        if ($user->relationLoaded('siswa') && $user->siswa !== null) {
            return UserRole::SISWA->value;
        }

        return null;
    }
}

