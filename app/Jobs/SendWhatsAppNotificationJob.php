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

        // Set status to sending
        $log->update(['status' => WhatsAppLog::STATUS_SENDING]);

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

        try {
            $result = $waService->executeDirectSend($normalizedPhone, $log->message_content);

            if (($result['success'] ?? false) === true) {
                $log->update([
                    'status' => WhatsAppLog::STATUS_SENT,
                    'error_reason' => null,
                    'response_payload' => $result,
                    'sent_at' => Carbon::now(config('app.timezone')),
                ]);
                return;
            }

            // Gateway returned error response
            $error = $result['error'] ?? 'Pengiriman gagal di WhatsApp Gateway.';
            $isPermanent = $result['is_permanent'] ?? false;

            if ($isPermanent) {
                $log->update([
                    'status' => WhatsAppLog::STATUS_FAILED,
                    'error_reason' => $error,
                    'response_payload' => $result,
                ]);
                return;
            }

            // Transient error: update status and throw to trigger queue retry
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error_reason' => $error,
                'response_payload' => $result,
            ]);

            throw new RuntimeException("WhatsApp Gateway transient error: {$error}");
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();

            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error_reason' => $errorMessage,
            ]);

            Log::warning("SendWhatsAppNotificationJob failure (attempt {$this->attempts()}/{$this->tries}) for log ID {$this->logId}: {$errorMessage}");

            // Let queue handle retry if attempts remaining
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
}
