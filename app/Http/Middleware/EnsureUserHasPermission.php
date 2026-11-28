<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memverifikasi bahwa pengguna memiliki izin (permission) spesifik.
 *
 * Menggunakan paket Spatie Permission (metode hasAnyPermission()).
 * Berfungsi untuk kontrol akses yang lebih granular (misalnya: absensi.view, penilaian.update)
 * di luar batasan role umum.
 *
 * Contoh penggunaan pada route:
 *   ->middleware('permission:absensi.view')
 *   ->middleware('permission:penilaian.create|penilaian.update')
 *
 * Mengembalikan HTTP 403 Forbidden jika pengguna tidak memiliki izin yang diminta.
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
