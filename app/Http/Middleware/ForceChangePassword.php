<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk mewajibkan pengguna mengganti password default pada saat pertama kali login.
 *
 * Tujuan Keamanan:
 * - Siswa yang baru dibuat akunnya memiliki password bawaan berupa tanggal lahir (YYYY-MM-DD).
 * - Sistem menandai kolom `must_change_password = true` di tabel users.
 * - Middleware ini menghadang seluruh akses halaman dan mengarahkannya ke 'force-change-password'.
 *
 * Pengecualian (Bypass):
 * 1. Rute 'force-change-password' dan 'logout' (mencegah redirect loop tak berujung).
 * 2. Pengguna yang login via SSO SiPintu (kredensial dikelola di server SiPintu).
 */
class ForceChangePassword

{
    /**
     * Routes that should be accessible even when password change is required.
     *
     * @var list<string>
     */
    private const EXCLUDED_ROUTES = [
        'force-change-password',
        'force-change-password.update',
        'logout',
        'auth.callback',
        'oauth.callback',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        // SSO users manage credentials in SiPintu and must not be forced to change local SIMONGAN password
        if ($request->session()->get('auth_via_sso', false)) {
            return $next($request);
        }

        $currentRoute = $request->route()?->getName();

        if ($currentRoute !== null && in_array($currentRoute, self::EXCLUDED_ROUTES, true)) {
            return $next($request);
        }

        return redirect()->route('force-change-password');
    }
}
