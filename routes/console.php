<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| JADWAL TUGAS OTOMATIS (LARAVEL SCHEDULER)
|--------------------------------------------------------------------------
|
| Bagian ini mendefinisikan seluruh proses otomatis di latar belakang:
|
| 1. Synchronize SiPintu (Pukul 02:00 WIB)
|    - Menyinkronkan master data siswa & guru riil dari SiPintu Gateway harian.
|
| 2. Penandaan Status Alfa Otomatis (Pukul 00:10 WIB)
|    - Menandai siswa pada penempatan PKL aktif sebagai 'alpha' jika pada hari
|      kemarin tidak melakukan check-in dan tidak ada pengajuan izin/sakit.
|
| 3. Pengingat & Keterlambatan WhatsApp (Setiap 1 Menit)
|    - Memindai penempatan PKL aktif untuk mengirimkan peringatan H-5 menit
|      sebelum jam masuk dan notifikasi keterlambatan via WhatsApp Gateway.
|    - Menggunakan withoutOverlapping() agar tidak terjadi pengiriman ganda.
|
*/
Schedule::command('sipintu:sync')->dailyAt('02:00');
Schedule::command('absensi:mark-alfa')->dailyAt('00:10');
Schedule::command('whatsapp:attendance-reminders')->everyMinute()->withoutOverlapping();
