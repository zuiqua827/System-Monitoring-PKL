<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Interfaces\WhatsAppServiceInterface;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendWhatsAppAttendanceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:attendance-reminders
                            {--force : Kirim tanpa validasi kecocokan waktu H-5}
                            {--time= : Simulasi waktu saat ini format HH:mm}
                            {--date= : Simulasi tanggal spesifik format YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memindai penempatan PKL aktif dan mengirimkan pengingat absensi H-5 serta notifikasi keterlambatan via WhatsApp.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppServiceInterface $waService): int
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');

        $dateStr = $this->option('date');
        $baseDate = $dateStr ? Carbon::parse($dateStr, $timezone) : Carbon::today($timezone);

        $timeStr = $this->option('time');
        if ($timeStr) {
            $simulatedTime = Carbon::createFromFormat(
                'Y-m-d H:i',
                $baseDate->toDateString() . ' ' . substr($timeStr, 0, 5),
                $timezone
            );
        } else {
            $simulatedTime = Carbon::now($timezone);
        }

        $force = (bool) $this->option('force');

        $settings = $waService->getSettings();

        if (!$settings['master_enabled']) {
            $this->info('WhatsApp notifications disabled in system settings. Skipping.');
            return self::SUCCESS;
        }

        $this->info("Scanning PKL placements for date {$simulatedTime->toDateString()} at {$simulatedTime->format('H:i')} (Force: " . ($force ? 'YES' : 'NO') . ")");

        // 1. Process 5-minute pre-entry attendance warnings
        $this->line('<comment>1. Scanning Attendance Warnings (H-5 Menit)...</comment>');
        $reminderResults = $waService->processAttendanceReminders($simulatedTime, $force);

        if ($reminderResults['disabled'] ?? false) {
            $this->line('   Attendance reminder feature is disabled in settings.');
        } else {
            $this->line("   Candidates: {$reminderResults['candidates']}, Queued: {$reminderResults['queued']}, Skipped: {$reminderResults['skipped']}");
            if (!empty($reminderResults['reasons'])) {
                $this->line('   Skip reasons: ' . json_encode($reminderResults['reasons']));
            }
        }

        // 2. Process late notifications (past jam masuk)
        $this->line('<comment>2. Scanning Late Notifications...</comment>');
        $lateResults = $waService->processLateNotifications($simulatedTime, $force);

        if ($lateResults['disabled'] ?? false) {
            $this->line('   Late notification feature is disabled in settings.');
        } else {
            $this->line("   Candidates: {$lateResults['candidates']}, Queued: {$lateResults['queued']}, Skipped: {$lateResults['skipped']}");
            if (!empty($lateResults['reasons'])) {
                $this->line('   Skip reasons: ' . json_encode($lateResults['reasons']));
            }
        }

        $totalQueued = ($reminderResults['queued'] ?? 0) + ($lateResults['queued'] ?? 0);
        $totalSkipped = ($reminderResults['skipped'] ?? 0) + ($lateResults['skipped'] ?? 0);

        $this->info("Attendance scan completed. Total Queued: {$totalQueued}, Total Skipped: {$totalSkipped}.");

        return self::SUCCESS;
    }
}
