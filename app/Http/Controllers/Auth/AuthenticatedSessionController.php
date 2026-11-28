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
 * Controller untuk mengelola sesi autentikasi pengguna (Login & Logout).
 *
 * Alur Proses Login:
 * 1. Autentikasi Kredensial:
 *    - Super Admin melalui email + password pada rute /admin/login.
 *    - Siswa melalui NIS + password (tanggal lahir atau password baru) pada rute /login.
 *    - Guru & DUDI melalui email/username + password pada rute /login.
 * 2. Session Regeneration:
 *    - Meregenerasi ID sesi untuk mencegah serangan Session Fixation.
 * 3. Pencatatan Metadata Login:
 *    - Mencatat waktu login terakhir dan alamat IP klien.
 * 4. Pengalihan Berbasis Role (Role-based Redirection):
 *    - RoleRedirectHelper menentukan URL dashboard tujuan (/admin/dashboard, /guru/dashboard, dst).
 * 5. Pencegatan Password Sementara:
 *    - Jika user memiliki flag `must_change_password = true`, middleware ForceChangePassword
 *      akan mencegat dan mengarahkan pengguna ke form penggantian password.
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
