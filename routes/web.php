<?php

use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Route structure:
| - Guest: welcome page
| - Authenticated: role-based dashboard redirect
| - Super Admin: /admin/*
| - Guru: /guru/*
| - DUDI: /dudi/*
| - Siswa: /siswa/*
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Generic Dashboard (fallback redirect)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = auth()->user();

    $redirectUrl = \App\Helpers\RoleRedirectHelper::getDashboardUrl($user);

    return redirect($redirectUrl);
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:Super Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Master Jurusan
    Route::resource('jurusan', JurusanController::class);
    Route::post('jurusan/{jurusan}/restore', [JurusanController::class, 'restore'])->name('jurusan.restore')->withTrashed();
    Route::delete('jurusan/{jurusan}/force-delete', [JurusanController::class, 'forceDelete'])->name('jurusan.force-delete')->withTrashed();

    // Master Kelas
    Route::resource('kelas', KelasController::class);
    Route::post('kelas/{kela}/restore', [KelasController::class, 'restore'])->name('kelas.restore')->withTrashed();
    Route::delete('kelas/{kela}/force-delete', [KelasController::class, 'forceDelete'])->name('kelas.force-delete')->withTrashed();
});

/*
|--------------------------------------------------------------------------
| Guru Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:Guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| DUDI Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:DUDI'])->prefix('dudi')->name('dudi.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Siswa Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:Siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Profile Routes (all authenticated users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
