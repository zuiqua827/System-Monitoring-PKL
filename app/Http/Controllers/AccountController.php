<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Account\UpdateAccountInfoRequest;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Models\User;
use App\Services\Interfaces\AccountSettingsServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Account Settings controller for the SIMONGAN application.
 *
 * Thin controller: delegates all business logic to AccountSettingsService.
 * Available to every authenticated role (Super Admin, Guru, DUDI, Siswa).
 */
class AccountController extends Controller
{
    public function __construct(
        private readonly AccountSettingsServiceInterface $accountSettings,
    ) {}

    /**
     * Display the account settings page.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('account.index', [
            'user' => $user,
            'role' => $this->resolveRole($user),
        ]);
    }

    /**
     * Update the user's profile information + per-role fields + avatar.
     */
    public function updateInfo(UpdateAccountInfoRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->accountSettings->updateProfile($user, $request->validated());

        return redirect()
            ->route('account.index')
            ->with('success', 'Informasi akun berhasil diperbarui.');
    }

    /**
     * Upload / replace the user's avatar.
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $this->accountSettings->uploadAvatar($user, $request->file('avatar'));

        return redirect()
            ->route('account.index')
            ->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Delete the user's avatar.
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->accountSettings->deleteAvatar($user);

        return redirect()
            ->route('account.index')
            ->with('success', 'Foto profil berhasil dihapus.');
    }

    /**
     * Change the user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $changed = $this->accountSettings->changePassword(
            $user,
            (string) $request->input('current_password'),
            (string) $request->input('password'),
        );

        if (! $changed) {
            return redirect()
                ->route('account.index')
                ->with('error', 'Password saat ini tidak sesuai.');
        }

        return redirect()
            ->route('account.index')
            ->with('success', 'Password berhasil diubah.');
    }

    /**
     * Resolve the user's primary role for the account page.
     */
    private function resolveRole(User $user): string
    {
        if ($user->hasRole(UserRole::SUPER_ADMIN->value)) {
            return UserRole::SUPER_ADMIN->value;
        }

        if ($user->hasRole(UserRole::GURU->value)) {
            return UserRole::GURU->value;
        }

        if ($user->hasRole(UserRole::DUDI->value)) {
            return UserRole::DUDI->value;
        }

        return UserRole::SISWA->value;
    }
}
