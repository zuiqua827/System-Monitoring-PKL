<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountAvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create([
            'email' => 'user_test@example.com',
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole(UserRole::SUPER_ADMIN->value);

        Storage::fake('public');
    }

    /**
     * Test 1: Account settings page loads successfully.
     */
    public function test_account_page_loads_with_initials_when_no_avatar(): void
    {
        $response = $this->actingAs($this->user)->get(route('account.index'));

        $response->assertOk();
        $response->assertSee('Unggah Foto Profil');
        $response->assertSee('Simpan Foto');
        $response->assertSee($this->user->initials());
    }

    private function createFakeImage(string $name = 'profile.png', string $mime = 'image/png', int $kb = 100): UploadedFile
    {
        // 1x1 PNG binary data that satisfies finfo/mime_content_type without requiring GD extension
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $content = base64_decode($pngBase64);
        if ($kb > 1) {
            $content .= str_repeat("\0", ($kb - 1) * 1024);
        }

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /**
     * Test 2: Upload avatar via JSON (AJAX) returns success with avatar_url.
     */
    public function test_upload_avatar_via_json_request_succeeds(): void
    {
        $file = $this->createFakeImage('profile.png', 'image/png');

        $response = $this->actingAs($this->user)
            ->postJson(route('account.upload-avatar'), [
                'avatar' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'avatar_url',
        ]);
        $response->assertJson([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
        ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->avatar);
        Storage::disk('public')->assertExists($this->user->avatar);
    }

    /**
     * Test 3: Upload avatar via standard form redirect.
     */
    public function test_upload_avatar_via_form_redirect_succeeds(): void
    {
        $file = $this->createFakeImage('profile.png', 'image/png');

        $response = $this->actingAs($this->user)
            ->post(route('account.upload-avatar'), [
                'avatar' => $file,
            ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('success', 'Foto profil berhasil diperbarui.');

        $this->user->refresh();
        $this->assertNotNull($this->user->avatar);
        Storage::disk('public')->assertExists($this->user->avatar);
    }

    /**
     * Test 4: Uploading file larger than 2MB is rejected by validation.
     */
    public function test_upload_avatar_exceeding_2mb_is_rejected(): void
    {
        // 2049 KB > 2048 KB limit
        $file = UploadedFile::fake()->create('large.jpg', 2500, 'image/jpeg');

        $response = $this->actingAs($this->user)
            ->postJson(route('account.upload-avatar'), [
                'avatar' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar']);
    }

    /**
     * Test 5: Uploading non-image format is rejected.
     */
    public function test_upload_avatar_invalid_format_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson(route('account.upload-avatar'), [
                'avatar' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['avatar']);
    }

    /**
     * Test 6: Delete avatar removes file and sets user avatar to null.
     */
    public function test_delete_avatar_succeeds(): void
    {
        // Upload first
        $file = $this->createFakeImage('avatar.png', 'image/png');
        $this->actingAs($this->user)->post(route('account.upload-avatar'), ['avatar' => $file]);

        $this->user->refresh();
        $storedAvatar = $this->user->avatar;
        $this->assertNotNull($storedAvatar);
        Storage::disk('public')->assertExists($storedAvatar);

        // Delete
        $response = $this->actingAs($this->user)
            ->delete(route('account.delete-avatar'));

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHas('success', 'Foto profil berhasil dihapus.');

        $this->user->refresh();
        $this->assertNull($this->user->avatar);
        Storage::disk('public')->assertMissing($storedAvatar);
    }
}
