<?php

declare(strict_types=1);

namespace Tests\Feature;

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

    public function test_reminder_queued_exactly_5_minutes_before_dudi_jam_masuk(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
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
        $time0725 = Carbon::parse('2026-09-18 07:25:00', config('app.timezone')); // Friday
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

    public function test_student_who_already_clocked_in_does_not_receive_reminder(): void
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

        // Student already checked in at 07:10
        Absensi::factory()->create([
            'penempatan_pkl_id' => $penempatan->id,
            'tanggal' => '2026-09-18',
            'jam_masuk' => '07:10:00',
            'status' => 'hadir',
        ]);

        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $result = $this->waService->processAttendanceReminders($time);

        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['reasons']['already_clocked_in']);

        Queue::assertNothingPushed();
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

    public function test_student_without_phone_number_is_safely_skipped_without_crash(): void
    {
        Queue::fake();

        $dudi = Dudi::factory()->create([
            'jam_masuk' => '07:30:00',
            'status_aktif' => true,
            'hari_operasional' => ['jumat'],
        ]);
        // User with no phone
        $user = User::factory()->create(['phone' => null]);
        $siswa = Siswa::factory()->create(['user_id' => $user->id, 'no_telepon' => null]);
        $this->createActivePlacement($dudi, $siswa);

        $time = Carbon::parse('2026-09-18 07:25:00', config('app.timezone'));
        $result = $this->waService->processAttendanceReminders($time);

        $this->assertSame(0, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(1, $result['reasons']['invalid_or_missing_phone']);

        Queue::assertNothingPushed();

        // Check log table has record with status skipped
        $log = WhatsAppLog::first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_SKIPPED, $log->status);
        $this->assertStringContainsString('tidak tersedia', $log->error_reason);
    }

    public function test_idempotency_prevents_duplicate_reminders_on_subsequent_runs(): void
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

        // First scan: queues the reminder
        $res1 = $this->waService->processAttendanceReminders($time);
        $this->assertSame(1, $res1['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        // Second scan at the exact same minute: skips due to idempotency!
        $res2 = $this->waService->processAttendanceReminders($time);
        $this->assertSame(0, $res2['queued']);
        $this->assertSame(1, $res2['skipped']);

        // Exactly one record in database
        $this->assertSame(1, WhatsAppLog::count());
    }

    public function test_late_notification_queued_when_student_has_not_clocked_in_after_jam_masuk(): void
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

        // At 07:35 (after jam masuk 07:30), student still hasn't clocked in
        $currentTime = Carbon::parse('2026-09-18 07:35:00', config('app.timezone'));
        $result = $this->waService->processLateNotifications($currentTime);

        $this->assertSame(1, $result['queued']);
        Queue::assertPushed(SendWhatsAppNotificationJob::class, 1);

        $log = WhatsAppLog::where('message_type', WhatsAppLog::TYPE_ATTENDANCE_LATE)->first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_PENDING, $log->status);
        $this->assertStringContainsString('TERLAMBAT', $log->message_content);
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
}
