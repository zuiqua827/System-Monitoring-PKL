<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\RoleRedirectHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForceChangePasswordRequest;
use App\Models\User;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles the mandatory password change flow.
 *
 * When a user has `must_change_password = true` (e.g., Siswa on first login
 * with default password = tanggal_lahir), they are forced to this page.
 *
 * After successfully changing the password:
 * - `must_change_password` is set to `false`
 * - User is redirected to their role-based dashboard
 */
class ForceChangePasswordController extends Controller
{
    public function __construct(
        private readonly UserAuthenticationServiceInterface $authenticationService,
    ) {}

    /**
     * Display the force change password form.
     */
    public function show(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect(RoleRedirectHelper::getDashboardUrl($user));
        }

        return view('auth.force-change-password');
    }

    /**
     * Handle the force change password form submission.
     */
    public function update(ForceChangePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authenticationService->forceChangePassword(
            user: $user,
            newPassword: (string) $request->validated('password'),
        );

        return redirect(RoleRedirectHelper::getDashboardUrl($user))
            ->with('status', 'Password berhasil diubah. Selamat datang!');
    }
}
