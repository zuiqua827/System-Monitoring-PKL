<?php

use App\Http\Controllers\Admin\AbsensiController as AdminAbsensiController;
use App\Http\Controllers\Admin\AktivitasController as AdminAktivitasController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DudiController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\PenempatanPKLController;
use App\Http\Controllers\Admin\PenilaianController as AdminPenilaianController;
use App\Http\Controllers\Admin\PeriodePKLController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Guru\AbsensiController as GuruAbsensiController;
use App\Http\Controllers\Guru\AktivitasController as GuruAktivitasController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\PenilaianController as GuruPenilaianController;
use App\Http\Controllers\Dudi\DashboardController as DudiDashboardController;
use App\Http\Controllers\Siswa\AbsensiController as SiswaAbsensiController;
use App\Http\Controllers\Siswa\AktivitasController as SiswaAktivitasController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\PenilaianController as SiswaPenilaianController;
use App\Http\Controllers\AccountController;
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
    return redirect('/login');
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
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

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
    Route::get('penempatan-pkl/students', [PenempatanPKLController::class, 'searchStudents'])->name('penempatan-pkl.students');
    Route::resource('penempatan-pkl', PenempatanPKLController::class);
    Route::post('penempatan-pkl/{penempatan_pkl}/restore', [PenempatanPKLController::class, 'restore'])->name('penempatan-pkl.restore')->withTrashed();
    Route::delete('penempatan-pkl/{penempatan_pkl}/force-delete', [PenempatanPKLController::class, 'forceDelete'])->name('penempatan-pkl.force-delete')->withTrashed();

    // Master Periode PKL
    Route::resource('periode-pkl', PeriodePKLController::class);
    Route::post('periode-pkl/{periode_pkl}/restore', [PeriodePKLController::class, 'restore'])->name('periode-pkl.restore')->withTrashed();
    Route::delete('periode-pkl/{periode_pkl}/force-delete', [PeriodePKLController::class, 'forceDelete'])->name('periode-pkl.force-delete')->withTrashed();

// Sinkronisasi SiPintu
    Route::get('/sipintu-sync', [\App\Http\Controllers\Admin\SipintuSyncController::class, 'index'])->name('sipintu-sync.index');
    Route::get('/sipintu-sync/preview', [\App\Http\Controllers\Admin\SipintuSyncController::class, 'preview'])->name('sipintu-sync.preview');
    Route::post('/sipintu-sync', [\App\Http\Controllers\Admin\SipintuSyncController::class, 'sync'])->name('sipintu-sync.sync');

    // Pemetaan Kelas SiPintu
    Route::get('/sipintu-classroom-mapping', [\App\Http\Controllers\Admin\SipintuClassroomMappingController::class, 'index'])->name('sipintu-classroom-mapping.index');
    Route::post('/sipintu-classroom-mapping', [\App\Http\Controllers\Admin\SipintuClassroomMappingController::class, 'store'])->name('sipintu-classroom-mapping.store');
    Route::post('/sipintu-classroom-mapping/apply', [\App\Http\Controllers\Admin\SipintuClassroomMappingController::class, 'apply'])->name('sipintu-classroom-mapping.apply');

    // Master Absensi
    Route::resource('absensi', AdminAbsensiController::class);
    Route::post('absensi/{absensi}/restore', [AdminAbsensiController::class, 'restore'])->name('absensi.restore')->withTrashed();
    Route::delete('absensi/{absensi}/force-delete', [AdminAbsensiController::class, 'forceDelete'])->name('absensi.force-delete')->withTrashed();

    // Master Aktivitas Harian
    Route::resource('aktivitas', AdminAktivitasController::class);
    Route::post('aktivitas/{aktivitas}/restore', [AdminAktivitasController::class, 'restore'])->name('aktivitas.restore')->withTrashed();
    Route::delete('aktivitas/{aktivitas}/force-delete', [AdminAktivitasController::class, 'forceDelete'])->name('aktivitas.force-delete')->withTrashed();

    // Master Penilaian PKL
    Route::resource('penilaian', AdminPenilaianController::class);
    Route::post('penilaian/{penilaian}/restore', [AdminPenilaianController::class, 'restore'])->name('penilaian.restore')->withTrashed();
    Route::delete('penilaian/{penilaian}/force-delete', [AdminPenilaianController::class, 'forceDelete'])->name('penilaian.force-delete')->withTrashed();

    // Pusat Laporan
    Route::get('/laporan', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/siswa', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'siswa'])->name('laporan.siswa');
    Route::get('/laporan/siswa/export/excel', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'exportSiswaExcel'])->name('laporan.siswa.export.excel');
    Route::get('/laporan/siswa/export/pdf', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'exportSiswaPdf'])->name('laporan.siswa.export.pdf');
    Route::get('/laporan/absensi', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'absensi'])->name('laporan.absensi');
    Route::get('/laporan/absensi/export/excel', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'exportAbsensiExcel'])->name('laporan.absensi.export.excel');
    Route::get('/laporan/aktivitas', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'aktivitas'])->name('laporan.aktivitas');
    Route::get('/laporan/penilaian', [\App\Http\Controllers\Admin\Laporan\LaporanController::class, 'penilaian'])->name('laporan.penilaian');
});

