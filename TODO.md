# TODO — Set Password Awal Akun Siswa, Guru, DUDI

## Tujuan

Mengatur password awal = Hash::make('password') untuk seluruh akun Siswa, Guru, DUDI.

- must_change_password = true (dipaksa ganti saat login pertama)
- Super Admin HARUS dikecualikan (password TIDAK boleh berubah)
- Tidak mengubah data lain, role, permission, migration.

## Status

- [x] **Audit read-only** — menjalankan `_audit_password_targets.php`
    - Siswa = 2308, Guru = 71, DUDI = 1, Super Admin = 1
    - Total unik user relasi = 2380 (eksklusi Super Admin → tidak ada tumpang tindih)
- [x] **Plan disetujui user** (must_change_password = true)
- [x] **Eksekusi** — `_set_initial_passwords.php` (DB::transaction, berjalan di terminal, menunggu selesai)
- [ ] **Verifikasi** — `_verify_initial_passwords.php` (menunggu eksekusi selesai, lalu re-run)
    - Contoh: Siswa = true, Guru = true, DUDI = true
    - Super Admin tidak berubah
- [ ] **Data BEFORE/AFTER** konsisten
- [ ] **Laporan akhir**

## Catatan

- Format script mengikuti `_migrate_student_passwords.php` + `SiswaService::migrateStudentPasswords()`.
- Password disimpan sebagai `Hash::make('password')`, bukan plaintext.
- ID user tidak di-hardcode; target dihitung via relasi model.
- Menggunakan transaksi database (rollback otomatis jika exception).
- Script tambahan idempoten: `_set_initial_passwords2.php` (jika perlu re-run).
- Diagnostik DUDI: `_diag_dudi.php`.
