<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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

        $user = User::firstOrCreate([
            'email' => 'admin@monitoringpkl.test',
        ], [
            'name' => 'Super Administrator',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $user->forceFill([
            'name' => 'Super Administrator',
            'role_id' => $role->id,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ])->save();

        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
