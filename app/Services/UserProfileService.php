<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserProfileServiceInterface;

class UserProfileService extends Service implements UserProfileServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function update(User $user, array $attributes): User
    {
        if (array_key_exists('email', $attributes) && $attributes['email'] !== $user->email) {
            $attributes['email_verified_at'] = null;
        }

        /** @var User $updatedUser */
        $updatedUser = $this->users->update($user, $attributes);

        return $updatedUser;
    }

    public function delete(User $user): void
    {
        $this->users->delete($user);
    }
}
