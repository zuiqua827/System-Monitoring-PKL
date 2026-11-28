<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;

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

    public function test_database_seeder_does_not_delete_existing_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $initialRoleCount = \Spatie\Permission\Models\Role::count();
        $initialUserCount = \App\Models\User::count();
        $initialJurusanCount = \App\Models\Jurusan::count();
        $initialKelasCount = \App\Models\Kelas::count();

        // Run again to ensure it doesn't duplicate or delete existing data destructively
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals($initialRoleCount, \Spatie\Permission\Models\Role::count(), 'Role count changed after second seeding');
        $this->assertEquals($initialUserCount, \App\Models\User::count(), 'User count changed after second seeding');
        $this->assertEquals($initialJurusanCount, \App\Models\Jurusan::count(), 'Jurusan data count changed after second seeding');
        $this->assertEquals($initialKelasCount, \App\Models\Kelas::count(), 'Kelas data count changed after second seeding');
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
