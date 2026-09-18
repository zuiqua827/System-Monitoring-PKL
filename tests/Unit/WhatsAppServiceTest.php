<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\WhatsAppLog;
use App\Services\WhatsAppService;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
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