/*
|--------------------------------------------------------------------------
| Guru Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:Guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');

    // Absensi Siswa Bimbingan
    Route::get('/absensi', [GuruAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/{id}', [GuruAbsensiController::class, 'show'])->name('absensi.show');
    Route::post('/absensi/{id}/verify', [GuruAbsensiController::class, 'verify'])->name('absensi.verify');

    // Aktivitas Siswa Bimbingan
    Route::get('/aktivitas', [GuruAktivitasController::class, 'index'])->name('aktivitas.index');
    Route::get('/aktivitas/{id}', [GuruAktivitasController::class, 'show'])->name('aktivitas.show');

    // Penilaian Siswa Bimbingan
    Route::get('/penilaian', [GuruPenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/{id}', [GuruPenilaianController::class, 'show'])->name('penilaian.show');

    // Pusat Laporan
    Route::get('/laporan', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/siswa', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'siswa'])->name('laporan.siswa');
    Route::get('/laporan/siswa/export/excel', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'exportSiswaExcel'])->name('laporan.siswa.export.excel');
    Route::get('/laporan/siswa/export/pdf', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'exportSiswaPdf'])->name('laporan.siswa.export.pdf');
    Route::get('/laporan/absensi', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'absensi'])->name('laporan.absensi');
    Route::get('/laporan/absensi/export/excel', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'exportAbsensiExcel'])->name('laporan.absensi.export.excel');
    Route::get('/laporan/aktivitas', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'aktivitas'])->name('laporan.aktivitas');
    Route::get('/laporan/penilaian', [\App\Http\Controllers\Guru\Laporan\LaporanController::class, 'penilaian'])->name('laporan.penilaian');
});

/*
|--------------------------------------------------------------------------
| DUDI Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:DUDI'])->prefix('dudi')->name('dudi.')->group(function () {
    Route::get('/dashboard', [DudiDashboardController::class, 'index'])->name('dashboard');
    Route::get('/siswa', [\App\Http\Controllers\Dudi\SiswaController::class, 'index'])->name('siswa.index');
    Route::get('/siswa/{id}', [\App\Http\Controllers\Dudi\SiswaController::class, 'show'])->name('siswa.show');
    Route::get('/absensi', [\App\Http\Controllers\Dudi\AbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/{id}', [\App\Http\Controllers\Dudi\AbsensiController::class, 'show'])->name('absensi.show');
    Route::get('/aktivitas', [\App\Http\Controllers\Dudi\AktivitasController::class, 'index'])->name('aktivitas.index');
    Route::get('/aktivitas/{id}', [\App\Http\Controllers\Dudi\AktivitasController::class, 'show'])->name('aktivitas.show');
    Route::put('/aktivitas/{id}', [\App\Http\Controllers\Dudi\AktivitasController::class, 'update'])->name('aktivitas.update');
    
    // Pengajuan Ketidakhadiran Siswa
    Route::get('/ketidakhadiran', [\App\Http\Controllers\Dudi\PengajuanKetidakhadiranController::class, 'index'])->name('ketidakhadiran.index');
    Route::get('/ketidakhadiran/{id}', [\App\Http\Controllers\Dudi\PengajuanKetidakhadiranController::class, 'show'])->name('ketidakhadiran.show');
    Route::post('/ketidakhadiran/{id}/process', [\App\Http\Controllers\Dudi\PengajuanKetidakhadiranController::class, 'process'])->name('ketidakhadiran.process');

    Route::get('/penilaian', [\App\Http\Controllers\Dudi\PenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/create', [\App\Http\Controllers\Dudi\PenilaianController::class, 'create'])->name('penilaian.create');
    Route::post('/penilaian', [\App\Http\Controllers\Dudi\PenilaianController::class, 'store'])->name('penilaian.store');
    Route::get('/penilaian/{id}', [\App\Http\Controllers\Dudi\PenilaianController::class, 'show'])->name('penilaian.show');
    Route::get('/penilaian/{id}/edit', [\App\Http\Controllers\Dudi\PenilaianController::class, 'edit'])->name('penilaian.edit');
    Route::put('/penilaian/{id}', [\App\Http\Controllers\Dudi\PenilaianController::class, 'update'])->name('penilaian.update');
    Route::post('/penilaian/{id}/finalize', [\App\Http\Controllers\Dudi\PenilaianController::class, 'finalize'])->name('penilaian.finalize');

    // Pusat Laporan
    Route::get('/laporan', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/siswa', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'siswa'])->name('laporan.siswa');
    Route::get('/laporan/siswa/export/excel', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'exportSiswaExcel'])->name('laporan.siswa.export.excel');
    Route::get('/laporan/siswa/export/pdf', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'exportSiswaPdf'])->name('laporan.siswa.export.pdf');
    Route::get('/laporan/absensi', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'absensi'])->name('laporan.absensi');
    Route::get('/laporan/absensi/export/excel', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'exportAbsensiExcel'])->name('laporan.absensi.export.excel');
    Route::get('/laporan/aktivitas', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'aktivitas'])->name('laporan.aktivitas');
    Route::get('/laporan/penilaian', [\App\Http\Controllers\Dudi\Laporan\LaporanController::class, 'penilaian'])->name('laporan.penilaian');
});

/*
|--------------------------------------------------------------------------
| Siswa Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:Siswa'])->prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');

    // Absensi Siswa (Check In, Check Out, Riwayat)
    Route::get('/absensi', [SiswaAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/{id}', [SiswaAbsensiController::class, 'show'])->name('absensi.show');
    Route::post('/absensi/check-in', [SiswaAbsensiController::class, 'checkIn'])->name('absensi.check-in');
    Route::post('/absensi/check-out', [SiswaAbsensiController::class, 'checkOut'])->name('absensi.check-out');

    // Aktivitas Siswa
    Route::resource('/aktivitas', SiswaAktivitasController::class)->names('aktivitas');
    Route::post('/aktivitas/{id}/submit', [SiswaAktivitasController::class, 'submit'])->name('aktivitas.submit');

    // Pengajuan Ketidakhadiran
    Route::get('/ketidakhadiran', [\App\Http\Controllers\Siswa\PengajuanKetidakhadiranController::class, 'index'])->name('ketidakhadiran.index');
    Route::post('/ketidakhadiran', [\App\Http\Controllers\Siswa\PengajuanKetidakhadiranController::class, 'store'])->name('ketidakhadiran.store');

    // Penilaian Siswa
    Route::get('/penilaian', [SiswaPenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/{penilaian}/pdf', [SiswaPenilaianController::class, 'downloadPdf'])->name('penilaian.pdf');
    Route::get('/penilaian/{id}', [SiswaPenilaianController::class, 'show'])->name('penilaian.show');
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

    // Account Settings (all roles)
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::patch('/account', [AccountController::class, 'updateInfo'])->name('account.update-info');
    Route::post('/account/avatar', [AccountController::class, 'uploadAvatar'])->name('account.upload-avatar');
    Route::delete('/account/avatar', [AccountController::class, 'deleteAvatar'])->name('account.delete-avatar');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.update-password');
});

require __DIR__.'/auth.php';
