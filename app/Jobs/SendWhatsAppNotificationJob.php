<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsAppLog;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $logId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppServiceInterface $waService): void
    {
        /** @var WhatsAppLog|null $log */
        $log = WhatsAppLog::find($this->logId);

        if ($log === null) {
            return;
        }

        // Idempotency guard: If message is already sent or skipped, do not send again
        if ($log->status === WhatsAppLog::STATUS_SENT || $log->status === WhatsAppLog::STATUS_SKIPPED) {
            return;
        }

        // If previously marked as permanent/final failed, do not retry
        if ($log->status === WhatsAppLog::STATUS_FAILED && $log->failed_at !== null) {
            return;
        }

        // Set status to sending
        $log->update(['status' => WhatsAppLog::STATUS_SENDING]);
        Log::info("Processing WhatsApp notification job [Log ID: {$this->logId}] for {$log->recipient_phone}");

        $settings = $waService->getSettings();
        if (!$settings['enabled']) {
            $log->update([
                'status' => WhatsAppLog::STATUS_SKIPPED,
                'error_reason' => 'Fitur WhatsApp dinonaktifkan di pengaturan sistem.',
            ]);
            return;
        }

        // Check if phone number is valid
        $normalizedPhone = $waService->normalizePhoneNumber($log->recipient_phone);
        if ($normalizedPhone === null) {
            // Permanent failure -> mark as skipped, do not retry
            $log->update([
                'status' => WhatsAppLog::STATUS_SKIPPED,
                'error_reason' => 'Nomor WhatsApp tidak valid: "' . $log->recipient_phone . '"',
            ]);
            return;
        }

        $result = $waService->executeDirectSend($normalizedPhone, $log->message_content);

        if ($result['success'] === true) {
            $log->update([
                'status' => WhatsAppLog::STATUS_SENT,
                'error_reason' => null,
                'response_payload' => $result,
                'sent_at' => Carbon::now(config('app.timezone')),
                'failed_at' => null,
            ]);
            Log::info("WhatsApp notification sent successfully [Log ID: {$this->logId}] to {$log->recipient_phone}");
            return;
        }

        // Gateway returned error response
        $isPermanent = (bool) ($result['is_permanent'] ?? false);
        $errorCode = $result['errorCode'] ?? 'UNKNOWN_ERROR';
        $errorMessage = $result['error'] ?? 'Pengiriman gagal di WhatsApp Gateway.';
        $formattedReason = "[{$errorCode}] {$errorMessage}";

        if ($isPermanent) {
            // Permanent failure: NOT_ON_WHATSAPP, INVALID_PHONE, INVALID_PARAMETERS
            // Record failure and complete job without retry
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error_reason' => $formattedReason,
                'response_payload' => $result,
                'failed_at' => Carbon::now(config('app.timezone')),
            ]);
            Log::info("SendWhatsAppNotificationJob permanent error for log ID {$this->logId}: {$formattedReason}");
            return;
        }

        // Transient failure: WHATSAPP_NOT_CONNECTED, TIMEOUT, SEND_FAILED
        $isLastAttempt = $this->attempts() >= $this->tries;

        if ($isLastAttempt) {
            // Final attempt failed
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error_reason' => "{$formattedReason} (Batas percobaan {$this->tries}x tercapai)",
                'response_payload' => $result,
                'failed_at' => Carbon::now(config('app.timezone')),
            ]);
            Log::error("SendWhatsAppNotificationJob exhausted all {$this->tries} attempts for log ID {$this->logId}: {$formattedReason}");
            throw new RuntimeException("WhatsApp Gateway transient error: {$formattedReason}");
        }

        // Retries remaining: keep status as PENDING so scheduler suppresses duplicates and queue handles backoff
        $log->update([
            'status' => WhatsAppLog::STATUS_PENDING,
            'error_reason' => "{$formattedReason} (Percobaan {$this->attempts()}/{$this->tries}, dijadwalkan ulang)",
            'response_payload' => $result,
            'failed_at' => null,
        ]);
        Log::warning("SendWhatsAppNotificationJob failure (attempt {$this->attempts()}/{$this->tries}) for log ID {$this->logId}: {$formattedReason}");
        throw new RuntimeException("WhatsApp Gateway transient error: {$formattedReason}");
    }
}
