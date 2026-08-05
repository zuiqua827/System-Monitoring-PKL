<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\RoleRedirectHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Requests\Auth\PklLoginRequest;
use App\Models\User;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Handles user authentication sessions (login/logout).
 *
 * Login flow:
 * 1. Authenticate credentials
 * 2. Record login metadata (timestamp + IP)
 * 3. Redirect based on role (Super Admin, Guru, DUDI, Siswa)
 * 4. If must_change_password is true, ForceChangePassword middleware will intercept
 *
 * Two entry points:
 *  - /admin/login  -> Super Admin only (email + password)
 *  - /login        -> PKL users (Siswa, Guru, DUDI) via role tabs
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly UserAuthenticationServiceInterface $authenticationService,
    ) {}

    /**
     * Display the PKL users login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Display the Super Admin login view.
     */
    public function createAdmin(): View
    {
        return view('admin.login');
    }

    /**
     * Handle an incoming PKL users authentication request.
     */
    public function store(PklLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        // Record login metadata
        $this->authenticationService->recordLoginMetadata(
            user: $user,
            ipAddress: $request->ip(),
        );

        // Redirect based on user role
        $dashboardUrl = RoleRedirectHelper::getDashboardUrl($user);

        return redirect()->intended($dashboardUrl);
    }

    /**
     * Handle an incoming Super Admin authentication request.
     */
    public function storeAdmin(AdminLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        // Record login metadata
        $this->authenticationService->recordLoginMetadata(
            user: $user,
            ipAddress: $request->ip(),
        );

        // Super Admin always redirects to the admin dashboard
        return redirect()->intended('/admin/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
