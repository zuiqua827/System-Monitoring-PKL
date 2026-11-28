<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhatsAppService();
    }

    public function test_normalizes_08_phone_number_to_628(): void
    {
        $normalized = $this->service->normalizePhoneNumber('081234567890');
        $this->assertSame('6281234567890', $normalized);
    }

    public function test_normalizes_plus_628_phone_number_to_628(): void
    {
        $normalized = $this->service->normalizePhoneNumber('+6281234567890');
        $this->assertSame('6281234567890', $normalized);
    }

    public function test_normalizes_628_phone_number_directly(): void
    {
        $normalized = $this->service->normalizePhoneNumber('6281234567890');
        $this->assertSame('6281234567890', $normalized);
    }

    public function test_normalizes_phone_number_starting_with_8(): void
    {
        $normalized = $this->service->normalizePhoneNumber('81234567890');
        $this->assertSame('6281234567890', $normalized);
    }

    public function test_strips_spaces_dashes_and_special_characters(): void
    {
        $normalized = $this->service->normalizePhoneNumber(' 0812-3456-7890 ');
        $this->assertSame('6281234567890', $normalized);
    }

    public function test_returns_null_for_empty_or_null_phone_number(): void
    {
        $this->assertNull($this->service->normalizePhoneNumber(null));
        $this->assertNull($this->service->normalizePhoneNumber(''));
        $this->assertNull($this->service->normalizePhoneNumber('   '));
    }

    public function test_returns_null_for_invalid_phone_number(): void
    {
        $this->assertNull($this->service->normalizePhoneNumber('abcdefghijk'));
        $this->assertNull($this->service->normalizePhoneNumber('0812')); // too short (< 10 digits)
        $this->assertNull($this->service->normalizePhoneNumber('0812345678901234567890')); // too long (> 16 digits)
    }

    public function test_renders_template_with_single_and_double_brace_placeholders(): void
    {
        $template = "Halo {nama}, Anda memiliki jadwal di {dudi} pada {jam_masuk} tanggal {tanggal}. Status: {status}.";
        $placeholders = [
            'nama' => 'Budi Santoso',
            'dudi' => 'PT Teknologi Unggul',
            'jam_masuk' => '07:30',
            'tanggal' => '18 September 2026',
            'status' => 'BELUM ABSEN',
        ];

        $rendered = $this->service->renderTemplate($template, $placeholders);

        $this->assertStringContainsString('Halo Budi Santoso', $rendered);
        $this->assertStringContainsString('di PT Teknologi Unggul', $rendered);
        $this->assertStringContainsString('pada 07:30', $rendered);
        $this->assertStringContainsString('tanggal 18 September 2026', $rendered);
        $this->assertStringContainsString('Status: BELUM ABSEN', $rendered);
    }

    public function test_safely_removes_unrendered_placeholder_brackets_without_exception(): void
    {
        $template = "Pemberitahuan untuk {nama}: {unknown_variable}";
        $rendered = $this->service->renderTemplate($template, ['nama' => 'Ahmad']);

        $this->assertSame('Pemberitahuan untuk Ahmad: ', $rendered);
    }

    public function test_normalizes_various_phone_number_formats_correctly(): void
    {
        // A. 08123456789 -> 628123456789
        $this->assertSame('628123456789', $this->service->normalizePhoneNumber('08123456789'));

        // B. 0812 3456 789 -> 628123456789
        $this->assertSame('628123456789', $this->service->normalizePhoneNumber('0812 3456 789'));

        // C. 0812-3456-789 -> 628123456789
        $this->assertSame('628123456789', $this->service->normalizePhoneNumber('0812-3456-789'));

        // D. +628123456789 -> 628123456789
        $this->assertSame('628123456789', $this->service->normalizePhoneNumber('+628123456789'));

        // E. 628123456789 -> 628123456789
        $this->assertSame('628123456789', $this->service->normalizePhoneNumber('628123456789'));

        // F. 8123456789 -> 628123456789
        $this->assertSame('628123456789', $this->service->normalizePhoneNumber('8123456789'));
    }

    public function test_rejects_non_indonesian_or_landline_numbers(): void
    {
        // Random non-mobile / non-Indonesian numbers should be rejected as null
        $this->assertNull($this->service->normalizePhoneNumber('1234567890'));
        $this->assertNull($this->service->normalizePhoneNumber('0211234567')); // Jakarta landline prefix
        $this->assertNull($this->service->normalizePhoneNumber('+14155552671')); // US number
    }

    public function test_execute_direct_send_success_parses_message_id_and_error_code(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/send' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'messageId' => 'MSG-ABC-123',
                'timestamp' => 1727670000,
            ], 200),
        ]);

        $result = $this->service->executeDirectSend('081234567890', 'Pesan uji coba');

        $this->assertTrue($result['success']);
        $this->assertSame('MSG-ABC-123', $result['messageId']);
        $this->assertNull($result['errorCode']);
        $this->assertFalse($result['is_permanent']);
    }

    public function test_execute_direct_send_permanent_error_not_on_whatsapp(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/send' => \Illuminate\Support\Facades\Http::response([
                'success' => false,
                'error' => 'Nomor tidak terdaftar WhatsApp.',
                'errorCode' => 'NOT_ON_WHATSAPP',
                'isPermanent' => true,
            ], 422),
        ]);

        $result = $this->service->executeDirectSend('081234567890', 'Pesan');

        $this->assertFalse($result['success']);
        $this->assertSame('NOT_ON_WHATSAPP', $result['errorCode']);
        $this->assertTrue($result['is_permanent']);
    }

    public function test_execute_direct_send_transient_error_whatsapp_not_connected(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/send' => \Illuminate\Support\Facades\Http::response([
                'success' => false,
                'error' => 'WhatsApp gateway disconnected',
                'errorCode' => 'WHATSAPP_NOT_CONNECTED',
                'isPermanent' => false,
            ], 503),
        ]);

        $result = $this->service->executeDirectSend('081234567890', 'Pesan');

        $this->assertFalse($result['success']);
        $this->assertSame('WHATSAPP_NOT_CONNECTED', $result['errorCode']);
        $this->assertFalse($result['is_permanent']);
    }

    public function test_execute_direct_send_transient_error_gateway_timeout(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/send' => \Illuminate\Support\Facades\Http::response([
                'success' => false,
                'error' => 'Gateway request timed out',
                'errorCode' => 'TIMEOUT',
                'isPermanent' => false,
            ], 504),
        ]);

        $result = $this->service->executeDirectSend('081234567890', 'Pesan');

        $this->assertFalse($result['success']);
        $this->assertSame('TIMEOUT', $result['errorCode']);
        $this->assertFalse($result['is_permanent']);
    }

    public function test_generates_deterministic_idempotency_key(): void
    {
        $key1 = WhatsAppLog::generateIdempotencyKey(101, '2026-09-18', WhatsAppLog::TYPE_ATTENDANCE_REMINDER);
        $key2 = WhatsAppLog::generateIdempotencyKey(101, '2026-09-18', WhatsAppLog::TYPE_ATTENDANCE_REMINDER);
        $key3 = WhatsAppLog::generateIdempotencyKey(102, '2026-09-18', WhatsAppLog::TYPE_ATTENDANCE_REMINDER);
        $key4 = WhatsAppLog::generateIdempotencyKey(101, '2026-09-18', WhatsAppLog::TYPE_ATTENDANCE_LATE);

        $this->assertSame($key1, $key2);
        $this->assertNotSame($key1, $key3);
        $this->assertNotSame($key1, $key4);
        $this->assertSame(64, strlen($key1));
    }
}
