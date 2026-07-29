<?php

use App\Http\Controllers\Admin\AbsensiController as AdminAbsensiController;
use App\Http\Controllers\Admin\DudiController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\PenempatanPKLController;
use App\Http\Controllers\Admin\PeriodePKLController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Guru\AbsensiController as GuruAbsensiController;
use App\Http\Controllers\Siswa\AbsensiController as SiswaAbsensiController;
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

    // Master DUDI
    Route::resource('dudi', DudiController::class);
    Route::post('dudi/{dudi}/restore', [DudiController::class, 'restore'])->name('dudi.restore')->withTrashed();
    Route::delete('dudi/{dudi}/force-delete', [DudiController::class, 'forceDelete'])->name('dudi.force-delete')->withTrashed();

    // Master Guru
    Route::resource('guru', GuruController::class);
    Route::post('guru/{guru}/restore', [GuruController::class, 'restore'])->name('guru.restore')->withTrashed();
    Route::delete('guru/{guru}/force-delete', [GuruController::class, 'forceDelete'])->name('guru.force-delete')->withTrashed();

    // Master Siswa
    Route::resource('siswa', SiswaController::class);
    Route::post('siswa/{siswa}/restore', [SiswaController::class, 'restore'])->name('siswa.restore')->withTrashed();
    Route::delete('siswa/{siswa}/force-delete', [SiswaController::class, 'forceDelete'])->name('siswa.force-delete')->withTrashed();

    // Master Penempatan PKL
    Route::resource('penempatan-pkl', PenempatanPKLController::class);
    Route::post('penempatan-pkl/{penempatan_pkl}/restore', [PenempatanPKLController::class, 'restore'])->name('penempatan-pkl.restore')->withTrashed();
    Route::delete('penempatan-pkl/{penempatan_pkl}/force-delete', [PenempatanPKLController::class, 'forceDelete'])->name('penempatan-pkl.force-delete')->withTrashed();

    // Master Periode PKL
    Route::resource('periode-pkl', PeriodePKLController::class);
    Route::post('periode-pkl/{periode_pkl}/restore', [PeriodePKLController::class, 'restore'])->name('periode-pkl.restore')->withTrashed();
    Route::delete('periode-pkl/{periode_pkl}/force-delete', [PeriodePKLController::class, 'forceDelete'])->name('periode-pkl.force-delete')->withTrashed();

    // Master Absensi
    Route::resource('absensi', AdminAbsensiController::class);
    Route::post('absensi/{absensi}/restore', [AdminAbsensiController::class, 'restore'])->name('absensi.restore')->withTrashed();
    Route::delete('absensi/{absensi}/force-delete', [AdminAbsensiController::class, 'forceDelete'])->name('absensi.force-delete')->withTrashed();
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

    // Absensi Siswa Bimbingan
    Route::get('/absensi', [GuruAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/{id}', [GuruAbsensiController::class, 'show'])->name('absensi.show');
    Route::post('/absensi/{id}/verify', [GuruAbsensiController::class, 'verify'])->name('absensi.verify');
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

    // Absensi Siswa (Check In, Check Out, Riwayat)
    Route::get('/absensi', [SiswaAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/{id}', [SiswaAbsensiController::class, 'show'])->name('absensi.show');
    Route::post('/absensi/check-in', [SiswaAbsensiController::class, 'checkIn'])->name('absensi.check-in');
    Route::post('/absensi/check-out', [SiswaAbsensiController::class, 'checkOut'])->name('absensi.check-out');
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
