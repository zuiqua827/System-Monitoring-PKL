<?php

namespace Database\Seeders;

use App\Models\Dudi;
use Illuminate\Database\Seeder;

class DudiSeeder extends Seeder
{
    public function run(): void
    {
        Dudi::factory(5)->create();
    }
}
