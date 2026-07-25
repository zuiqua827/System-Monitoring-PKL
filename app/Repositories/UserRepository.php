<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

/**
 * @extends EloquentRepository<User>
 */
class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function updatePassword(User $user, string $hashedPassword, ?string $rememberToken = null): User
    {
        $attributes = ['password' => $hashedPassword];

        if ($rememberToken !== null) {
            $attributes['remember_token'] = $rememberToken;
        }

        /** @var User $updatedUser */
        $updatedUser = $this->update($user, $attributes);

        return $updatedUser;
    }
}
