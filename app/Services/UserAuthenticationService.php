<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserAuthenticationService extends Service implements UserAuthenticationServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function register(array $attributes): User
    {
        /** @var User $user */
        $user = $this->transaction(fn (): User => $this->users->create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => Hash::make($attributes['password']),
        ]));

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $this->users->updatePassword(
                    user: $user,
                    hashedPassword: Hash::make($password),
                    rememberToken: Str::random(60),
                );

                event(new PasswordReset($user));
            },
        );
    }

    public function updatePassword(User $user, string $password): void
    {
        $this->users->updatePassword($user, Hash::make($password));
    }

    /**
     * Force change password for first-login users.
     * Updates password and clears the must_change_password flag.
     */
    public function forceChangePassword(User $user, string $newPassword): void
    {
        $this->transaction(function () use ($user, $newPassword): void {
            $this->users->updatePassword($user, Hash::make($newPassword));

            $this->users->update($user, [
                'must_change_password' => false,
            ]);
        });
    }

    public function confirmPassword(User $user, string $password): bool
    {
        return Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $password,
        ]);
    }

    /**
     * Record login metadata (last_login_at and last_login_ip).
     */
    public function recordLoginMetadata(User $user, ?string $ipAddress): void
    {
        $this->users->update($user, [
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }
}
