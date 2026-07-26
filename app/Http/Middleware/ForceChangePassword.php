<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that forces users to change their password before accessing any other page.
 *
 * Users with `must_change_password = true` (e.g., Siswa on first login)
 * are redirected to the force-change-password page.
 *
 * Excluded routes: logout, force-change-password (to prevent redirect loop).
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

        $currentRoute = $request->route()?->getName();

        if ($currentRoute !== null && in_array($currentRoute, self::EXCLUDED_ROUTES, true)) {
            return $next($request);
        }

        return redirect()->route('force-change-password');
    }
}
