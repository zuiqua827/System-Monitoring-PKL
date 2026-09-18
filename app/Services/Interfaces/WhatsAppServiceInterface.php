<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\PenempatanPKL;
use App\Models\WhatsAppLog;
use Carbon\CarbonInterface;

interface WhatsAppServiceInterface
{
    /**
     * Get real-time connection status and QR code from the WhatsApp Gateway.
     *
     * @return array{
     *     is_online: bool,
     *     status: string,
     *     phone: ?string,
     *     name: ?string,
     *     qr: ?string,
     *     error: ?string
     * }
     */
    public function getGatewayStatus(): array;

    /**
     * Direct HTTP call to WhatsApp Gateway (called by queue worker or test send).
     *
     * @return array{
     *     success: bool,
     *     messageId?: string,
     *     error?: string,
     *     is_permanent?: bool
     * }
     */
    public function executeDirectSend(string $phone, string $message): array;

    /**
     * Scan active placements and dispatch 5-minute pre-entry attendance warnings.
     *
     * @return array{
     *     candidates: int,
     *     queued: int,
     *     skipped: int,
     *     reasons: array<string, int>
     * }
     */
    public function processAttendanceReminders(CarbonInterface $currentTime, bool $force = false): array;

    /**
     * Scan active placements and dispatch late notifications if student hasn't clocked in past the entry time.
     *
     * @return array{
     *     candidates: int,
     *     queued: int,
     *     skipped: int,
     *     reasons: array<string, int>
     * }
     */
    public function processLateNotifications(CarbonInterface $currentTime, bool $force = false): array;

    /**
     * Send a single manual test message via WhatsApp Gateway.
     *
     * @return array{success: bool, status: string, error?: string}
     */
    public function sendTestMessage(string $phone, string $message): array;

    /**
     * Get WhatsApp notification settings from database/configuration.
     *
     * @return array{
     *     master_enabled: bool,
     *     reminder_enabled: bool,
     *     late_enabled: bool,
     *     offset_minutes: int,
     *     reminder_template: string,
     *     late_template: string,
     *     gateway_url: string,
     *     api_key: ?string,
     *     enabled: bool
     * }
     */
    public function getSettings(): array;

    /**
     * Update WhatsApp notification settings in database.
     *
     * @param array<string, mixed> $data
     */
    public function updateSettings(array $data): void;

    /**
     * Disconnect/logout current WhatsApp session in Gateway.
     *
     * @return array{success: bool, message: string}
     */
    public function disconnectGateway(): array;

    /**
     * Normalize an Indonesian or international phone number to standard format (e.g. 628xxxxxxxxxx).
     */
    public function normalizePhoneNumber(?string $phone): ?string;

    /**
     * Render message template safely with dynamic placeholder values.
     *
     * @param array<string, string> $placeholders
     */
    public function renderTemplate(string $template, array $placeholders): string;
}
