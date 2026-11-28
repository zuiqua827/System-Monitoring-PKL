<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PengajuanKetidakhadiran;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Interfaces\AbsensiServiceInterface;
use App\Services\Interfaces\RekapBulananServiceInterface;
use App\Repositories\Laporan\Interfaces\LaporanRepositoryInterface;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsensiSingleSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    private User $guruUser;
    private Guru $guru;
    private User $dudiUser;
    private Dudi $dudi;
    private User $siswaUser;
    private Siswa $siswa;
    private PeriodePKL $periode;
    private PenempatanPKL $penempatan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // 1. Create Guru
        $this->guruUser = User::factory()->create(['email_verified_at' => now()]);
        $this->guruUser->assignRole('Guru');
        $this->guru = Guru::factory()->create(['user_id' => $this->guruUser->id]);

        // 2. Create DUDI (Operating Monday to Friday)
        $this->dudiUser = User::factory()->create(['email_verified_at' => now()]);
        $this->dudiUser->assignRole('DUDI');
        $this->dudi = Dudi::factory()->create([
            'user_id' => $this->dudiUser->id,
            'hari_operasional' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
            'jam_masuk' => '08:00:00',
            'batas_terlambat' => '08:15:00',
            'batas_sangat_terlambat' => '09:00:00',
        ]);

        // 3. Create Siswa
        $this->siswaUser = User::factory()->create(['email_verified_at' => now()]);
        $this->siswaUser->assignRole('Siswa');
        $this->siswa = Siswa::factory()->create([
            'user_id' => $this->siswaUser->id,
            'nama' => 'Budi Pratama',
            'nis' => '11223344',
        ]);

        // 4. Create Periode and PenempatanPKL
        $this->periode = PeriodePKL::factory()->create([
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'Aktif',
        ]);

        $this->penempatan = PenempatanPKL::factory()->create([
            'periode_pkl_id' => $this->periode->id,
            'guru_id' => $this->guru->id,
            'dudi_id' => $this->dudi->id,
            'siswa_id' => $this->siswa->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Rule 1: Siswa dengan satu hari Hadir.
     */
    public function test_01_siswa_dengan_satu_hari_hadir(): void
    {
        // Mock current time to 2026-09-02 (Wednesday)
        Carbon::setTestNow('2026-09-02 10:00:00');

        // Only 1 past operational day: 2026-09-01 (Tuesday)
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
            'jam_masuk' => '07:55:00',
            'jam_keluar' => '16:00:00',
        ]);

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-01');

        $this->assertEquals(1, $rekap['summary']['hadir']);
        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertEquals(1, $rekap['summary']['total_hari']);
    }

    /**
     * Rule 2: Siswa dengan beberapa hari Alfa karena tidak melakukan presensi.
     */
    public function test_02_siswa_dengan_beberapa_hari_alfa_karena_tidak_melakukan_presensi(): void
    {
        // Mock time to 2026-09-04 10:00:00 (Friday)
        // Concluded days in window: 2026-09-01 (Tue), 2026-09-02 (Wed), 2026-09-03 (Thu) = 3 days
        Carbon::setTestNow('2026-09-04 10:00:00');

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-03');

        $this->assertEquals(0, $rekap['summary']['hadir']);
        $this->assertEquals(3, $rekap['summary']['alfa']);
        $this->assertEquals(3, $rekap['summary']['total_hari']);
        $this->assertCount(3, $rekap['bolos']);
    }

    /**
     * Rule 3: Siswa dengan status Terlambat.
     */
    public function test_03_siswa_dengan_status_terlambat(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');

        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'terlambat',
            'jam_masuk' => '08:20:00',
            'jam_keluar' => '16:00:00',
        ]);

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-01');

        $this->assertEquals(1, $rekap['summary']['terlambat']);
        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertEquals(1, $rekap['summary']['total_hadir']);
    }

    /**
     * Rule 4: Siswa dengan status Izin.
     */
    public function test_04_siswa_dengan_status_izin(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');

        // Pengajuan izin disetujui untuk 2026-09-01
        PengajuanKetidakhadiran::create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'jenis' => 'izin',
            'tanggal' => '2026-09-01',
            'status' => 'disetujui',
            'alasan' => 'Keperluan keluarga',
        ]);

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-01');

        $this->assertEquals(1, $rekap['summary']['izin']);
        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertCount(1, $rekap['izin']);
    }

    /**
     * Rule 5: Siswa dengan status Sakit.
     */
    public function test_05_siswa_dengan_status_sakit(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');

        PengajuanKetidakhadiran::create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'jenis' => 'sakit',
            'tanggal' => '2026-09-01',
            'status' => 'disetujui',
            'alasan' => 'Demam tinggi',
        ]);

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-01');

        $this->assertEquals(1, $rekap['summary']['sakit']);
        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertCount(1, $rekap['sakit']);
    }

    /**
     * Rule 6: Hari libur / non-operasional tidak dihitung Alfa.
     */
    public function test_06_hari_libur_tidak_dihitung_alfa(): void
    {
        // 2026-09-05 is Saturday, 2026-09-06 is Sunday
        // Mock time to 2026-09-07 (Monday)
        Carbon::setTestNow('2026-09-07 10:00:00');

        $service = app(AbsensiServiceInterface::class);
        // Evaluating specifically the weekend days
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-05', '2026-09-06');

        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertEquals(0, $rekap['summary']['total_hari']);
        $this->assertCount(0, $rekap['bolos']);
    }

    /**
     * Rule 7: Hari sebelum mulai PKL tidak dihitung Alfa.
     */
    public function test_07_hari_sebelum_mulai_pkl_tidak_dihitung_alfa(): void
    {
        // Penempatan starts 2026-09-01
        // Mock time to 2026-09-10
        Carbon::setTestNow('2026-09-10 10:00:00');

        $service = app(AbsensiServiceInterface::class);
        // Evaluate August (before PKL start)
        $rekap = $service->calculateAttendance($this->penempatan, '2026-08-01', '2026-08-31');

        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertEquals(0, $rekap['summary']['total_hari']);
    }

    /**
     * Rule 8: Hari setelah selesai PKL tidak dihitung Alfa.
     */
    public function test_08_hari_setelah_selesai_pkl_tidak_dihitung_alfa(): void
    {
        // Set penempatan finished on 2026-09-10
        $this->penempatan->update(['tanggal_selesai' => '2026-09-10']);

        // Mock time to 2026-10-01 (October)
        Carbon::setTestNow('2026-10-01 10:00:00');

        $service = app(AbsensiServiceInterface::class);
        // Evaluate late September (after PKL finish)
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-11', '2026-09-30');

        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertEquals(0, $rekap['summary']['total_hari']);
    }

    /**
     * Rule 9: Siswa tanpa penempatan tidak dihitung Alfa.
     */
    public function test_09_siswa_tanpa_penempatan_tidak_dihitung_alfa(): void
    {
        $siswaBaru = Siswa::factory()->create();

        $service = app(RekapBulananServiceInterface::class);
        $rekap = $service->getMonthlyRecap($siswaBaru, 9, 2026);

        $this->assertEquals(0, $rekap['summary_absensi']['alfa']);
        $this->assertEquals(0, $rekap['summary_absensi']['hadir']);
        $this->assertEquals(0, $rekap['summary_absensi']['total_hari']);
    }

    /**
     * Rule 10: Siswa dengan beberapa status dalam satu bulan.
     */
    public function test_10_siswa_dengan_beberapa_status_dalam_satu_bulan(): void
    {
        // 2026-09-01 (Tue): Hadir
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
            'jam_masuk' => '07:50:00',
        ]);

        // 2026-09-02 (Wed): Terlambat
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-02',
            'status' => 'terlambat',
            'jam_masuk' => '08:25:00',
        ]);

        // 2026-09-03 (Thu): Izin
        PengajuanKetidakhadiran::create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'jenis' => 'izin',
            'tanggal' => '2026-09-03',
            'status' => 'disetujui',
            'alasan' => 'Keperluan keluarga',
        ]);

        // 2026-09-04 (Fri): Sakit
        PengajuanKetidakhadiran::create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'jenis' => 'sakit',
            'tanggal' => '2026-09-04',
            'status' => 'disetujui',
            'alasan' => 'Demam',
        ]);

        // 2026-09-07 (Mon) and 2026-09-08 (Tue): No records (2 Alfa)
        // Mock current date to 2026-09-09 10:00:00 (Wednesday)
        Carbon::setTestNow('2026-09-09 10:00:00');

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-08');

        $this->assertEquals(1, $rekap['summary']['hadir']);
        $this->assertEquals(1, $rekap['summary']['terlambat']);
        $this->assertEquals(1, $rekap['summary']['izin']);
        $this->assertEquals(1, $rekap['summary']['sakit']);
        $this->assertEquals(2, $rekap['summary']['alfa']);
        $this->assertEquals(6, $rekap['summary']['total_hari']);
    }

    /**
     * Rule 11: Filter bulan dan tahun.
     */
    public function test_11_filter_bulan_dan_tahun(): void
    {
        // September 2026
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-15',
            'status' => 'hadir',
        ]);

        // October 2026
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-10-15',
            'status' => 'hadir',
        ]);

        $service = app(AbsensiServiceInterface::class);
        $rekapSept = $service->getRekapAbsensiBulanan($this->penempatan, 9, 2026);
        $rekapOct = $service->getRekapAbsensiBulanan($this->penempatan, 10, 2026);

        $this->assertEquals(1, $rekapSept['summary']['hadir']);
        $this->assertEquals(1, $rekapOct['summary']['hadir']);
    }

    /**
     * Rule 12: Guru tidak dapat melihat siswa di luar kewenangannya.
     */
    public function test_12_guru_tidak_dapat_melihat_siswa_di_luar_kewenangannya(): void
    {
        $otherGuruUser = User::factory()->create(['email_verified_at' => now()]);
        $otherGuruUser->assignRole('Guru');
        $otherGuru = Guru::factory()->create(['user_id' => $otherGuruUser->id]);

        $otherSiswa = Siswa::factory()->create();
        PenempatanPKL::factory()->create([
            'guru_id' => $otherGuru->id,
            'siswa_id' => $otherSiswa->id,
            'periode_pkl_id' => $this->periode->id,
            'dudi_id' => $this->dudi->id,
        ]);

        // Original guru attempts to access rekap-bulanan of other student
        $response = $this->actingAs($this->guruUser)->get(
            route('guru.siswa.rekap-bulanan', [
                'siswa' => $otherSiswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertStatus(403);
    }

    /**
     * Rule 13: DUDI tidak dapat melihat siswa di luar penempatannya.
     */
    public function test_13_dudi_tidak_dapat_melihat_siswa_di_luar_penempatannya(): void
    {
        $otherDudiUser = User::factory()->create(['email_verified_at' => now()]);
        $otherDudiUser->assignRole('DUDI');
        $otherDudi = Dudi::factory()->create(['user_id' => $otherDudiUser->id]);

        $otherSiswa = Siswa::factory()->create();
        PenempatanPKL::factory()->create([
            'guru_id' => $this->guru->id,
            'siswa_id' => $otherSiswa->id,
            'periode_pkl_id' => $this->periode->id,
            'dudi_id' => $otherDudi->id,
        ]);

        // Original DUDI attempts to access rekap-bulanan of other student
        $response = $this->actingAs($this->dudiUser)->get(
            route('dudi.siswa.rekap-bulanan', [
                'siswa' => $otherSiswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );

        $response->assertStatus(403);
    }

    /**
     * Rule 14: Tidak terjadi penghitungan ganda.
     */
    public function test_14_tidak_terjadi_penghitungan_ganda(): void
    {
        Carbon::setTestNow('2026-09-04 10:00:00');

        // Hadir on 2026-09-01
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
        ]);

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-03');

        // 3 days total: 1 Hadir (Sep 1), 2 Alfa (Sep 2, Sep 3)
        $this->assertEquals(1, $rekap['summary']['hadir']);
        $this->assertEquals(2, $rekap['summary']['alfa']);
        $this->assertEquals(3, $rekap['summary']['total_hari']);

        // Sum of all status counts must exactly equal total evaluated days
        $sum = $rekap['summary']['hadir'] +
            $rekap['summary']['terlambat'] +
            $rekap['summary']['izin'] +
            $rekap['summary']['sakit'] +
            $rekap['summary']['alfa'];

        $this->assertEquals(3, $sum);
    }

    /**
     * Rule 15: Hari yang belum selesai tidak dihitung Alfa.
     */
    public function test_15_hari_yang_belum_selesai_tidak_dihitung_alfa(): void
    {
        // Today is 2026-09-01 (Tuesday), first day of PKL, 09:00 in the morning
        Carbon::setTestNow('2026-09-01 09:00:00');

        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-01');

        // Since 2026-09-01 is today and not concluded, it MUST NOT be counted as Alfa yet!
        $this->assertEquals(0, $rekap['summary']['alfa']);
        $this->assertCount(0, $rekap['bolos']);
    }

    /**
     * Special Regression Test:
     * Seorang siswa mempunyai 10 hari wajib PKL, dengan:
     * - 1 hari Hadir
     * - 9 hari tanpa record absensi
     *
     * Hasil yang diharapkan:
     * Hadir = 1, Alfa = 9, Total Hari = 10
     *
     * Konsisten pada:
     * 1. Halaman Siswa (Presensi)
     * 2. Halaman Guru (Rekap Bulanan)
     * 3. Halaman Guru (Laporan PKL)
     */
    public function test_16_regression_test_10_hari_wajib_1_hadir_9_alfa_lintas_halaman(): void
    {
        // 10 operational days from 2026-09-01 to 2026-09-14:
        // Sep 1 (Tue), Sep 2 (Wed), Sep 3 (Thu), Sep 4 (Fri) [4 days]
        // Sep 5, 6 (Weekend)
        // Sep 7 (Mon), Sep 8 (Tue), Sep 9 (Wed), Sep 10 (Thu), Sep 11 (Fri) [5 days]
        // Sep 12, 13 (Weekend)
        // Sep 14 (Mon) [1 day] -> Total 10 days!
        // Mock current time to 2026-09-15 08:00:00 (Tuesday)
        Carbon::setTestNow('2026-09-15 08:00:00');

        // 1 day Hadir on 2026-09-01
        Absensi::factory()->create([
            'penempatan_pkl_id' => $this->penempatan->id,
            'tanggal' => '2026-09-01',
            'status' => 'hadir',
            'jam_masuk' => '07:45:00',
            'jam_keluar' => '16:00:00',
        ]);

        // 9 days have NO absensi records:
        // Sep 2, 3, 4, 7, 8, 9, 10, 11, 14

        // 1. Verify AbsensiService directly
        $service = app(AbsensiServiceInterface::class);
        $rekap = $service->calculateAttendance($this->penempatan, '2026-09-01', '2026-09-14');

        $this->assertEquals(1, $rekap['summary']['hadir']);
        $this->assertEquals(9, $rekap['summary']['alfa']);
        $this->assertEquals(10, $rekap['summary']['total_hari']);

        // 2. Verify Siswa Presensi Page
        $siswaResponse = $this->actingAs($this->siswaUser)->get(route('siswa.absensi.index'));
        $siswaResponse->assertOk();
        $siswaResponse->assertSee('Rekap Presensi Selama Periode PKL');
        $siswaResponse->assertSee('Tidak Hadir (Bolos)');
        // Verify view data
        $viewRekap = $siswaResponse->viewData('rekapPresensi');
        $this->assertNotNull($viewRekap);
        $this->assertCount(1, $viewRekap['hadir']);
        $this->assertCount(9, $viewRekap['bolos']);
        $this->assertEquals(9, $viewRekap['summary']['alfa']);

        // 3. Verify Guru Rekap Bulanan Page
        $guruRekapResponse = $this->actingAs($this->guruUser)->get(
            route('guru.siswa.rekap-bulanan', [
                'siswa' => $this->siswa->id,
                'bulan' => 9,
                'tahun' => 2026,
            ])
        );
        $guruRekapResponse->assertOk();
        $rekapData = $guruRekapResponse->viewData('rekap');
        $this->assertEquals(1, $rekapData['summary_absensi']['hadir']);
        $this->assertEquals(9, $rekapData['summary_absensi']['alfa']);
        $this->assertEquals(10, $rekapData['summary_absensi']['total_hari']);

        // 4. Verify Guru Laporan PKL Absensi Page
        $guruLaporanResponse = $this->actingAs($this->guruUser)->get(
            route('guru.laporan.absensi', [
                'periode_pkl_id' => $this->periode->id,
                'siswa_id' => $this->siswa->id,
                'tanggal_mulai' => '2026-09-01',
                'tanggal_selesai' => '2026-09-14',
            ])
        );
        $guruLaporanResponse->assertOk();
        $laporanStats = $guruLaporanResponse->viewData('stats');
        $this->assertEquals(1, $laporanStats['total_hadir']);
        $this->assertEquals(9, $laporanStats['total_alfa']);
        $this->assertEquals(10, $laporanStats['total_records']);

        // 5. Verify Laporan Repository PDF and Excel Export Data
        $laporanRepo = app(LaporanRepositoryInterface::class);
        $filters = [
            'periode_pkl_id' => $this->periode->id,
            'siswa_id' => $this->siswa->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-14',
        ];

        $pdfRecords = $laporanRepo->getAbsensiReportForPdf($filters);
        $this->assertCount(10, $pdfRecords);

        $summaryStats = $laporanRepo->getAbsensiSummaryStats($filters);
        $this->assertEquals(1, $summaryStats['total_hadir']);
        $this->assertEquals(9, $summaryStats['total_alfa']);

        $exportQuery = $laporanRepo->getAbsensiExportQuery($filters);
        $this->assertCount(10, $exportQuery);
    }
}
