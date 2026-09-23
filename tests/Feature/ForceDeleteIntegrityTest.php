<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Interfaces\JurusanServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SuperAdminSeeder;

class ForceDeleteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private JurusanServiceInterface $jurusanService;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup base roles needed by models
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->jurusanService = app(JurusanServiceInterface::class);
    }

    public function test_force_delete_blocked_by_active_child(): void
    {
        $jurusan = Jurusan::factory()->create(['nama' => 'Teknik Komputer', 'kode' => 'TKJ']);
        $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id, 'nama' => '11-TKJ-1']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('masih memiliki 1 kelas aktif');

        $this->jurusanService->forceDelete($jurusan);
    }

    public function test_force_delete_cascades_soft_deleted_child(): void
    {
        $jurusan = Jurusan::factory()->create(['nama' => 'Teknik Komputer', 'kode' => 'TKJ']);
        $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id, 'nama' => '11-TKJ-1']);
        
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create([
            'class_id' => $kelas->id,
            'user_id' => $user->id,
            'nis' => '12345'
        ]);

        // Soft delete the hierarchy
        $siswa->delete();
        $kelas->delete();

        // Should succeed and cascade
        $result = $this->jurusanService->forceDelete($jurusan);
        $this->assertTrue($result);

        // Verify cascading deletes
        $this->assertDatabaseMissing('siswa', ['id' => $siswa->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
        $this->assertDatabaseMissing('jurusan', ['id' => $jurusan->id]);
    }

    public function test_force_delete_mixed_children_prioritizes_active_blocker(): void
    {
        $jurusan = Jurusan::factory()->create(['nama' => 'Teknik Komputer', 'kode' => 'TKJ']);
        
        $kelas1 = Kelas::factory()->create(['jurusan_id' => $jurusan->id, 'nama' => '11-TKJ-1']);
        $kelas2 = Kelas::factory()->create(['jurusan_id' => $jurusan->id, 'nama' => '11-TKJ-2']);

        // Soft delete ONLY one kelas
        $kelas2->delete();

        // Should block on the active one
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('masih memiliki 1 kelas aktif');

        $this->jurusanService->forceDelete($jurusan);
    }
    
    public function test_force_delete_maintains_data_isolation(): void
    {
        $jurusan = Jurusan::factory()->create();
        $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id]);
        $kelas->delete();

        $jurusanLain = Jurusan::factory()->create();
        $kelasLain = Kelas::factory()->create(['jurusan_id' => $jurusanLain->id]);

        $this->jurusanService->forceDelete($jurusan);

        // Ensure the other data is still intact
        $this->assertDatabaseHas('jurusan', ['id' => $jurusanLain->id]);
        $this->assertDatabaseHas('kelas', ['id' => $kelasLain->id]);
    }
    
    public function test_transaction_rolls_back_on_deep_active_child(): void
    {
        $jurusan = Jurusan::factory()->create();
        
        // Kelas is soft-deleted, but contains an active siswa
        $kelas = Kelas::factory()->create(['jurusan_id' => $jurusan->id]);
        $kelas->delete();
        
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['class_id' => $kelas->id, 'user_id' => $user->id]);

        try {
            $this->jurusanService->forceDelete($jurusan);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('masih memiliki 1 siswa aktif', $e->getMessage());
        }

        // Verify transaction rolled back, so nothing was force deleted
        // The soft-deleted kelas should still exist in the database as soft-deleted
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'deleted_at' => $kelas->deleted_at]);
        $this->assertDatabaseHas('siswa', ['id' => $siswa->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
