<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\Absensi;
use App\Models\PengajuanKetidakhadiran;
use App\Models\PenempatanPKL;
use App\Models\Setting;
use App\Models\WhatsAppLog;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService extends Service implements WhatsAppServiceInterface
{
    public const DEFAULT_REMINDER_TEMPLATE = "⚠️ *PERINGATAN ABSENSI PKL*\n\nHalo {nama},\n\nAnda belum melakukan absensi masuk PKL hari ini.\n\nDUDI: {dudi}\nJam masuk: {jam_masuk}\nTanggal: {tanggal}\n\nSilakan segera melakukan absensi sebelum batas waktu untuk menghindari status terlambat.\n\n— Sistem Monitoring PKL";

    public const DEFAULT_LATE_TEMPLATE = "🔴 *ABSENSI TERLAMBAT*\n\nHalo {nama},\n\nAnda belum melakukan absensi masuk sampai batas waktu yang ditentukan.\n\nDUDI: {dudi}\nJam masuk: {jam_masuk}\nStatus: TERLAMBAT\nTanggal: {tanggal}\n\nSilakan segera melakukan absensi.\n\n— Sistem Monitoring PKL";

    public const DEFAULT_OFFSET_MINUTES = 5;

    /**
     * {@inheritDoc}
     */
    public function getGatewayStatus(): array
    {
        $settings = $this->getSettings();
        $gatewayUrl = rtrim($settings['gateway_url'], '/');
        $apiKey = $settings['api_key'] ?? config('services.whatsapp.api_key');
        $timeout = (int) config('services.whatsapp.timeout', 10);

        try {
            $req = Http::timeout($timeout);
            if (!empty($apiKey)) {
                $req = $req->withHeaders([
                    'x-api-key' => $apiKey,
                    'Authorization' => "Bearer {$apiKey}",
                ]);
            }

            $response = $req->get("{$gatewayUrl}/api/status");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'is_online' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'phone' => $data['phone'] ?? null,
                    'name' => $data['name'] ?? null,
                    'qr' => $data['qr'] ?? null,
                    'error' => null,
                ];
            }

            return [
                'is_online' => false,
                'status' => 'error',
                'phone' => null,
                'name' => null,
                'qr' => null,
                'error' => 'Gateway merespons status HTTP ' . $response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'is_online' => false,
                'status' => 'disconnected',
                'phone' => null,
                'name' => null,
                'qr' => null,
                'error' => 'Tidak dapat terhubung ke WhatsApp Gateway: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function executeDirectSend(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        if ($normalizedPhone === null) {
            return [
                'success' => false,
                'messageId' => null,
                'error' => 'Nomor WhatsApp tidak valid: "' . $phone . '"',
                'errorCode' => 'INVALID_PHONE',
                'is_permanent' => true,
            ];
        }

        if (trim($message) === '') {
            return [
                'success' => false,
                'messageId' => null,
                'error' => 'Pesan WhatsApp tidak boleh kosong.',
                'errorCode' => 'INVALID_MESSAGE',
                'is_permanent' => true,
            ];
        }

        $settings = $this->getSettings();
        $gatewayUrl = rtrim($settings['gateway_url'], '/');
        $apiKey = $settings['api_key'] ?? config('services.whatsapp.api_key');
        $timeout = (int) config('services.whatsapp.timeout', 15);

        try {
            $req = Http::timeout($timeout);
            if (!empty($apiKey)) {
                $req = $req->withHeaders([
                    'x-api-key' => $apiKey,
                    'Authorization' => "Bearer {$apiKey}",
                ]);
            }

            $response = $req->post("{$gatewayUrl}/api/send", [
                'phone' => $normalizedPhone,
                'message' => $message,
            ]);

            $payload = $response->json() ?? [];

            if ($response->successful() && ($payload['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'messageId' => $payload['messageId'] ?? null,
                    'error' => null,
                    'errorCode' => null,
                    'is_permanent' => false,
                ];
            }

            $errorCode = $payload['errorCode'] ?? null;
            $errorMessage = $payload['error'] ?? ('Gagal mengirim pesan (HTTP ' . $response->status() . ')');

            // Determine permanence: payload isPermanent takes precedence, then known permanent errorCodes / HTTP status
            $isPermanent = false;
            if (isset($payload['isPermanent'])) {
                $isPermanent = (bool) $payload['isPermanent'];
            } elseif (isset($payload['is_permanent'])) {
                $isPermanent = (bool) $payload['is_permanent'];
            } elseif ($errorCode !== null) {
                $isPermanent = in_array($errorCode, ['INVALID_PARAMETERS', 'INVALID_PHONE', 'INVALID_MESSAGE', 'NOT_ON_WHATSAPP'], true);
            } else {
                $isPermanent = in_array($response->status(), [400, 422], true);
            }

            if ($errorCode === null) {
                if ($response->status() === 503) {
                    $errorCode = 'WHATSAPP_NOT_CONNECTED';
                } elseif ($response->status() === 504 || $response->status() === 408) {
                    $errorCode = 'TIMEOUT';
                } elseif ($response->status() === 400) {
                    $errorCode = 'INVALID_PARAMETERS';
                } elseif ($response->status() === 422) {
                    $errorCode = 'INVALID_PHONE';
                } else {
                    $errorCode = 'SEND_FAILED';
                }
            }

            return [
                'success' => false,
                'messageId' => null,
                'error' => $errorMessage,
                'errorCode' => $errorCode,
                'is_permanent' => $isPermanent,
            ];
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $isTimeout = str_contains(strtolower($msg), 'timeout') || str_contains(strtolower($msg), 'timed out');
            return [
                'success' => false,
                'messageId' => null,
                'error' => 'Koneksi ke WhatsApp Gateway gagal: ' . $msg,
                'errorCode' => $isTimeout ? 'TIMEOUT' : 'WHATSAPP_NOT_CONNECTED',
                'is_permanent' => false, // Network/timeout error is transient
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function processAttendanceReminders(CarbonInterface $currentTime, bool $force = false): array
    {
        $settings = $this->getSettings();

        // Check if master switch and reminder switch are enabled
        if (!$settings['master_enabled'] || !$settings['reminder_enabled']) {
            return [
                'candidates' => 0,
                'queued' => 0,
                'skipped' => 0,
                'disabled' => true,
                'reasons' => ['feature_disabled' => 1],
            ];
        }

        $dateStr = $currentTime->toDateString();
        $offsetMinutes = $settings['offset_minutes'];

        $penempatans = $this->getActivePlacementsForDate($dateStr);

        $candidates = 0;
        $queued = 0;
        $skipped = 0;
        $reasons = [];

        foreach ($penempatans as $penempatan) {
            $candidates++;
            $dudi = $penempatan->dudi;
            $siswa = $penempatan->siswa;

            if (!$dudi->status_aktif) {
                $skipped++;
                $reasons['dudi_inactive'] = ($reasons['dudi_inactive'] ?? 0) + 1;
                continue;
            }

            // Check if today is an operational day for this specific DUDI
            if (!$dudi->isHariOperasional($currentTime)) {
                $skipped++;
                $reasons['dudi_holiday'] = ($reasons['dudi_holiday'] ?? 0) + 1;
                continue;
            }

            // Determine DUDI entry time
            $jamMasuk = Carbon::parse($dudi->jam_masuk ?? '07:00:00', config('app.timezone'));
            $reminderTime = $jamMasuk->copy()->subMinutes($offsetMinutes)->format('H:i');

            // Verify if current time matches reminder time (e.g. 5 min before jam masuk)
            if (!$force && $currentTime->format('H:i') !== $reminderTime) {
                $skipped++;
                $reasons['not_reminder_time'] = ($reasons['not_reminder_time'] ?? 0) + 1;
                continue;
            }

            // Check if student has already checked in today
            $alreadyClockedIn = Absensi::where('penempatan_pkl_id', $penempatan->id)
                ->whereDate('tanggal', $dateStr)
                ->whereNotNull('jam_masuk')
                ->exists();

            if ($alreadyClockedIn) {
                $skipped++;
                $reasons['already_clocked_in'] = ($reasons['already_clocked_in'] ?? 0) + 1;
                continue;
            }

            // Check if student has an approved absence (izin / sakit)
            $hasApprovedLeave = PengajuanKetidakhadiran::where('penempatan_pkl_id', $penempatan->id)
                ->whereDate('tanggal', $dateStr)
                ->where('status', 'disetujui')
                ->exists();

            if ($hasApprovedLeave) {
                $skipped++;
                $reasons['approved_leave'] = ($reasons['approved_leave'] ?? 0) + 1;
                continue;
            }

            // Idempotency: generate unique key
            $idempotencyKey = WhatsAppLog::generateIdempotencyKey(
                (int) $siswa->id,
                $dateStr,
                WhatsAppLog::TYPE_ATTENDANCE_REMINDER
            );

            // Student phone number: prioritize User::$phone (Pengaturan Akun), fallback to Siswa::$no_telepon
            $user = $siswa->user;
            $rawPhone = trim((string) ($user->phone ?? $siswa->no_telepon ?? ''));
            $normalizedPhone = $this->normalizePhoneNumber($rawPhone);

            // Render message template
            $message = $this->renderTemplate($settings['reminder_template'], [
                'nama' => $siswa->nama,
                'nama_siswa' => $siswa->nama,
                'dudi' => $dudi->nama_perusahaan,
                'nama_dudi' => $dudi->nama_perusahaan,
                'jam_masuk' => $jamMasuk->format('H:i'),
                'tanggal' => $currentTime->translatedFormat('d F Y'),
                'waktu' => $currentTime->format('H:i'),
                'status' => 'BELUM ABSEN',
            ]);

            // If phone is missing or invalid, record as skipped once and do not queue
            if ($normalizedPhone === null) {
                $log = WhatsAppLog::firstOrCreateSafe(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'penempatan_pkl_id' => $penempatan->id,
                        'siswa_id' => $siswa->id,
                        'user_id' => $user->id,
                        'recipient_phone' => $rawPhone ?: '-',
                        'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
                        'message_content' => $message,
                        'status' => WhatsAppLog::STATUS_SKIPPED,
                        'error_reason' => 'Nomor WhatsApp siswa tidak tersedia atau format tidak valid: "' . $rawPhone . '"',
                        'tanggal' => $dateStr,
                    ]
                );

                $skipped++;
                $reasons['invalid_or_missing_phone'] = ($reasons['invalid_or_missing_phone'] ?? 0) + 1;
                continue;
            }

            // Atomically create or fetch log using idempotency key
            $log = WhatsAppLog::firstOrCreateSafe(
                ['idempotency_key' => $idempotencyKey],
                [
                    'penempatan_pkl_id' => $penempatan->id,
                    'siswa_id' => $siswa->id,
                    'user_id' => $user->id,
                    'recipient_phone' => $normalizedPhone,
                    'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
                    'message_content' => $message,
                    'status' => WhatsAppLog::STATUS_PENDING,
                    'error_reason' => null,
                    'tanggal' => $dateStr,
                ]
            );

            // If log was already created earlier, suppress duplicate dispatch!
            // Queue retry lifecycle manages attempts; scheduler must not re-dispatch.
            if (!$log->wasRecentlyCreated) {
                $skipped++;
                $reasons['duplicate_suppressed'] = ($reasons['duplicate_suppressed'] ?? 0) + 1;
                continue;
            }

            // Dispatch to queue
            SendWhatsAppNotificationJob::dispatch($log->id);
            $queued++;
        }

        return [
            'candidates' => $candidates,
            'queued' => $queued,
            'skipped' => $skipped,
            'disabled' => false,
            'reasons' => $reasons,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function processLateNotifications(CarbonInterface $currentTime, bool $force = false): array
    {
        $settings = $this->getSettings();

        // Check if master switch and late notification switch are enabled
        if (!$settings['master_enabled'] || !$settings['late_enabled']) {
            return [
                'candidates' => 0,
                'queued' => 0,
                'skipped' => 0,
                'disabled' => true,
                'reasons' => ['feature_disabled' => 1],
            ];
        }

        $dateStr = $currentTime->toDateString();
        $penempatans = $this->getActivePlacementsForDate($dateStr);

        $candidates = 0;
        $queued = 0;
        $skipped = 0;
        $reasons = [];

        foreach ($penempatans as $penempatan) {
            $candidates++;
            $dudi = $penempatan->dudi;
            $siswa = $penempatan->siswa;

            if (!$dudi->status_aktif) {
                $skipped++;
                $reasons['dudi_inactive'] = ($reasons['dudi_inactive'] ?? 0) + 1;
                continue;
            }

            // Check if today is an operational day for this specific DUDI
            if (!$dudi->isHariOperasional($currentTime)) {
                $skipped++;
                $reasons['dudi_holiday'] = ($reasons['dudi_holiday'] ?? 0) + 1;
                continue;
            }

            // Determine DUDI entry time
            $jamMasuk = Carbon::parse($dudi->jam_masuk ?? '07:00:00', config('app.timezone'));

            // The late notification triggers right at or after jam_masuk
            // If not forced, it triggers when current time is >= jamMasuk and <= jamMasuk + 30 min, matching the hour:minute or scan
            if (!$force) {
                $currentMinutes = $currentTime->hour * 60 + $currentTime->minute;
                $entryMinutes = $jamMasuk->hour * 60 + $jamMasuk->minute;

                if ($currentMinutes < $entryMinutes || $currentMinutes > $entryMinutes + 30) {
                    $skipped++;
                    $reasons['not_late_window'] = ($reasons['not_late_window'] ?? 0) + 1;
                    continue;
                }
            }

            // Check if student has already checked in today
            $alreadyClockedIn = Absensi::where('penempatan_pkl_id', $penempatan->id)
                ->whereDate('tanggal', $dateStr)
                ->whereNotNull('jam_masuk')
                ->exists();

            if ($alreadyClockedIn) {
                $skipped++;
                $reasons['already_clocked_in'] = ($reasons['already_clocked_in'] ?? 0) + 1;
                continue;
            }

            // Check if student has an approved absence (izin / sakit)
            $hasApprovedLeave = PengajuanKetidakhadiran::where('penempatan_pkl_id', $penempatan->id)
                ->whereDate('tanggal', $dateStr)
                ->where('status', 'disetujui')
                ->exists();

            if ($hasApprovedLeave) {
                $skipped++;
                $reasons['approved_leave'] = ($reasons['approved_leave'] ?? 0) + 1;
                continue;
            }

            // Idempotency: generate unique key for late notification
            $idempotencyKey = WhatsAppLog::generateIdempotencyKey(
                (int) $siswa->id,
                $dateStr,
                WhatsAppLog::TYPE_ATTENDANCE_LATE
            );

            // Student phone number: prioritize User::$phone (Pengaturan Akun), fallback to Siswa::$no_telepon
            $user = $siswa->user;
            $rawPhone = trim((string) ($user->phone ?? $siswa->no_telepon ?? ''));
            $normalizedPhone = $this->normalizePhoneNumber($rawPhone);

            // Render message template
            $message = $this->renderTemplate($settings['late_template'], [
                'nama' => $siswa->nama,
                'nama_siswa' => $siswa->nama,
                'dudi' => $dudi->nama_perusahaan,
                'nama_dudi' => $dudi->nama_perusahaan,
                'jam_masuk' => $jamMasuk->format('H:i'),
                'tanggal' => $currentTime->translatedFormat('d F Y'),
                'waktu' => $currentTime->format('H:i'),
                'status' => 'TERLAMBAT',
            ]);

            // If phone is missing or invalid, record as skipped once and do not queue
            if ($normalizedPhone === null) {
                $log = WhatsAppLog::firstOrCreateSafe(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'penempatan_pkl_id' => $penempatan->id,
                        'siswa_id' => $siswa->id,
                        'user_id' => $user->id,
                        'recipient_phone' => $rawPhone ?: '-',
                        'message_type' => WhatsAppLog::TYPE_ATTENDANCE_LATE,
                        'message_content' => $message,
                        'status' => WhatsAppLog::STATUS_SKIPPED,
                        'error_reason' => 'Nomor WhatsApp siswa tidak tersedia atau format tidak valid: "' . $rawPhone . '"',
                        'tanggal' => $dateStr,
                    ]
                );

                $skipped++;
                $reasons['invalid_or_missing_phone'] = ($reasons['invalid_or_missing_phone'] ?? 0) + 1;
                continue;
            }

            // Atomically create or fetch log using idempotency key
            $log = WhatsAppLog::firstOrCreateSafe(
                ['idempotency_key' => $idempotencyKey],
                [
                    'penempatan_pkl_id' => $penempatan->id,
                    'siswa_id' => $siswa->id,
                    'user_id' => $user->id,
                    'recipient_phone' => $normalizedPhone,
                    'message_type' => WhatsAppLog::TYPE_ATTENDANCE_LATE,
                    'message_content' => $message,
                    'status' => WhatsAppLog::STATUS_PENDING,
                    'error_reason' => null,
                    'tanggal' => $dateStr,
                ]
            );

            // If log was already created earlier, suppress duplicate dispatch!
            // Queue retry lifecycle manages attempts; scheduler must not re-dispatch.
            if (!$log->wasRecentlyCreated) {
                $skipped++;
                $reasons['duplicate_suppressed'] = ($reasons['duplicate_suppressed'] ?? 0) + 1;
                continue;
            }

            // Dispatch to queue
            SendWhatsAppNotificationJob::dispatch($log->id);
            $queued++;
        }

        return [
            'candidates' => $candidates,
            'queued' => $queued,
            'skipped' => $skipped,
            'disabled' => false,
            'reasons' => $reasons,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function sendTestMessage(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        if ($normalizedPhone === null) {
            return [
                'success' => false,
                'status' => 'failed',
                'error' => 'Format nomor WhatsApp tidak valid. Gunakan format nomor seluler Indonesia seperti 08123456789 atau 628123456789.',
                'errorCode' => 'INVALID_PHONE',
            ];
        }

        $result = $this->executeDirectSend($normalizedPhone, $message);
        $errorCode = $result['errorCode'] ?? null;
        $isSuccess = $result['success'] === true;

        // User-friendly error message for administrative UI
        $userFriendlyError = null;
        if (!$isSuccess) {
            $userFriendlyError = match ($errorCode) {
                'NOT_ON_WHATSAPP' => 'Nomor tidak terdaftar di WhatsApp.',
                'WHATSAPP_NOT_CONNECTED' => 'Gateway WhatsApp sedang tidak terhubung. Silakan periksa koneksi WhatsApp.',
                'TIMEOUT' => 'Gateway WhatsApp mengalami batas waktu (timeout). Silakan coba lagi.',
                'INVALID_PHONE' => 'Format nomor WhatsApp tidak valid.',
                'INVALID_MESSAGE' => 'Pesan WhatsApp tidak boleh kosong.',
                'INVALID_PARAMETERS' => 'Parameter permintaan tidak valid.',
                default => $result['error'] ?? 'Terjadi kesalahan saat mengirim pesan via gateway.',
            };
        }

        // Record audit log for test message
        WhatsAppLog::create([
            'idempotency_key' => hash('sha256', 'test_msg:' . microtime(true) . ':' . $normalizedPhone),
            'penempatan_pkl_id' => null,
            'siswa_id' => null,
            'user_id' => auth()->id(),
            'recipient_phone' => $normalizedPhone,
            'message_type' => WhatsAppLog::TYPE_MANUAL_TEST,
            'message_content' => $message,
            'status' => $isSuccess ? WhatsAppLog::STATUS_SENT : WhatsAppLog::STATUS_FAILED,
            'error_reason' => $userFriendlyError ?: ($result['error'] ?? null),
            'response_payload' => $result,
            'tanggal' => Carbon::today(config('app.timezone')),
            'sent_at' => $isSuccess ? Carbon::now(config('app.timezone')) : null,
            'failed_at' => !$isSuccess ? Carbon::now(config('app.timezone')) : null,
        ]);

        return [
            'success' => $isSuccess,
            'status' => $isSuccess ? 'sent' : 'failed',
            'error' => $userFriendlyError,
            'errorCode' => $errorCode,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings(): array
    {
        $master = Setting::where('key', 'wa_master_enabled')->value('value');
        $reminder = Setting::where('key', 'wa_reminder_enabled')->value('value');
        $late = Setting::where('key', 'wa_late_enabled')->value('value');
        $offsetMinutes = Setting::where('key', 'wa_reminder_offset_minutes')->value('value');
        $reminderTpl = Setting::where('key', 'wa_reminder_template')->value('value');
        $lateTpl = Setting::where('key', 'wa_late_template')->value('value');
        $gatewayUrl = Setting::where('key', 'wa_gateway_url')->value('value');
        $apiKey = Setting::where('key', 'wa_gateway_api_key')->value('value');

        // Safe defaults: all disabled by default
        $masterEnabled = $master !== null ? filter_var($master, FILTER_VALIDATE_BOOLEAN) : false;
        $reminderEnabled = $reminder !== null ? filter_var($reminder, FILTER_VALIDATE_BOOLEAN) : false;
        $lateEnabled = $late !== null ? filter_var($late, FILTER_VALIDATE_BOOLEAN) : false;

        return [
            'master_enabled' => $masterEnabled,
            'reminder_enabled' => $reminderEnabled,
            'late_enabled' => $lateEnabled,
            'enabled' => $masterEnabled, // Alias for backward compatibility
            'offset_minutes' => $offsetMinutes !== null ? (int) $offsetMinutes : self::DEFAULT_OFFSET_MINUTES,
            'reminder_template' => !empty($reminderTpl) ? (string) $reminderTpl : self::DEFAULT_REMINDER_TEMPLATE,
            'late_template' => !empty($lateTpl) ? (string) $lateTpl : self::DEFAULT_LATE_TEMPLATE,
            'gateway_url' => !empty($gatewayUrl) ? (string) $gatewayUrl : (string) config('services.whatsapp.gateway_url', 'http://127.0.0.1:3000'),
            'api_key' => $apiKey !== null ? (string) $apiKey : config('services.whatsapp.api_key'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function updateSettings(array $data): void
    {
        if (array_key_exists('master_enabled', $data)) {
            Setting::updateOrCreate(
                ['key' => 'wa_master_enabled'],
                [
                    'value' => $data['master_enabled'] ? '1' : '0',
                    'type' => 'boolean',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (array_key_exists('reminder_enabled', $data)) {
            Setting::updateOrCreate(
                ['key' => 'wa_reminder_enabled'],
                [
                    'value' => $data['reminder_enabled'] ? '1' : '0',
                    'type' => 'boolean',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (array_key_exists('late_enabled', $data)) {
            Setting::updateOrCreate(
                ['key' => 'wa_late_enabled'],
                [
                    'value' => $data['late_enabled'] ? '1' : '0',
                    'type' => 'boolean',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (isset($data['offset_minutes'])) {
            Setting::updateOrCreate(
                ['key' => 'wa_reminder_offset_minutes'],
                [
                    'value' => (string) max(1, min(60, (int) $data['offset_minutes'])),
                    'type' => 'integer',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (isset($data['reminder_template'])) {
            Setting::updateOrCreate(
                ['key' => 'wa_reminder_template'],
                [
                    'value' => (string) $data['reminder_template'],
                    'type' => 'string',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (isset($data['late_template'])) {
            Setting::updateOrCreate(
                ['key' => 'wa_late_template'],
                [
                    'value' => (string) $data['late_template'],
                    'type' => 'string',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (isset($data['gateway_url'])) {
            Setting::updateOrCreate(
                ['key' => 'wa_gateway_url'],
                [
                    'value' => (string) $data['gateway_url'],
                    'type' => 'string',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }

        if (array_key_exists('api_key', $data)) {
            Setting::updateOrCreate(
                ['key' => 'wa_gateway_api_key'],
                [
                    'value' => $data['api_key'] ? (string) $data['api_key'] : null,
                    'type' => 'string',
                    'group_name' => 'whatsapp',
                    'is_public' => false,
                ]
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function disconnectGateway(): array
    {
        $settings = $this->getSettings();
        $gatewayUrl = rtrim($settings['gateway_url'], '/');
        $apiKey = $settings['api_key'] ?? config('services.whatsapp.api_key');

        try {
            $req = Http::timeout(10);
            if (!empty($apiKey)) {
                $req = $req->withHeaders([
                    'x-api-key' => $apiKey,
                    'Authorization' => "Bearer {$apiKey}",
                ]);
            }

            $response = $req->post("{$gatewayUrl}/api/disconnect");
            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Sesi WhatsApp pada gateway berhasil diputuskan.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Gagal memutuskan sesi WhatsApp: HTTP ' . $response->status(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi gateway: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function normalizePhoneNumber(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $clean = trim($phone);
        if ($clean === '') {
            return null;
        }

        // Strip everything except digits
        $digitsOnly = preg_replace('/[^0-9]/', '', $clean);

        if ($digitsOnly === null || $digitsOnly === '') {
            return null;
        }

        // Convert leading 08... or 8... to canonical 628...
        if (str_starts_with($digitsOnly, '08')) {
            $digitsOnly = '628' . substr($digitsOnly, 2);
        } elseif (str_starts_with($digitsOnly, '8')) {
            $digitsOnly = '62' . $digitsOnly;
        }

        // Must start with Indonesian mobile prefix '628'
        if (!str_starts_with($digitsOnly, '628')) {
            return null;
        }

        // Validate length: Indonesian mobile WhatsApp numbers are 10 to 16 digits
        $len = strlen($digitsOnly);
        if ($len < 10 || $len > 16) {
            return null;
        }

        return $digitsOnly;
    }

    /**
     * {@inheritDoc}
     */
    public function renderTemplate(string $template, array $placeholders): string
    {
        $search = [];
        $replace = [];

        foreach ($placeholders as $key => $val) {
            // Support both single brace {key} and double brace {{key}}
            $search[] = '{' . $key . '}';
            $replace[] = (string) $val;

            $search[] = '{{' . $key . '}}';
            $replace[] = (string) $val;
        }

        $rendered = str_replace($search, $replace, $template);

        // Safely strip any remaining unrendered placeholder brackets so no raw syntax leaks
        return preg_replace('/\{\{[a-zA-Z0-9_]+\}\}|\{[a-zA-Z0-9_]+\}/', '', $rendered) ?? $rendered;
    }

    /**
     * Helper to fetch active placements for a date with relations.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, PenempatanPKL>
     */
    private function getActivePlacementsForDate(string $dateStr)
    {
        return PenempatanPKL::where('status', 'aktif')
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $dateStr);
            })
            ->where(function ($q) use ($dateStr) {
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $dateStr);
            })
            ->with(['siswa.user', 'dudi'])
            ->get();
    }
}
