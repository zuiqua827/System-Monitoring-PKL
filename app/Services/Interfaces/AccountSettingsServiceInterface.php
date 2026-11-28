<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Service for the SIMONGAN Account Settings module.
 *
 * Handles profile information updates (including per-role fields), avatar
 * upload/replace/delete, and password changes. Reuses the existing
 * Repository → Service architecture and does not duplicate repositories.
 */
interface AccountSettingsServiceInterface
{
    /**
     * Update the authenticated user's profile information.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateProfile(User $user, array $attributes): User;

    /**
     * Upload (or replace) the user's profile photo.
     */
    public function uploadAvatar(User $user, UploadedFile $file): User;

    /**
     * Delete the user's profile photo.
     */
    public function deleteAvatar(User $user): User;

    /**
     * Change the user's password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool;
}
