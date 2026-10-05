<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memverifikasi bahwa pengguna yang login memiliki salah satu role yang diizinkan.
 *
 * Menggunakan paket Spatie Permission (metode hasAnyRole()).
 * 4 Role Utama SIMONGAN:
 * 1. Super Admin : Akses penuh ke seluruh fitur dan master data.
 * 2. Guru        : Akses monitoring siswa bimbingan dan cetak Rapor PKL.
 * 3. DUDI        : Akses pembimbing industri, persetujuan izin, dan penilaian.
 * 4. Siswa       : Akses absensi selfie GPS, jurnal harian, dan melihat nilai.
 *
 * Contoh penggunaan pada route:
 *   ->middleware('role:Super Admin')
 *   ->middleware('role:Guru|DUDI')
 *
 * Mengembalikan HTTP 403 Forbidden jika pengguna tidak memiliki role yang sesuai.
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
