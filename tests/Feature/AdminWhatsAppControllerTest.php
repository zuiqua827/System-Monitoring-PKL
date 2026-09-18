<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\User;
use App\Models\WhatsAppLog;
use App\Services\Interfaces\WhatsAppServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWhatsAppControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $siswaUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole(UserRole::SUPER_ADMIN->value);

        $this->siswaUser = User::factory()->create();
        $this->siswaUser->assignRole(UserRole::SISWA->value);
    }

    public function test_guest_is_redirected_to_login_when_accessing_whatsapp_dashboard(): void
    {
        $response = $this->get('/admin/whatsapp');
        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_whatsapp_dashboard(): void
    {
        $response = $this->actingAs($this->siswaUser)->get('/admin/whatsapp');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_whatsapp_dashboard(): void
    {
        Http::fake([
            '*/api/status' => Http::response([
                'status' => 'connected',
                'phone' => '6281234567890',
                'name' => 'Gateway SIMONGAN',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/admin/whatsapp');

        $response->assertStatus(200);
        $response->assertSee('WhatsApp Gateway & Notifikasi');
        $response->assertSee('Master WhatsApp Notification');
        $response->assertSee('Pengingat Absensi Masuk');
        $response->assertSee('Notifikasi Keterlambatan');
    }

    public function test_super_admin_can_access_whatsapp_logs(): void
    {
        WhatsAppLog::create([
            'idempotency_key' => 'test-key-123',
            'recipient_phone' => '6281234567890',
            'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
            'message_content' => 'Halo Budi, pengingat absensi',
            'status' => WhatsAppLog::STATUS_SENT,
            'tanggal' => '2026-09-18',
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/admin/whatsapp/logs');

        $response->assertStatus(200);
        $response->assertSee('Riwayat Log WhatsApp');
        $response->assertSee('6281234567890');
        $response->assertSee('Reminder H-5');
    }

    public function test_super_admin_can_update_whatsapp_settings(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/whatsapp/settings', [
            'master_enabled' => '1',
            'reminder_enabled' => '1',
            'late_enabled' => '1',
            'offset_minutes' => 10,
            'reminder_template' => 'Peringatan kustom untuk {nama}',
            'late_template' => 'Pesan terlambat kustom untuk {nama}',
            'gateway_url' => 'http://127.0.0.1:3000',
            'api_key' => 'secret-key-test',
        ]);

        $response->assertRedirect('/admin/whatsapp');
        $response->assertSessionHas('success');

        $waService = app(WhatsAppServiceInterface::class);
        $settings = $waService->getSettings();

        $this->assertTrue($settings['master_enabled']);
        $this->assertTrue($settings['reminder_enabled']);
        $this->assertTrue($settings['late_enabled']);
        $this->assertSame(10, $settings['offset_minutes']);
        $this->assertSame('Peringatan kustom untuk {nama}', $settings['reminder_template']);
        $this->assertSame('Pesan terlambat kustom untuk {nama}', $settings['late_template']);
        $this->assertSame('secret-key-test', $settings['api_key']);
    }

    public function test_super_admin_can_send_test_message(): void
    {
        Http::fake([
            '*/api/send' => Http::response([
                'success' => true,
                'messageId' => 'MSG-12345',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->post('/admin/whatsapp/test-send', [
            'phone' => '081234567890',
            'message' => 'Tes pesan dari Super Admin',
        ]);

        $response->assertRedirect('/admin/whatsapp');
        $response->assertSessionHas('success');

        $log = WhatsAppLog::where('message_type', WhatsAppLog::TYPE_MANUAL_TEST)->first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_SENT, $log->status);
        $this->assertSame('6281234567890', $log->recipient_phone);
    }

    public function test_gateway_offline_in_test_send_fails_gracefully_without_breaking_application(): void
    {
        Http::fake([
            '*/api/send' => Http::response([
                'success' => false,
                'error' => 'Gateway connection timeout',
            ], 500),
        ]);

        $response = $this->actingAs($this->superAdmin)->post('/admin/whatsapp/test-send', [
            'phone' => '081234567890',
            'message' => 'Tes pesan saat gateway down',
        ]);

        $response->assertRedirect('/admin/whatsapp');
        $response->assertSessionHas('error');

        $log = WhatsAppLog::where('message_type', WhatsAppLog::TYPE_MANUAL_TEST)->first();
        $this->assertNotNull($log);
        $this->assertSame(WhatsAppLog::STATUS_FAILED, $log->status);
    }

    public function test_status_ajax_returns_json_response(): void
    {
        Http::fake([
            '*/api/status' => Http::response([
                'status' => 'qr_ready',
                'phone' => null,
                'name' => null,
                'qr' => 'data:image/png;base64,sampleqr',
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/admin/whatsapp/status-ajax');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'qr_ready',
            'qr' => 'data:image/png;base64,sampleqr',
        ]);
    }

    public function test_send_whatsapp_notification_job_updates_log_status_on_success(): void
    {
        Http::fake([
            '*/api/send' => Http::response([
                'success' => true,
                'messageId' => 'MSG-999',
            ], 200),
        ]);

        $waService = app(WhatsAppServiceInterface::class);
        $waService->updateSettings(['master_enabled' => true]);

        $log = WhatsAppLog::create([
            'idempotency_key' => 'job-test-key-1',
            'recipient_phone' => '6281234567890',
            'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
            'message_content' => 'Pengingat absensi penting',
            'status' => WhatsAppLog::STATUS_PENDING,
            'tanggal' => '2026-09-18',
        ]);

        $job = new SendWhatsAppNotificationJob($log->id);
        $job->handle($waService);

        $log->refresh();
        $this->assertSame(WhatsAppLog::STATUS_SENT, $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertNull($log->error_reason);
    }

    public function test_send_whatsapp_notification_job_marks_failed_on_permanent_client_error(): void
    {
        Http::fake([
            '*/api/send' => Http::response([
                'success' => false,
                'error' => 'Nomor WhatsApp tidak terdaftar',
            ], 400),
        ]);

        $waService = app(WhatsAppServiceInterface::class);
        $waService->updateSettings(['master_enabled' => true]);

        $log = WhatsAppLog::create([
            'idempotency_key' => 'job-test-key-2',
            'recipient_phone' => '6281234567890',
            'message_type' => WhatsAppLog::TYPE_ATTENDANCE_REMINDER,
            'message_content' => 'Pengingat absensi',
            'status' => WhatsAppLog::STATUS_PENDING,
            'tanggal' => '2026-09-18',
        ]);

        $job = new SendWhatsAppNotificationJob($log->id);
        // Permanent error should not throw exception (no useless queue retry)
        $job->handle($waService);

        $log->refresh();
        $this->assertSame(WhatsAppLog::STATUS_FAILED, $log->status);
        $this->assertStringContainsString('tidak terdaftar', (string) $log->error_reason);
    }
}
