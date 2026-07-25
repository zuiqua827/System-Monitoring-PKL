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
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

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

    public function confirmPassword(User $user, string $password): bool
    {
        return Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $password,
        ]);
    }
}
