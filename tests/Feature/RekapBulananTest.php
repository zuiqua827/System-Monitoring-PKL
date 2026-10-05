<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Aktivitas;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Interfaces\RekapBulananServiceInterface;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RekapBulananTest extends TestCase
{
    use RefreshDatabase;

    private User $guruUser;
    private Guru $guru;
    private User $dudiUser;
    private Dudi $dudi;
    private Siswa $siswa;
    private PenempatanPKL $penempatan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // 1. Create Guru
        $this->guruUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->guruUser->assignRole('Guru');
        $this->guru = Guru::factory()->create(['user_id' => $this->guruUser->id]);

        // 2. Create DUDI
        $this->dudiUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->dudiUser->assignRole('DUDI');
        $this->dudi = Dudi::factory()->create(['user_id' => $this->dudiUser->id]);

        // 3. Create Siswa
        $siswaUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $siswaUser->assignRole('Siswa');
        $this->siswa = Siswa::factory()->create([
            'user_id' => $siswaUser->id,
            'nama' => 'Ahmad Santoso',
            'nis' => '12345678',
        ]);

        // 4. Create PenempatanPKL linking Guru, DUDI, and Siswa
        $periode = PeriodePKL::factory()->create();
        $this->penempatan = PenempatanPKL::factory()->create([
            'periode_pkl_id' => $periode->id,
            'guru_id' => $this->guru->id,
            'dudi_id' => $this->dudi->id,
            'siswa_id' => $this->siswa->id,
            'status' => 'aktif',
        ]);
    }

    /**
     * Test 1: Rekap bulan dengan data lengkap (absensi + aktivitas).
     */
    public function test_1_rekap_bulan_dengan_data_lengkap(): void
    {
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
            'jam_masuk' => '07:42:00',
            'jam_keluar' => '15:30:00',
        ]);

        Aktivitas::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'judul' => 'Membantu administrasi kantor',
            'deskripsi' => 'Membantu input data absensi dan arsip surat',
            'status' => 'disetujui',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '12:00:00',
        ]);

        $response = $this->actingAs($this->guruUser)->get(
            route('guru.siswa.rekap-bulanan', [
                'siswa' => $this->siswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertOk();
        $response->assertSee('Ahmad Santoso');
        $response->assertSee('September 2026');
        $response->assertSee('Membantu administrasi kantor');
        $response->assertSee('Hadir');
    }

    /**
     * Test 2: Rekap bulan tanpa aktivitas (hanya ada absensi).
     */
    public function test_2_rekap_bulan_tanpa_aktivitas(): void
    {
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-02',
            'status' => 'hadir',
            'jam_masuk' => '07:30:00',
            'jam_keluar' => '15:30:00',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(1, $rekap['summary_absensi']['hadir']);
        $this->assertEquals(0, $rekap['summary_aktivitas']['total']);
        $this->assertTrue($rekap['has_absensi']);
        $this->assertFalse($rekap['has_aktivitas']);

        $response = $this->actingAs($this->guruUser)->get(
            route('guru.rekap-bulanan.index', [
                'siswa_id' => $this->siswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertOk();
        $response->assertSee('Belum ada aktivitas pada periode ini.');
    }

    /**
     * Test 3: Rekap bulan tanpa absensi (hanya ada aktivitas).
     */
    public function test_3_rekap_bulan_tanpa_absensi(): void
    {
        Aktivitas::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-05',
            'judul' => 'Kegiatan Remote Coding',
            'deskripsi' => 'Membuat modul autentikasi',
            'status' => 'disetujui',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(0, $rekap['summary_absensi']['total_hari']);
        $this->assertEquals(1, $rekap['summary_aktivitas']['total']);
        $this->assertFalse($rekap['has_absensi']);
        $this->assertTrue($rekap['has_aktivitas']);

        $response = $this->actingAs($this->guruUser)->get(
            route('guru.rekap-bulanan.index', [
                'siswa_id' => $this->siswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertOk();
        $response->assertSee('Belum ada data absensi pada periode ini.');
    }

    /**
     * Test 4: Rekap siswa yang memiliki hadir dihitung dengan benar.
     */
    public function test_4_rekap_siswa_yang_memiliki_hadir(): void
    {
        $this->penempatan->update([
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-03',
        ]);

        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
        ]);
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-02',
            'status' => 'hadir',
        ]);
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-03',
            'status' => 'hadir',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(3, $rekap['summary_absensi']['hadir']);
        $this->assertEquals(3, $rekap['summary_absensi']['total_hari']);
    }

    /**
     * Test 5: Rekap siswa yang terlambat.
     */
    public function test_5_rekap_siswa_yang_terlambat(): void
    {
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-04',
            'status' => 'terlambat',
            'jam_masuk' => '08:15:00',
        ]);
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-05',
            'status' => 'terlambat',
            'jam_masuk' => '08:30:00',
            'keterangan' => 'Sangat Terlambat',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(2, $rekap['summary_absensi']['terlambat']);
        $this->assertEquals('Terlambat', $rekap['rekap_harian'][0]['status_keterlambatan']);
        $this->assertEquals('Sangat Terlambat', $rekap['rekap_harian'][1]['status_keterlambatan']);
    }

    /**
     * Test 6: Rekap siswa izin.
     */
    public function test_6_rekap_siswa_izin(): void
    {
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-06',
            'status' => 'izin',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(1, $rekap['summary_absensi']['izin']);
        $this->assertEquals('Izin', $rekap['rekap_harian'][0]['status_absensi']);
    }

    /**
     * Test 7: Rekap siswa sakit.
     */
    public function test_7_rekap_siswa_sakit(): void
    {
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-07',
            'status' => 'sakit',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(1, $rekap['summary_absensi']['sakit']);
        $this->assertEquals('Sakit', $rekap['rekap_harian'][0]['status_absensi']);
    }

    /**
     * Test 8: Rekap siswa alfa.
     */
    public function test_8_rekap_siswa_alfa(): void
    {
        $this->penempatan->update([
            'tanggal_mulai' => '2026-09-08',
            'tanggal_selesai' => '2026-09-08',
        ]);

        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-08',
            'status' => 'alpha',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(1, $rekap['summary_absensi']['alfa']);
        $this->assertEquals('Alfa', $rekap['rekap_harian'][0]['status_absensi']);
    }

    /**
     * Test 9: Total aktivitas dihitung dengan benar.
     */
    public function test_9_total_aktivitas_dihitung_dengan_benar(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Aktivitas::factory()->create([
                'penempatan_pkl_id' => $this->penempatan->id,
                'tanggal' => "2026-09-0{$i}",
                'judul' => "Tugas {$i}",
            ]);
        }

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(5, $rekap['summary_aktivitas']['total']);
    }

    /**
     * Test 10: Detail aktivitas hanya menampilkan aktivitas pada bulan yang dipilih.
     */
    public function test_10_detail_aktivitas_hanya_menampilkan_aktivitas_pada_bulan_yang_dipilih(): void
    {
        // Activity in August 2026 (previous month)
        Aktivitas::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-08-31',
            'judul' => 'Kegiatan Agustus',
        ]);

        // Activity in September 2026 (target month)
        Aktivitas::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-15',
            'judul' => 'Kegiatan September',
        ]);

        // Activity in October 2026 (next month)
        Aktivitas::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-10-01',
            'judul' => 'Kegiatan Oktober',
        ]);

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $this->assertEquals(1, $rekap['summary_aktivitas']['total']);
        $rowsWithAktivitas = array_values(array_filter($rekap['rekap_harian'], fn($r) => $r['jumlah_aktivitas'] > 0));
        $this->assertCount(1, $rowsWithAktivitas);
        $this->assertEquals('Kegiatan September', $rowsWithAktivitas[0]['aktivitas'][0]['judul']);
    }

    /**
     * Test 11: Guru tidak dapat melihat siswa di luar kewenangannya.
     */
    public function test_11_guru_tidak_dapat_melihat_siswa_di_luar_kewenangannya(): void
    {
        // Another Guru
        $otherGuruUser = User::factory()->create(['email_verified_at' => now()]);
        $otherGuruUser->assignRole('Guru');
        $otherGuru = Guru::factory()->create(['user_id' => $otherGuruUser->id]);

        // Another Student placed ONLY under otherGuru
        $otherSiswa = Siswa::factory()->create();
        PenempatanPKL::factory()->create([
            'guru_id' => $otherGuru->id,
            'siswa_id' => $otherSiswa->id,
        ]);

        // Original guru tries to access other student
        $response = $this->actingAs($this->guruUser)->get(
            route('guru.siswa.rekap-bulanan', [
                'siswa' => $otherSiswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertForbidden();
    }

    /**
     * Test 12: DUDI tidak dapat melihat siswa di luar penempatannya.
     */
    public function test_12_dudi_tidak_dapat_melihat_siswa_di_luar_penempatannya(): void
    {
        // Another DUDI
        $otherDudiUser = User::factory()->create(['email_verified_at' => now()]);
        $otherDudiUser->assignRole('DUDI');
        $otherDudi = Dudi::factory()->create(['user_id' => $otherDudiUser->id]);

        // Another Student placed ONLY under otherDudi
        $otherSiswa = Siswa::factory()->create();
        PenempatanPKL::factory()->create([
            'dudi_id' => $otherDudi->id,
            'siswa_id' => $otherSiswa->id,
        ]);

        // Original DUDI tries to access other student
        $response = $this->actingAs($this->dudiUser)->get(
            route('dudi.siswa.rekap-bulanan', [
                'siswa' => $otherSiswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertForbidden();
    }

    /**
     * Test 13: Bulan berbeda menghasilkan data berbeda.
     */
    public function test_13_bulan_berbeda_menghasilkan_data_berbeda(): void
    {
        // September: 2 Hadir, 1 Aktivitas
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
        ]);
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-02',
            'status' => 'hadir',
        ]);
        Aktivitas::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'judul' => 'Aktivitas September',
        ]);

        // October: 1 Terlambat, 3 Aktivitas
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-10-05',
            'status' => 'terlambat',
        ]);
        for ($i = 1; $i <= 3; $i++) {
            Aktivitas::factory()->create([
                'penempatan_pkl_id' => $this->penempatan->id,
                'tanggal' => "2026-10-0{$i}",
                'judul' => "Aktivitas Oktober {$i}",
            ]);
        }

        $service = app(RekapBulananServiceInterface::class);

        $rekapSeptember = $service->getMonthlyRecap($this->siswa, 9, 2026);
        $rekapOktober = $service->getMonthlyRecap($this->siswa, 10, 2026);

        $this->assertEquals(2, $rekapSeptember['summary_absensi']['hadir']);
        $this->assertEquals(0, $rekapSeptember['summary_absensi']['terlambat']);
        $this->assertEquals(1, $rekapSeptember['summary_aktivitas']['total']);

        $this->assertEquals(0, $rekapOktober['summary_absensi']['hadir']);
        $this->assertEquals(1, $rekapOktober['summary_absensi']['terlambat']);
        $this->assertEquals(3, $rekapOktober['summary_aktivitas']['total']);
    }

    /**
     * Test 14: Tidak terjadi N+1 query.
     */
    public function test_14_tidak_terjadi_n_plus_one_query(): void
    {
        // Seed 15 absensi records and 15 aktivitas records across different dates
        for ($i = 1; $i <= 15; $i++) {
            $day = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            Absensi::factory()->create([
                'penempatan_pkl_id' => $this->penempatan->id,
                'tanggal' => "2026-09-{$day}",
                'status' => 'hadir',
            ]);
            Aktivitas::factory()->create([
                'penempatan_pkl_id' => $this->penempatan->id,
                'tanggal' => "2026-09-{$day}",
                'judul' => "Aktivitas Hari ke-{$i}",
            ]);
        }

        DB::enableQueryLog();

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($this->siswa, 9, 2026);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertEquals(15, $rekap['summary_absensi']['hadir']);
        $this->assertEquals(15, $rekap['summary_aktivitas']['total']);

        // The query count is constant and minimal (<= 12 queries for eager loading, completely free of N+1)
        $this->assertLessThanOrEqual(12, count($queries));
    }
}
