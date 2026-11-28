<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AbsensiStatus;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Absensi;
use App\Models\Dudi;
use App\Models\PengajuanKetidakhadiran;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppAttendanceReminderTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppServiceInterface $waService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->waService = app(WhatsAppServiceInterface::class);

        // Configure default enabled settings for test scenarios
        $this->waService->updateSettings([
            'master_enabled' => true,
            'reminder_enabled' => true,
            'late_enabled' => true,
            'offset_minutes' => 5,
        ]);
    }

    private function createActivePlacement(Dudi $dudi, Siswa $siswa, array $attributes = []): PenempatanPKL
    {
        $periode = PeriodePKL::factory()->create(['status' => 'aktif']);

        return PenempatanPKL::factory()->create(array_merge([
            'periode_pkl_id' => $periode->id,
            'dudi_id' => $dudi->id,
            'siswa_id' => $siswa->id,
            'status' => 'aktif',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-11-30',
        ], $attributes));
    }

    /**
     * Scenario 1: Reminder tepat pada waktunya -> terkirim.
     */
    public function test_reminder_queued_on_exact_reminder_time(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);

        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Friday 2026-09-18 at 07:25 (exactly 5 minutes before 07:30)
        $currentTime = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));

        $result = $this->waService->processAttendanceReminders($currentTime);

        $this->assertSame(1, $result['candidates']);
        $this->assertSame(1, $result['queued']);
        $this->assertSame(0, $result['skipped']);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        $log = WhatsAppLog::first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_PENDING, $log->status);
        $this->assertSame('6281234567890', $log->recipient_phone);
        $this->assertStringContainsString($siswa->nama, $log->message_content);
        $this->assertStringContainsString('07:30', $log->message_content);
    }

    /**
     * Scenario 2: Scheduler terlambat 1 menit -> reminder tetap dapat terkirim.
     */
    public function test_reminder_queued_when_scheduler_runs_one_minute_late(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);

        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Reminder time is 07:25. Scheduler runs 1 minute late at 07:26.
        $currentTime = Carbon::parse('2026-09-18 07:26:00', config('app.timezone'));

        $result = $this->waService->processAttendanceReminders($currentTime);

        $this->assertSame(1, $result['queued']);
        $this->assertSame(0, $result['skipped']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    /**
     * Scenario 3: Scheduler terlambat beberapa menit -> reminder tetap dapat terkirim selama masih dalam catch-up window.
     */
    public function test_reminder_queued_when_scheduler_runs_several_minutes_late_within_window(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);

        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Reminder time is 07:25. Scheduler runs at 07:29 (4 minutes late, but still before 07:30 jam_masuk)
        $currentTime = Carbon::parse('2026-09-18 07:29:00', config('app.timezone'));

        $result = $this->waService->processAttendanceReminders($currentTime);

        $this->assertSame(1, $result['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        // Once jam_masuk arrives (07:30), the reminder window closes
        $afterEntryTime = Carbon::parse('2026-09-18 07:30:00', config('app.timezone'));
        $afterResult = $this->waService->processAttendanceReminders($afterEntryTime);
        // Duplicate is already suppressed because it was sent at 07:29
        $this->assertSame(0, $afterResult['queued']);
    }

    /**
     * Scenario 4: Reminder yang sudah pernah dikirim -> tidak dikirim ulang (Idempotency).
     */
    public function test_reminder_already_sent_is_not_sent_again_duplicate_suppression(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        $time1 = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $res1 = $this->waService->processAttendanceReminders($time1);
        $this->assertSame(1, $res1['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        // Second run 2 minutes later at 07:27
        $time2 = Carbon::parse('2026-09-18 07:27:00', config('app.timezone'));
        $res2 = $this->waService->processAttendanceReminders($time2);
        $this->assertSame(0, $res2['queued']);
        $this->assertSame(1, $res2['skipped']);
        $this->assertSame(1, $res2['reasons']['duplicate_suppressed']);

        // Exactly one record in database and only 1 job queued
        $this->assertSame(1, WhatsAppLog::count());
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    /**
     * Scenario 5: Siswa melewati batas terlambat -> notifikasi terlambat dapat dikirim.
     */
    public function test_late_notification_queued_when_student_passes_batas_terlambat(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // At 07:40 (before batas_terlambat 07:45): not late yet
        $timeBefore = Carbon::parse('2026-09-18 07:40:00', config('app.timezone'));
        $resBefore = $this->waService->processLateNotifications($timeBefore);
        $this->assertSame(0, $resBefore['queued']);
        $this->assertSame(1, $resBefore['reasons']['not_late_window']);

        // At 07:46 (past batas_terlambat 07:45): late notification is queued
        $timeAfter = Carbon::parse('2026-09-18 07:46:00', config('app.timezone'));
        $resAfter = $this->waService->processLateNotifications($timeAfter);

        $this->assertSame(1, $resAfter['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        $log = WhatsAppLog::where('message_type', WhatsAppLog::TYPE_ATTENDANCE_LATE)->first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_PENDING, $log->status);
        $this->assertStringContainsString('TERLAMBAT', $log->message_content);
    }

    /**
     * Scenario 6: Sudah lebih dari 30 menit tetapi masih belum absen -> tetap dapat diproses.
     */
    public function test_late_notification_still_sent_even_after_more_than_30_minutes_before_jam_pulang(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'jam_pulang' => '16:00:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Current time is 08:35 (65 minutes after jam_masuk, 50 minutes after batas_terlambat)
        // In the old system, this would fail because currentMinutes > entryMinutes + 30.
        // In our improved system, it is still valid and catches up properly!
        $currentTime = Carbon::parse('2026-09-18 08:35:00', config('app.timezone'));
        $result = $this->waService->processLateNotifications($currentTime);

        $this->assertSame(1, $result['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        $log = WhatsAppLog::where('message_type', WhatsAppLog::TYPE_ATTENDANCE_LATE)->first();
        $this->assertNotNull($log);
    }

    /**
     * Scenario 7: Notifikasi terlambat yang sudah pernah dikirim -> tidak dikirim ulang.
     */
    public function test_late_notification_already_sent_is_not_sent_again(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Run 1: At 07:50
        $time1 = Carbon::parse('2026-09-18 07:50:00', config('app.timezone'));
        $res1 = $this->waService->processLateNotifications($time1);
        $this->assertSame(1, $res1['queued']);

        // Run 2: At 08:15 (25 minutes later)
        $time2 = Carbon::parse('2026-09-18 08:15:00', config('app.timezone'));
        $res2 = $this->waService->processLateNotifications($time2);
        $this->assertSame(0, $res2['queued']);
        $this->assertSame(1, $res2['skipped']);
        $this->assertSame(1, $res2['reasons']['duplicate_suppressed']);

        $this->assertSame(1, WhatsAppLog::where('message_type', WhatsAppLog::TYPE_ATTENDANCE_LATE)->count());
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    /**
     * Scenario 8: Siswa memiliki absensi hadir -> tidak menerima reminder atau late notice.
     */
    public function test_student_with_hadir_attendance_does_not_receive_reminder_or_late_notice(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $penempatan = $this->createActivePlacement($dudi, $siswa);

        Absensi::factory()->create([
            'penempatan_pkl_id' => $penempatan->id,
            'tanggal' => '2026-09-18',
            'status' => AbsensiStatus::HADIR->value,
            'jam_masuk' => '07:15:00',
        ]);

        // Test Reminder
        $timeReminder = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $resReminder = $this->waService->processAttendanceReminders($timeReminder);
        $this->assertSame(0, $resReminder['queued']);
        $this->assertSame(1, $resReminder['skipped']);
        $this->assertSame(1, $resReminder['reasons']['already_clocked_in']);

        // Test Late
        $timeLate = Carbon::parse('2026-09-18 08:00:00', config('app.timezone'));
        $resLate = $this->waService->processLateNotifications($timeLate);
        $this->assertSame(0, $resLate['queued']);
        $this->assertSame(1, $resLate['skipped']);
        $this->assertSame(1, $resLate['reasons']['already_clocked_in']);

        Queue::assertNothingPushed();
    }

    /**
     * Scenario 9: Siswa memiliki absensi sakit -> tidak menerima reminder atau late notice.
     */
    public function test_student_with_sakit_attendance_does_not_receive_reminder_or_late_notice(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $penempatan = $this->createActivePlacement($dudi, $siswa);

        Absensi::factory()->create([
            'penempatan_pkl_id' => $penempatan->id,
            'tanggal' => '2026-09-18',
            'status' => AbsensiStatus::SAKIT->value,
            'jam_masuk' => null,
        ]);

        $timeReminder = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $resReminder = $this->waService->processAttendanceReminders($timeReminder);
        $this->assertSame(0, $resReminder['queued']);
        $this->assertSame(1, $resReminder['reasons']['already_clocked_in']);

        $timeLate = Carbon::parse('2026-09-18 08:00:00', config('app.timezone'));
        $resLate = $this->waService->processLateNotifications($timeLate);
        $this->assertSame(0, $resLate['queued']);
        $this->assertSame(1, $resLate['reasons']['already_clocked_in']);

        Queue::assertNothingPushed();
    }

    /**
     * Scenario 10: Siswa memiliki absensi izin -> tidak menerima reminder atau late notice.
     */
    public function test_student_with_izin_attendance_does_not_receive_reminder_or_late_notice(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $penempatan = $this->createActivePlacement($dudi, $siswa);

        Absensi::factory()->create([
            'penempatan_pkl_id' => $penempatan->id,
            'tanggal' => '2026-09-18',
            'status' => AbsensiStatus::IZIN->value,
            'jam_masuk' => null,
        ]);

        $timeReminder = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $resReminder = $this->waService->processAttendanceReminders($timeReminder);
        $this->assertSame(0, $resReminder['queued']);
        $this->assertSame(1, $resReminder['reasons']['already_clocked_in']);

        $timeLate = Carbon::parse('2026-09-18 08:00:00', config('app.timezone'));
        $resLate = $this->waService->processLateNotifications($timeLate);
        $this->assertSame(0, $resLate['queued']);
        $this->assertSame(1, $resLate['reasons']['already_clocked_in']);

        Queue::assertNothingPushed();
    }

    /**
     * Scenario 11: Siswa memiliki absensi alpha -> tidak dianggap sebagai "belum absen" jika sudah dicatat.
     */
    public function test_student_with_alpha_attendance_does_not_receive_reminder_or_late_notice(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $penempatan = $this->createActivePlacement($dudi, $siswa);

        Absensi::factory()->create([
            'penempatan_pkl_id' => $penempatan->id,
            'tanggal' => '2026-09-18',
            'status' => AbsensiStatus::ALPHA->value,
            'jam_masuk' => null,
        ]);

        $timeReminder = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $resReminder = $this->waService->processAttendanceReminders($timeReminder);
        $this->assertSame(0, $resReminder['queued']);
        $this->assertSame(1, $resReminder['reasons']['already_clocked_in']);

        $timeLate = Carbon::parse('2026-09-18 08:00:00', config('app.timezone'));
        $resLate = $this->waService->processLateNotifications($timeLate);
        $this->assertSame(0, $resLate['queued']);
        $this->assertSame(1, $resLate['reasons']['already_clocked_in']);

        Queue::assertNothingPushed();
    }

    /**
     * Scenario 12: Siswa tanpa nomor WhatsApp -> di-skip dengan alasan yang jelas.
     */
    public function test_student_without_phone_number_is_safely_skipped_with_clear_reason(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => null]);
        $siswa = Siswa::factory()->create(['user_id' => $user->id, 'no_telepon' => null]);
        $this->createActivePlacement($dudi, $siswa);

        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $result = $this->waService->processAttendanceReminders($time);

        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['reasons']['invalid_or_missing_phone']);

        Queue::assertNothingPushed();

        $log = WhatsAppLog::first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_SKIPPED, $log->status);
        $this->assertStringContainsString('tidak tersedia', $log->error_reason);
    }

    /**
     * Scenario 13: Timezone Asia/Jakarta tetap digunakan.
     */
    public function test_timezone_asia_jakarta_is_consistently_used_for_reminders_and_late_notifications(): void
    {
        Queue::fake();

        $this->assertSame('Asia/Jakarta', config('app.timezone'));

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'batas_terlambat' => '07:45:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Date parsed with Asia/Jakarta
        $timeJakarta = Carbon::parse('2026-09-18 07:25:00', 'Asia/Jakarta');
        $this->assertSame('Asia/Jakarta', $timeJakarta->getTimezone()->getName());

        $result = $this->waService->processAttendanceReminders($timeJakarta);
        $this->assertSame(1, $result['queued']);

        $log = WhatsAppLog::first();
        $this->assertNotNull($log);
        $this->assertSame('2026-09-18', $log->tanggal->toDateString());
        $this->assertSame('Asia/Jakarta', $log->tanggal->getTimezone()->getName());
    }

    public function test_different_dudi_have_different_reminder_times(): void
    {
        Queue::fake();

        // DUDI A: jam masuk 07:30 -> reminder 07:25
        $dudiA = Dudi::factory()->create([
            'nama_perusahaan' => 'DUDI A',
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $userA = User::factory()->create(['phone' => '081111111111']);
        $siswaA = Siswa::factory()->create(['user_id' => $userA->id]);
        $this->createActivePlacement($dudiA, $siswaA);

        // DUDI B: jam masuk 08:00 -> reminder 07:55
        $dudiB = Dudi::factory()->create([
            'nama_perusahaan' => 'DUDI B',
            'jam_masuk' => '08:00:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $userB = User::factory()->create(['phone' => '082222222222']);
        $siswaB = Siswa::factory()->create(['user_id' => $userB->id]);
        $this->createActivePlacement($dudiB, $siswaB);

        // At 07:25, only DUDI A should be queued, DUDI B should be skipped (not its reminder time)
        $time0725 = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $res0725 = $this->waService->processAttendanceReminders($time0725);

        $this->assertSame(2, $res0725['candidates']);
        $this->assertSame(1, $res0725['queued']);
        $this->assertSame(1, $res0725['skipped']);
        $this->assertSame(1, $res0725['reasons']['not_reminder_time']);

        // Now at 07:55, DUDI B should be queued
        $time0755 = Carbon::parse('2026-09-18 07:55:00', config('app.timezone'));
        $res0755 = $this->waService->processAttendanceReminders($time0755);

        $this->assertSame(1, $res0755['queued']);
    }

    public function test_student_with_approved_absence_does_not_receive_reminder(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $penempatan = $this->createActivePlacement($dudi, $siswa);

        // Approved leave
        PengajuanKetidakhadiran::create([
            'penempatan_pkl_id' => $penempatan->id,
            'tanggal' => '2026-09-18',
            'jenis' => 'izin',
            'alasan' => 'Ada acara keluarga',
            'status' => 'disetujui',
        ]);

        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $result = $this->waService->processAttendanceReminders($time);

        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['reasons']['approved_leave']);

        Queue::assertNothingPushed();
    }

    public function test_dudi_holiday_does_not_trigger_reminder(): void
    {
        Queue::fake();

        // DUDI only operates on Mondays
        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['senin'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        // Friday is not an operational day for this DUDI
        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $result = $this->waService->processAttendanceReminders($time);

        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['reasons']['dudi_holiday']);

        Queue::assertNothingPushed();
    }

    public function test_master_switch_disabled_skips_all_processing(): void
    {
        Queue::fake();

        $this->waService->updateSettings(['master_enabled' => false]);

        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $resReminder = $this->waService->processAttendanceReminders($time);
        $resLate = $this->waService->processLateNotifications($time);

        $this->assertTrue($resReminder['disabled']);
        $this->assertTrue($resLate['disabled']);
        Queue::assertNothingPushed();
    }

    public function test_artisan_command_executes_cleanly_with_time_simulation(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        $this->artisan('whatsapp:attendance-reminders', [
            '--date' => '2026-09-18',
            '--time' => '07:25',
        ])
        ->expectsOutputToContain('Attendance scan completed')
        ->assertExitCode(0);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    public function test_scheduler_does_not_duplicate_job_when_log_failed_or_in_retry(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        $user = User::factory()->create(['phone' => '081234567890']);
        $siswa = Siswa::factory()->create(['user_id' => $user->id]);
        $this->createActivePlacement($dudi, $siswa);

        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));

        // First scan queues job
        $res1 = $this->waService->processAttendanceReminders($time);
        $this->assertSame(1, $res1['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        // Simulate log failing transiently and being in PENDING or FAILED state
        $log = WhatsAppLog::first();
        $log->update(['status' => WhatsAppLog::STATUS_FAILED, 'failed_at' => Carbon::now()]);

        // Second scan running 1 minute later MUST NOT dispatch another duplicate job
        $timeLater = Carbon::parse('2026-09-18 07:26:00', config('app.timezone'));
        $res2 = $this->waService->processAttendanceReminders($timeLater, true); // force=true to bypass window

        $this->assertSame(0, $res2['queued']);
        $this->assertSame(1, $res2['skipped']);
        $this->assertSame(1, $res2['reasons']['duplicate_suppressed']);

        // Still only 1 job ever pushed
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);
    }

    public function test_concurrency_safe_first_or_create_handles_duplicate_key_without_crashing(): void
    {
        $idempotencyKey = 'unique-test-key-' . uniqid();

        // Process 1 creates the record
        $log1 = WhatsAppLog::firstOrCreateSafe(
            ['idempotency_key' => $idempotencyKey],
            [
                'recipient_phone' => '6281234567890',
                'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
                'message_content' => 'Test Concurrency',
                'status' => WhatsAppLog::STATUS_PENDING,
                'tanggal' => '2026-09-18',
            ]
        );
        $this->assertTrue($log1->wasRecentlyCreated);

        // Process 2 simulates a concurrent attempt with the same idempotency key
        $log2 = WhatsAppLog::firstOrCreateSafe(
            ['idempotency_key' => $idempotencyKey],
            [
                'recipient_phone' => '6281234567890',
                'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
                'message_content' => 'Test Concurrency Second',
                'status' => WhatsAppLog::STATUS_PENDING,
                'tanggal' => '2026-09-18',
            ]
        );

        $this->assertSame($log1->id, $log2->id);
        $this->assertFalse($log2->wasRecentlyCreated);
    }
}
