<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\AccountSettingsServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Account Settings service for the SIMONGAN application.
 *
 * Thin service that reuses the existing Repository → Service pattern.
 * It does NOT create any new repositories — it reuses UserRepositoryInterface
 * for all persistence and delegates avatar storage to Laravel Storage.
 */
class AccountSettingsService extends Service implements AccountSettingsServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function updateProfile(User $user, array $attributes): User
    {
        // Only allow whitelisted, fillable profile fields.
        $allowed = [
            'name',
            'phone',
            'department',
            'address',
            'gender',
            'birth_date',
        ];

        $data = array_intersect_key($attributes, array_flip($allowed));

        /** @var User $updatedUser */
        $updatedUser = $this->users->update($user, $data);

        return $updatedUser;
    }

    /**
     * {@inheritDoc}
     */
    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        $user = $this->transaction(function () use ($user, $file): User {
            // Delete any previous avatar before storing the new one.
            if ($user->avatar !== null && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $extension = $file->getClientOriginalExtension() ?: 'jpg';

            $path = $file->storeAs(
                'avatars',
                sprintf('user-%d-%s.%s', $user->id, Str::random(16), $extension),
                'public',
            );

            if ($path === false) {
                throw new InvalidArgumentException('Gagal menyimpan foto profil.');
            }

            /** @var User $updatedUser */
            $updatedUser = $this->users->update($user, ['avatar' => $path]);

            return $updatedUser;
        });

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteAvatar(User $user): User
    {
        $user = $this->transaction(function () use ($user): User {
            if ($user->avatar !== null && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            /** @var User $updatedUser */
            $updatedUser = $this->users->update($user, ['avatar' => null]);

            return $updatedUser;
        });

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, (string) $user->password)) {
            return false;
        }

        $this->transaction(function () use ($user, $newPassword): void {
            $this->users->updatePassword($user, Hash::make($newPassword));
            $this->users->update($user, ['must_change_password' => false]);
        });

        return true;
    }
}
