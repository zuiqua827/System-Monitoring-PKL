<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify the authenticated user has the required permission.
 *
 * Usage in routes:
 *   ->middleware('permission:user.view')
 *   ->middleware('permission:user.view|user.create')
 *
 * Uses Spatie Permission's `hasAnyPermission()` method internally.
 * Returns 403 if the user does not have any of the specified permissions.
 */
class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $permissions  Pipe-separated list of permission names
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403, 'Unauthorized.');
        }

        /** @var \App\Models\User $user */
        if (! $user->hasAnyPermission($permissions)) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan tindakan ini.');
        }

        return $next($request);
    }
}
