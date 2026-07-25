<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;

interface UserProfileServiceInterface
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function update(User $user, array $attributes): User;

    public function delete(User $user): void;
}
