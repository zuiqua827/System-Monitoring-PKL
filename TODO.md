# TODO — Pisahkan halaman login Super Admin dari login user lain

## Step (UI/Navigasi only)

- [x] Hapus blok "Administrator? Login Khusus Admin" dari `resources/views/auth/login.blade.php`
- [x] Hapus blok "Bukan Administrator? Login Siswa / Guru / Industri" dari `resources/views/admin/login.blade.php`
- [x] Jalankan `php artisan optimize:clear`
- [x] Jalankan `php artisan view:cache`
- [x] Validasi route `/login` dan `/admin/login` tetap terdaftar
- [x] Validasi GET `/login` tidak menampilkan akses Admin
- [x] Validasi GET `/admin/login` tidak menampilkan akses user lain
