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

    /**
     * Force change password for first-login users.
     * Updates password and sets must_change_password to false.
     */
    public function forceChangePassword(User $user, string $newPassword): void;

    public function confirmPassword(User $user, string $password): bool;

    /**
     * Record login metadata (timestamp and IP address).
     */
    public function recordLoginMetadata(User $user, ?string $ipAddress): void;
}
