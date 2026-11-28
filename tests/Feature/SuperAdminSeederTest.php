<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate(UserRole::SUPER_ADMIN->value, 'web');
});

it('1. creates super admin simon@gmail.com when database has no admin', function (): void {
    expect(User::where('email', 'simon@gmail.com')->exists())->toBeFalse();

    $seeder = new SuperAdminSeeder();
    $seeder->run();

    $user = User::where('email', 'simon@gmail.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Super Administrator')
        ->and($user->email)->toBe('simon@gmail.com')
        ->and($user->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->must_change_password)->toBeFalse()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

it('2. preserves existing simon@gmail.com account without resetting password or creating duplicates', function (): void {
    $existing = User::factory()->create([
        'name' => 'Existing Simon',
        'email' => 'simon@gmail.com',
        'password' => Hash::make('custom_secret_password_999'),
    ]);
    $existing->assignRole(UserRole::SUPER_ADMIN->value);

    $initialCount = User::count();

    $seeder = new SuperAdminSeeder();
    $seeder->run();

    expect(User::count())->toBe($initialCount);

    $refreshed = User::where('email', 'simon@gmail.com')->first();
    expect($refreshed)->not->toBeNull()
        ->and($refreshed->id)->toBe($existing->id)
        ->and(Hash::check('custom_secret_password_999', $refreshed->password))->toBeTrue()
        ->and(Hash::check('password', $refreshed->password))->toBeFalse()
        ->and($refreshed->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue();
});

it('3. safely migrates legacy admin@monitoringpkl.test without resetting password', function (): void {
    $legacy = User::factory()->create([
        'name' => 'Legacy Super Admin',
        'email' => 'admin@monitoringpkl.test',
        'password' => Hash::make('legacy_preserved_pwd_123'),
    ]);
    $legacy->assignRole(UserRole::SUPER_ADMIN->value);

    $seeder = new SuperAdminSeeder();
    $seeder->run();

    expect(User::where('email', 'admin@monitoringpkl.test')->exists())->toBeFalse();

    $migrated = User::where('email', 'simon@gmail.com')->first();
    expect($migrated)->not->toBeNull()
        ->and($migrated->id)->toBe($legacy->id)
        ->and(Hash::check('legacy_preserved_pwd_123', $migrated->password))->toBeTrue()
        ->and($migrated->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue();
});

it('4. throws exception when multiple super admins exist without simon@gmail.com', function (): void {
    $admin1 = User::factory()->create(['email' => 'admin1@smkn1.sch.id']);
    $admin1->assignRole(UserRole::SUPER_ADMIN->value);

    $admin2 = User::factory()->create(['email' => 'admin2@smkn1.sch.id']);
    $admin2->assignRole(UserRole::SUPER_ADMIN->value);

    $seeder = new SuperAdminSeeder();

    expect(fn () => $seeder->run())
        ->toThrow(RuntimeException::class, 'Konflik identitas Super Admin');

    // Confirm neither admin email was mutated
    expect(User::where('email', 'admin1@smkn1.sch.id')->exists())->toBeTrue()
        ->and(User::where('email', 'admin2@smkn1.sch.id')->exists())->toBeTrue()
        ->and(User::where('email', 'simon@gmail.com')->exists())->toBeFalse();
});

it('5. throws exception when single super admin has unexpected email and is not legacy seeder account', function (): void {
    $customAdmin = User::factory()->create(['email' => 'kepsek@smkn1.sch.id']);
    $customAdmin->assignRole(UserRole::SUPER_ADMIN->value);

    $seeder = new SuperAdminSeeder();

    expect(fn () => $seeder->run())
        ->toThrow(RuntimeException::class, 'Konflik identitas Super Admin');

    expect(User::where('email', 'kepsek@smkn1.sch.id')->exists())->toBeTrue()
        ->and(User::where('email', 'simon@gmail.com')->exists())->toBeFalse();
});

it('6. is strictly idempotent and can be run multiple times safely', function (): void {
    $seeder = new SuperAdminSeeder();

    $seeder->run();
    $countAfterFirst = User::count();
    $firstUser = User::where('email', 'simon@gmail.com')->first();

    $seeder->run();
    $countAfterSecond = User::count();
    $secondUser = User::where('email', 'simon@gmail.com')->first();

    expect($countAfterSecond)->toBe($countAfterFirst)
        ->and($secondUser->id)->toBe($firstUser->id)
        ->and($secondUser->email)->toBe('simon@gmail.com')
        ->and($secondUser->hasRole(UserRole::SUPER_ADMIN->value))->toBeTrue();
});

it('7. does not mutate password when re-run on existing user with custom password', function (): void {
    $user = User::factory()->create([
        'email' => 'simon@gmail.com',
        'password' => Hash::make('very_secure_custom_pwd_456'),
    ]);
    $user->assignRole(UserRole::SUPER_ADMIN->value);

    $seeder = new SuperAdminSeeder();
    $seeder->run();
    $seeder->run();

    $refreshed = User::where('email', 'simon@gmail.com')->first();
    expect(Hash::check('very_secure_custom_pwd_456', $refreshed->password))->toBeTrue();
});
