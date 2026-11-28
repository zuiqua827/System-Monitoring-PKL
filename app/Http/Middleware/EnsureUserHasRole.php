<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify the authenticated user has one of the required roles.
 *
 * Usage in routes:
 *   ->middleware('role:Super Admin')
 *   ->middleware('role:Super Admin|Guru')
 *
 * Uses Spatie Permission's `hasRole()` method internally.
 * Returns 403 if the user does not have any of the specified roles.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $roles  Pipe-separated list of role names
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Unauthorized.');
        }

        /** @var \App\Models\User $user */
        if (! $user->hasAnyRole($roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
