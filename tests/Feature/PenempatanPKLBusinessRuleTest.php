<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenempatanPKLBusinessRuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $guruAUser;
    protected Guru $guruA;
    protected User $guruBUser;
    protected Guru $guruB;
    protected User $dudiUser;
    protected Dudi $dudi;
    protected PeriodePKL $periode;
    protected Kelas $kelas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // Setup Admin
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole(UserRole::SUPER_ADMIN->value);

        // Setup Guru A
        $this->guruAUser = User::factory()->create(['name' => 'Guru A']);
        $this->guruAUser->assignRole(UserRole::GURU->value);
        $this->guruA = Guru::create([
            'user_id' => $this->guruAUser->id,
            'nama' => 'Guru A',
            'nip' => '198001012005011001',
        ]);

        // Setup Guru B
        $this->guruBUser = User::factory()->create(['name' => 'Guru B']);
        $this->guruBUser->assignRole(UserRole::GURU->value);
        $this->guruB = Guru::create([
            'user_id' => $this->guruBUser->id,
            'nama' => 'Guru B',
            'nip' => '198001012005011002',
        ]);

        // Setup DUDI
        $this->dudiUser = User::factory()->create(['name' => 'DUDI Mitra']);
        $this->dudiUser->assignRole(UserRole::DUDI->value);
        $this->dudi = Dudi::create([
            'user_id' => $this->dudiUser->id,
            'nama_perusahaan' => 'DUDI Mitra Sejahtera',
            'penanggung_jawab' => 'Bpk. Mitra',
            'status_aktif' => true,
        ]);

        $this->periode = PeriodePKL::first() ?? PeriodePKL::create([
            'nama' => 'Periode 2026/2027',
            'tahun_ajaran' => '2026/2027',
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'aktif',
        ]);

        $this->kelas = Kelas::first();
    }

    /**
     * Helper to create a student without penempatan.
     */
    protected function createStudent(string $nama, string $nis): Siswa
    {
        $user = User::factory()->create([
            'name' => $nama,
            'email' => "{$nis}@student.test",
        ]);
        $user->assignRole(UserRole::SISWA->value);

        return Siswa::create([
            'user_id' => $user->id,
            'class_id' => $this->kelas->id,
            'nis' => $nis,
            'nama' => $nama,
        ]);
    }

    /**
     * Test 1 & 3: Siswa tanpa Penempatan PKL TIDAK boleh muncul pada Guru & Laporan Siswa Guru.
     */
    public function test_siswa_without_penempatan_does_not_appear_on_guru_laporan(): void
    {
        $siswa = $this->createStudent('Ahmad Unplaced', '99001');

        $response = $this->actingAs($this->guruAUser)->get(route('guru.laporan.siswa'));

        $response->assertStatus(200);
        $response->assertDontSee($siswa->nama);
        $response->assertDontSee($siswa->nis);
    }

    /**
     * Test 2: Siswa tanpa Penempatan PKL TIDAK boleh muncul pada DUDI.
     */
    public function test_siswa_without_penempatan_does_not_appear_on_dudi_list(): void
    {
        $siswa = $this->createStudent('Avrel Unplaced', '99002');

        $response = $this->actingAs($this->dudiUser)->get(route('dudi.siswa.index'));

        $response->assertStatus(200);
        $response->assertDontSee($siswa->nama);
        $response->assertDontSee($siswa->nis);
    }

    /**
     * Test 4: Siswa tanpa Penempatan PKL TETAP muncul pada Kelola Siswa (Admin).
     */
    public function test_siswa_without_penempatan_still_appears_on_admin_kelola_siswa(): void
    {
        $siswa = $this->createStudent('Budi Belum Ditempatkan', '99003');

        $response = $this->actingAs($this->adminUser)->get(route('admin.siswa.index'));

        $response->assertStatus(200);
        $response->assertSee($siswa->nama);
        $response->assertSee($siswa->nis);
    }

    /**
     * Test 5 & 6: Siswa DENGAN Penempatan PKL muncul pada Guru dan DUDI yang benar.
     */
    public function test_siswa_with_penempatan_appears_on_assigned_guru_and_dudi(): void
    {
        $siswa = $this->createStudent('Citra Placed', '99004');

        PenempatanPKL::create([
            'periode_pkl_id' => $this->periode->id,
            'guru_id' => $this->guruA->id,
            'dudi_id' => $this->dudi->id,
            'siswa_id' => $siswa->id,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
        ]);

        // Guru A must see Citra
        $responseGuru = $this->actingAs($this->guruAUser)->get(route('guru.laporan.siswa'));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee($siswa->nama);

        // DUDI must see Citra
        $responseDudi = $this->actingAs($this->dudiUser)->get(route('dudi.siswa.index'));
        $responseDudi->assertStatus(200);
        $responseDudi->assertSee($siswa->nama);
    }

    /**
     * Test 7: Siswa dengan Penempatan PKL Guru A TIDAK muncul pada Guru B.
     */
    public function test_siswa_assigned_to_guru_a_does_not_appear_on_guru_b(): void
    {
        $siswa = $this->createStudent('Dewi Placed A', '99005');

        PenempatanPKL::create([
            'periode_pkl_id' => $this->periode->id,
            'guru_id' => $this->guruA->id,
            'dudi_id' => $this->dudi->id,
            'siswa_id' => $siswa->id,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
        ]);

        // Guru B must NOT see Dewi
        $responseGuruB = $this->actingAs($this->guruBUser)->get(route('guru.laporan.siswa'));
        $responseGuruB->assertStatus(200);
        $responseGuruB->assertDontSee($siswa->nama);
    }

    /**
     * Test 8: Siswa yang belum pernah login TETAP muncul jika sudah memiliki Penempatan PKL.
     */
    public function test_siswa_who_never_logged_in_appears_if_placed(): void
    {
        $siswa = $this->createStudent('Eko Never Logged In', '99006');
        $this->assertNull($siswa->user->last_login_at);

        PenempatanPKL::create([
            'periode_pkl_id' => $this->periode->id,
            'guru_id' => $this->guruA->id,
            'dudi_id' => $this->dudi->id,
            'siswa_id' => $siswa->id,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
        ]);

        $response = $this->actingAs($this->guruAUser)->get(route('guru.laporan.siswa'));
        $response->assertStatus(200);
        $response->assertSee($siswa->nama);
    }

    /**
     * Test 9: Siswa yang sudah login TETAPI belum memiliki Penempatan PKL tetap TIDAK muncul.
     */
    public function test_siswa_logged_in_without_penempatan_does_not_appear(): void
    {
        $siswa = $this->createStudent('Fajar Has Logged In', '99007');
        $siswa->user->update(['last_login_at' => now()]);

        $responseGuru = $this->actingAs($this->guruAUser)->get(route('guru.laporan.siswa'));
        $responseGuru->assertStatus(200);
        $responseGuru->assertDontSee($siswa->nama);

        $responseDudi = $this->actingAs($this->dudiUser)->get(route('dudi.siswa.index'));
        $responseDudi->assertStatus(200);
        $responseDudi->assertDontSee($siswa->nama);
    }

    /**
     * Test 10: Penempatan dibatalkan dapat difilter dengan benar sesuai aturan status penempatan.
     */
    public function test_status_filter_works_for_dibatalkan_penempatan(): void
    {
        $siswa = $this->createStudent('Gita Cancelled', '99008');

        PenempatanPKL::create([
            'periode_pkl_id' => $this->periode->id,
            'guru_id' => $this->guruA->id,
            'dudi_id' => $this->dudi->id,
            'siswa_id' => $siswa->id,
            'status' => 'dibatalkan',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-11-30',
        ]);

        // When filtering for 'aktif', Gita should NOT appear
        $responseAktif = $this->actingAs($this->guruAUser)->get(route('guru.laporan.siswa', ['status' => 'aktif']));
        $responseAktif->assertStatus(200);
        $responseAktif->assertDontSee($siswa->nama);

        // When filtering for 'dibatalkan', Gita SHOULD appear
        $responseDibatalkan = $this->actingAs($this->guruAUser)->get(route('guru.laporan.siswa', ['status' => 'dibatalkan']));
        $responseDibatalkan->assertStatus(200);
        $responseDibatalkan->assertSee($siswa->nama);
    }
}
