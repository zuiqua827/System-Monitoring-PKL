<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole(\App\Enums\UserRole::SUPER_ADMIN->value);
        $this->admin = $admin;
    }

    public function test_all_33_classes_exist_in_database(): void
    {
        $this->assertEquals(33, Kelas::count());
        $this->assertEquals(11, Kelas::where('tingkat', 10)->count());
        $this->assertEquals(11, Kelas::where('tingkat', 11)->count());
        $this->assertEquals(11, Kelas::where('tingkat', 12)->count());
    }

    public function test_admin_can_view_kelas_index_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.kelas.index'));

        $response->assertStatus(200);
        $response->assertSee('Data Kelas');
        $response->assertSee('Semua Tingkat');
    }

    public function test_admin_can_filter_kelas_by_tingkat_10(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.kelas.index', ['tingkat' => 10, 'per_page' => 50]));

        $response->assertStatus(200);
        $response->assertSee('X AKL 1');
        $response->assertSee('X PPLG 1');
        $response->assertDontSee('XII AKL 1');
    }

    public function test_admin_can_filter_kelas_by_tingkat_11(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.kelas.index', ['tingkat' => 11, 'per_page' => 50]));

        $response->assertStatus(200);
        $response->assertSee('XI AKL 1');
        $response->assertSee('XI PPLG 1');
        $response->assertDontSee('XII AKL 1');
    }

    public function test_admin_can_filter_kelas_by_tingkat_12(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.kelas.index', ['tingkat' => 12, 'per_page' => 50]));

        $response->assertStatus(200);
        $response->assertSee('XII AKL 1');
        $response->assertSee('XII PPLG 1');
        $response->assertDontSee('X AKL 1');
    }

    public function test_admin_can_create_new_kelas_with_tingkat_10_11_12(): void
    {
        $jurusan = Jurusan::first();

        $response10 = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'X BARU 1',
            'tingkat' => 10,
            'tahun_ajaran' => '2025/2026',
        ]);

        $response10->assertRedirect(route('admin.kelas.index'));
        $this->assertDatabaseHas('kelas', [
            'nama' => 'X BARU 1',
            'tingkat' => 10,
        ]);

        $response11 = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'XI BARU 1',
            'tingkat' => 11,
            'tahun_ajaran' => '2025/2026',
        ]);

        $response11->assertRedirect(route('admin.kelas.index'));
        $this->assertDatabaseHas('kelas', [
            'nama' => 'XI BARU 1',
            'tingkat' => 11,
        ]);
    }

    public function test_cannot_create_duplicate_kelas(): void
    {
        $kelasExisting = Kelas::first();

        $response = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $kelasExisting->jurusan_id,
            'nama' => $kelasExisting->nama,
            'tingkat' => $kelasExisting->tingkat,
            'tahun_ajaran' => $kelasExisting->tahun_ajaran,
        ]);

        $response->assertSessionHasErrors(['nama']);
    }

    public function test_cannot_create_kelas_with_invalid_tingkat(): void
    {
        $jurusan = Jurusan::first();

        $response = $this->actingAs($this->admin)->post(route('admin.kelas.store'), [
            'jurusan_id' => $jurusan->id,
            'nama' => 'IX REGULER',
            'tingkat' => 9,
            'tahun_ajaran' => '2025/2026',
        ]);

        $response->assertSessionHasErrors(['tingkat']);
    }

    public function test_admin_can_update_kelas(): void
    {
        $kelas = Kelas::where('nama', 'X AKL 1')->first();

        $response = $this->actingAs($this->admin)->put(route('admin.kelas.update', $kelas->id), [
            'jurusan_id' => $kelas->jurusan_id,
            'nama' => 'X AKL 1 UPDATED',
            'tingkat' => 10,
            'tahun_ajaran' => '2025/2026',
        ]);

        $response->assertRedirect(route('admin.kelas.index'));
        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'nama' => 'X AKL 1 UPDATED',
        ]);
    }
}
