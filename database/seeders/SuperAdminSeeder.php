<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => UserRole::SUPER_ADMIN->value,
            'guard_name' => 'web',
        ]);

        /** @var User|null $user */
        $user = User::where('email', 'simon@gmail.com')->first();

        if ($user !== null) {
            $updates = [];
            if ($user->role_id !== $role->id) {
                $updates['role_id'] = $role->id;
            }
            if ($user->email_verified_at === null) {
                $updates['email_verified_at'] = now();
            }
            if (! empty($updates)) {
                $user->forceFill($updates)->save();
            }
        } else {
            $existingSuperAdmins = User::role(UserRole::SUPER_ADMIN->value)->get();
            $superAdminCount = $existingSuperAdmins->count();

            if ($superAdminCount > 1) {
                throw new RuntimeException(
                    "Konflik identitas Super Admin: Ditemukan {$superAdminCount} akun ber-role Super Admin tanpa adanya akun simon@gmail.com. " .
                    "Email akun Super Admin yang sudah ada tidak boleh diubah secara otomatis."
                );
            }

            if ($superAdminCount === 1) {
                /** @var User $singleAdmin */
                $singleAdmin = $existingSuperAdmins->first();
                if ($singleAdmin->email === 'admin@monitoringpkl.test') {
                    $user = $singleAdmin;
                    $user->forceFill([
                        'email' => 'simon@gmail.com',
                        'role_id' => $role->id,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();
                } else {
                    throw new RuntimeException(
                        "Konflik identitas Super Admin: Ditemukan akun Super Admin dengan email [{$singleAdmin->email}]. " .
                        "Email akun Super Admin lain tidak boleh diubah secara otomatis menjadi simon@gmail.com."
                    );
                }
            } else {
                /** @var User|null $legacyAdmin */
                $legacyAdmin = User::where('email', 'admin@monitoringpkl.test')->first();
                if ($legacyAdmin !== null) {
                    $user = $legacyAdmin;
                    $user->forceFill([
                        'email' => 'simon@gmail.com',
                        'role_id' => $role->id,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();
                } else {
                    $user = User::create([
                        'name' => 'Super Administrator',
                        'email' => 'simon@gmail.com',
                        'email_verified_at' => now(),
                        'password' => Hash::make('password'),
                        'role_id' => $role->id,
                        'must_change_password' => false,
                    ]);
                }
            }
        }

        if (! $user->hasRole(UserRole::SUPER_ADMIN->value)) {
            $user->assignRole($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
