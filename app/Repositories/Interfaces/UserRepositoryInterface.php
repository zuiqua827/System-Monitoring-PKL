<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;

/**
 * @extends BaseRepositoryInterface<User>
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function updatePassword(User $user, string $hashedPassword, ?string $rememberToken = null): User;
}
