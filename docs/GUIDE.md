# PROJECT GUIDE
# Sistem Monitoring PKL SMK

---

# DESKRIPSI PROJECT

Project ini merupakan website Sistem Monitoring Praktik Kerja Lapangan (PKL) berbasis Laravel 13.

Tujuan sistem adalah memonitor seluruh aktivitas PKL siswa secara real-time mulai dari penempatan, absensi, jurnal harian, monitoring guru, validasi DUDI, penilaian hingga laporan.

Project harus dikembangkan menggunakan Laravel Best Practice.

JANGAN mengubah struktur database tanpa alasan yang jelas.

JANGAN membuat fitur yang bertentangan dengan dokumen ini.

---

# TUJUAN PROJECT

Membuat sistem monitoring PKL yang memiliki fitur:

- Multi Role Login
- Monitoring PKL
- Absensi GPS
- Upload Selfie
- Jurnal Harian
- Validasi DUDI
- Monitoring Guru
- Penilaian
- Laporan PKL
- Dashboard Statistik
- Notifikasi
- Audit Log
- Setting Sistem

---

# TECH STACK

Framework
- Laravel 13

PHP
- 8.4+

Database
- MySQL

Authentication
- Laravel Breeze

Authorization
- Spatie Laravel Permission

Template Engine
- Blade

CSS
- TailwindCSS

Javascript
- AlpineJS

Development
- Laragon

---

# ROLE SISTEM

Project menggunakan Spatie Laravel Permission.

Role yang digunakan:

1. Super Admin

2. Guru

3. DUDI

4. Siswa

TIDAK BOLEH membuat role baru tanpa alasan.

Semua hak akses menggunakan Permission Spatie.

---

# SISTEM LOGIN

## Super Admin

Login menggunakan

Email
Password

Role otomatis Super Admin.

---

## Guru

Login menggunakan

Email
Password

Guru dibuat oleh Super Admin.

Role Guru diberikan oleh Super Admin.

---

## DUDI

Login menggunakan

Email
Password

DUDI dibuat oleh Super Admin.

Role DUDI diberikan oleh Super Admin.

---

## Siswa

Login menggunakan

Username = NIS

Password pertama = Tanggal Lahir

Format

YYYY-MM-DD

Setelah login pertama

WAJIB mengganti password.

Menggunakan Force Change Password.

---

# STRUKTUR DATABASE

Database utama terdiri dari:

users

roles

permissions

jurusan

kelas

guru

dudi

siswa

periode_pkl

penempatan_pkl

absensi

aktivitas

komentar

laporan

penilaian

notifikasi

audit_logs

settings

---

# RELASI DATABASE

Jurusan

↓

Kelas

↓

Siswa

↓

Penempatan PKL

↓

Absensi

↓

Aktivitas

↓

Komentar

↓

Laporan

↓

Penilaian

Guru

↓

Penempatan PKL

DUDI

↓

Penempatan PKL

Periode PKL

↓

Penempatan PKL

User

↓

Guru

↓

DUDI

↓

Siswa

↓

Notifikasi

↓

Audit Log

---

# STATUS PROJECT

## Sudah Selesai

✔ Migration

✔ Foreign Key

✔ Seeder

✔ Role Seeder

✔ Permission Seeder

✔ Super Admin Seeder

✔ Spatie Permission

✔ Factory

✔ Model

✔ Soft Delete

✔ Fillable

✔ Casts

✔ Relationship Model

✔ Force Change Password

✔ Multi Role Login

✔ Middleware Role

✔ Middleware Permission

---

# BELUM DIKERJAKAN

Dashboard

CRUD Jurusan

CRUD Kelas

CRUD Guru

CRUD DUDI

CRUD Siswa

CRUD Periode PKL

CRUD Penempatan PKL

Dashboard Super Admin

Dashboard Guru

Dashboard DUDI

Dashboard Siswa

Absensi GPS

Selfie

Jurnal Harian

Monitoring Guru

Monitoring DUDI

Penilaian

Laporan

Export PDF

Export Excel

Notification

Activity Log UI

Setting UI

---

# STANDAR MODEL

Semua Model WAJIB memiliki

HasFactory

SoftDeletes

fillable

casts

Relationship

Factory

Tidak boleh menggunakan query langsung di Controller.

---

# STANDAR CODING

Gunakan

Service Layer

Repository Pattern

Form Request Validation

Policy

Middleware

Spatie Permission

Laravel Best Practice

Controller hanya sebagai penghubung.

Business Logic berada pada Service.

---

# TEMA UI

Tema hanya sebagai referensi.

Tidak harus sama persis.

Konsep yang digunakan

Modern

Minimalis

Clean

Dominan Putih

Dominan Biru

Card Dashboard

Sidebar kiri

Navbar atas

Responsive

Simple

Professional

Seluruh halaman boleh didesain ulang selama tetap mengikuti kebutuhan Sistem Monitoring PKL.

---

# FLOW SISTEM

Super Admin

↓

Mengelola

Guru

DUDI

Siswa

Jurusan

Kelas

Periode PKL

Penempatan PKL

↓

Guru melakukan Monitoring

↓

Siswa melakukan Absensi

↓

Siswa membuat Aktivitas Harian

↓

DUDI melakukan Validasi

↓

Guru memberikan Penilaian

↓

Sistem membuat Laporan

---

# ATURAN PENGEMBANGAN

AI Agent WAJIB:

1. Membaca file ini terlebih dahulu sebelum membuat kode.

2. Tidak mengubah struktur database tanpa alasan.

3. Tidak menghapus relasi yang sudah ada.

4. Mengikuti Laravel Best Practice.

5. Menggunakan Spatie Permission.

6. Menggunakan Service Layer.

7. Menggunakan Repository Pattern.

8. Menggunakan Form Request.

9. Menggunakan Middleware.

10. Tidak membuat duplicate model.

11. Tidak membuat duplicate migration.

12. Tidak mengubah nama tabel.

13. Tidak mengubah foreign key.

14. Tidak mengubah nama role.

15. Tidak mengubah permission tanpa alasan.

16. Tidak mengubah login flow.

17. Selalu menjaga kompatibilitas dengan kode sebelumnya.

18. Setiap fitur baru harus mengikuti struktur database yang sudah ada.

19. Jangan membuat kode yang menyebabkan bug pada fitur yang telah selesai.

20. Setelah menyelesaikan suatu fitur, lakukan pengecekan relasi, route, middleware, dan permission sebelum dianggap selesai.

---

# PRIORITAS SPRINT SELANJUTNYA

Sprint 3

1.
CRUD Jurusan

2.
CRUD Kelas

3.
CRUD Guru

4.
CRUD DUDI

5.
CRUD Siswa

6.
CRUD Periode PKL

7.
CRUD Penempatan PKL

Setelah seluruh CRUD selesai

baru lanjut

Sprint 4

Dashboard

Absensi

Aktivitas

Monitoring

Penilaian

Laporan

Export

Notification

---

# CATATAN

Project ini akan dikembangkan secara bertahap.

Seluruh AI Agent harus menjaga konsistensi arsitektur.

Jika terdapat keraguan, jangan mengubah implementasi yang sudah ada. Lebih baik melanjutkan struktur yang telah dibuat daripada membuat pendekatan baru yang berpotensi menimbulkan bug.    