<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniqueValidationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin_test@example.com',
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole(UserRole::SUPER_ADMIN->value);
    }

    /**
     * TEST 1: Buat data dengan nama "PKL Tahun Ajaran 2026/2027" -> Hasil: berhasil.
     */
    public function test_1_create_periode_pkl_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.periode-pkl.store'), [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response->assertRedirect(route('admin.periode-pkl.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('periode_pkl', [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'deleted_at' => null,
        ]);
    }

    /**
     * TEST 2: Buat data kedua dengan nama yang sama ketika data pertama masih aktif -> Hasil: ditolak dengan validation error duplicate.
     */
    public function test_2_create_duplicate_active_periode_pkl_fails_validation(): void
    {
        // Setup initial active record
        PeriodePKL::create([
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        // Attempt to create duplicate active record
        $response = $this->actingAs($this->admin)->post(route('admin.periode-pkl.store'), [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response->assertSessionHasErrors(['nama']);
        $this->assertEquals(1, PeriodePKL::where('nama', 'PKL Tahun Ajaran 2026/2027')->count());
    }

    /**
     * TEST 3: Soft delete data pertama. Buat data baru dengan nama yang sama -> Hasil: berhasil sesuai aturan bisnis.
     */
    public function test_3_create_periode_pkl_after_soft_delete_succeeds(): void
    {
        $periode = PeriodePKL::create([
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        // Soft delete the first record
        $periode->delete();
        $this->assertSoftDeleted('periode_pkl', ['id' => $periode->id]);

        // Create new record with same name
        $response = $this->actingAs($this->admin)->post(route('admin.periode-pkl.store'), [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.periode-pkl.index'));

        // Should now exist as an active record
        $this->assertDatabaseHas('periode_pkl', [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'deleted_at' => null,
        ]);
    }

    /**
     * TEST 4: Force delete data pertama. Buat data baru dengan nama yang sama -> Hasil: WAJIB berhasil.
     */
    public function test_4_create_periode_pkl_after_force_delete_must_succeed(): void
    {
        $periode = PeriodePKL::create([
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        // Soft delete then force delete via endpoint or model
        $response = $this->actingAs($this->admin)->delete(route('admin.periode-pkl.force-delete', $periode->id));
        $response->assertRedirect(route('admin.periode-pkl.index'));

        // Verify record is physically gone
        $this->assertDatabaseMissing('periode_pkl', ['id' => $periode->id]);

        // Attempt creating again with exact same name - MUST SUCCEED
        $createResponse = $this->actingAs($this->admin)->post(route('admin.periode-pkl.store'), [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $createResponse->assertSessionHasNoErrors();
        $createResponse->assertRedirect(route('admin.periode-pkl.index'));

        $this->assertDatabaseHas('periode_pkl', [
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'deleted_at' => null,
        ]);
    }

    /**
     * TEST 5: Pastikan force deleted record benar-benar tidak ditemukan oleh query normal maupun validasi duplicate.
     */
    public function test_5_force_deleted_record_not_found_by_any_query_or_validation(): void
    {
        $periode = PeriodePKL::create([
            'nama' => 'PKL 2026 Permanen',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $periode->forceDelete();

        // Query normal
        $this->assertNull(PeriodePKL::where('nama', 'PKL 2026 Permanen')->first());

        // Query withTrashed
        $this->assertNull(PeriodePKL::withTrashed()->where('nama', 'PKL 2026 Permanen')->first());

        // Count in table
        $this->assertEquals(0, PeriodePKL::withTrashed()->where('nama', 'PKL 2026 Permanen')->count());
    }

    /**
     * TEST 6: UPDATE record sendiri dengan nama yang sama -> Hasil: Berhasil (self-ignored).
     */
    public function test_6_update_own_record_with_same_name_succeeds(): void
    {
        $periode = PeriodePKL::create([
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.periode-pkl.update', $periode->id), [
            'nama' => 'PKL Tahun Ajaran 2026/2027', // Same name
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-15', // Changed date
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.periode-pkl.index'));

        $this->assertDatabaseHas('periode_pkl', [
            'id' => $periode->id,
            'nama' => 'PKL Tahun Ajaran 2026/2027',
            'tanggal_mulai' => '2026-07-15 00:00:00',
        ]);
    }

    /**
     * TEST 7: UPDATE record dengan nama record aktif lain -> Hasil: ditolak validation error duplicate.
     */
    public function test_7_update_with_another_active_record_name_fails(): void
    {
        $periode1 = PeriodePKL::create([
            'nama' => 'Periode Semester 1',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $periode2 = PeriodePKL::create([
            'nama' => 'Periode Semester 2',
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.periode-pkl.update', $periode2->id), [
            'nama' => 'Periode Semester 1', // Already taken by active periode1
            'tahun_ajaran' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'Persiapan',
        ]);

        $response->assertSessionHasErrors(['nama']);
    }

    /**
     * TEST 8: Jurusan lifecycle (Active -> Soft Delete -> Force Delete).
     */
    public function test_8_jurusan_uniqueness_lifecycle(): void
    {
        // 1. Create Jurusan
        $response = $this->actingAs($this->admin)->post(route('admin.jurusan.store'), [
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
            'deskripsi' => 'Jurusan RPL',
        ]);
        $response->assertRedirect(route('admin.jurusan.index'));

        // 2. Active duplicate fails
        $duplicateResponse = $this->actingAs($this->admin)->post(route('admin.jurusan.store'), [
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);
        $duplicateResponse->assertSessionHasErrors(['kode', 'nama']);

        // 3. Soft delete and re-create
        $jurusan = Jurusan::where('kode', 'RPL')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.jurusan.destroy', $jurusan->id));
        $this->assertSoftDeleted('jurusan', ['id' => $jurusan->id]);

        $recreateSoft = $this->actingAs($this->admin)->post(route('admin.jurusan.store'), [
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
            'deskripsi' => 'Jurusan RPL Baru',
        ]);
        $recreateSoft->assertSessionHasNoErrors();
        $this->assertDatabaseHas('jurusan', ['kode' => 'RPL', 'deleted_at' => null]);

        // 4. Force delete and re-create
        $jurusanActive = Jurusan::where('kode', 'RPL')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.jurusan.force-delete', $jurusanActive->id));
        $this->assertDatabaseMissing('jurusan', ['id' => $jurusanActive->id]);

        $recreateForce = $this->actingAs($this->admin)->post(route('admin.jurusan.store'), [
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
            'deskripsi' => 'Jurusan RPL Setelah Force Delete',
        ]);
        $recreateForce->assertSessionHasNoErrors();
        $this->assertDatabaseHas('jurusan', ['kode' => 'RPL', 'deleted_at' => null]);
    }

    /**
     * TEST 9: Kelas lifecycle (Active -> Soft Delete -> Force Delete).
     */
    public function test_9_kelas_uniqueness_lifecycle(): void
    {
        $jurusan = Jurusan::create(['kode' => 'TKJ', 'nama' => 'Teknik Komputer']);

        // 1. Create Kelas
        $response = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'XII TKJ 1',
            'tingkat' => 12,
            'tahun_ajaran' => '2026/2027',
        ]);
        $response->assertRedirect(route('admin.kelas.index'));
        $this->assertDatabaseHas('kelas', ['nama' => 'XII TKJ 1', 'deleted_at' => null]);

        // 2. Active duplicate fails
        $dup = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'XII TKJ 1',
            'tingkat' => 12,
            'tahun_ajaran' => '2026/2027',
        ]);
        $dup->assertSessionHasErrors(['nama']);

        // 3. Soft delete and re-create
        $kelas = Kelas::where('nama', 'XII TKJ 1')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.kelas.destroy', $kelas->id));
        $this->assertSoftDeleted('kelas', ['id' => $kelas->id]);

        $recreateSoft = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'XII TKJ 1',
            'tingkat' => 12,
            'tahun_ajaran' => '2026/2027',
        ]);
        $recreateSoft->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kelas', ['nama' => 'XII TKJ 1', 'deleted_at' => null]);

        // 4. Force delete and re-create
        $kelasActive = Kelas::where('nama', 'XII TKJ 1')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('admin.kelas.force-delete', $kelasActive->id));
        $this->assertDatabaseMissing('kelas', ['id' => $kelasActive->id]);

        $recreateForce = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'XII TKJ 1',
            'tingkat' => 12,
            'tahun_ajaran' => '2026/2027',
        ]);
        $recreateForce->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kelas', ['nama' => 'XII TKJ 1', 'deleted_at' => null]);
    }
}
