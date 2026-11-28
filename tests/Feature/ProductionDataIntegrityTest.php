<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use Database\Seeders\CleanupDummyDataSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_does_not_seed_dummy_siswa(): void
    {
        $this->seed(DatabaseSeeder::class);

        $dummyCount = Siswa::where('nama', 'like', '% Jr.%')
            ->orWhere('nama', 'like', '% Sr.%')
            ->orWhere('nama', 'like', '%Prof.%')
            ->count();

        $this->assertEquals(0, $dummyCount, 'DatabaseSeeder seharusnya tidak pernah memasukkan data dummy siswa!');
    }

    public function test_cleanup_dummy_data_seeder_removes_dummies_safely(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Inject a dummy student manually
        $kelas = Kelas::first();
        if (!$kelas) {
            $this->markTestSkipped('No classes available.');
        }
        
        $dummy = Siswa::factory()->create([
            'nama' => 'John Doe Jr.',
            'class_id' => $kelas->id,
        ]);

        $this->assertDatabaseHas('siswa', ['id' => $dummy->id, 'deleted_at' => null]);

        $this->seed(CleanupDummyDataSeeder::class);

        $this->assertSoftDeleted('siswa', ['id' => $dummy->id]);
    }

    public function test_classes_x_xi_xii_are_properly_seeded(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Kelas::where('tingkat', 10)->exists(), 'Kelas 10 harus tersedia');
        $this->assertTrue(Kelas::where('tingkat', 11)->exists(), 'Kelas 11 harus tersedia');
        $this->assertTrue(Kelas::where('tingkat', 12)->exists(), 'Kelas 12 harus tersedia');
        
        $this->assertGreaterThanOrEqual(11, Kelas::where('tingkat', 10)->count());
        $this->assertGreaterThanOrEqual(11, Kelas::where('tingkat', 11)->count());
        $this->assertGreaterThanOrEqual(11, Kelas::where('tingkat', 12)->count());
    }
}
