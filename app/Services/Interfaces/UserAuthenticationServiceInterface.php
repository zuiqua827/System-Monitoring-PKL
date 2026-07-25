<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;

interface UserAuthenticationServiceInterface
{
    /**
     * @param array{name: string, email: string, password: string} $attributes
     */
    public function register(array $attributes): User;

    public function sendPasswordResetLink(string $email): string;

    /**
     * @param array{email: string, password: string, password_confirmation: string, token: string} $credentials
     */
    public function resetPassword(array $credentials): string;

    public function updatePassword(User $user, string $password): void;

    public function confirmPassword(User $user, string $password): bool;
}
