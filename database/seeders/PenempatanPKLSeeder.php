<?php

namespace Database\Seeders;

use App\Models\PenempatanPKL;
use Illuminate\Database\Seeder;

class PenempatanPKLSeeder extends Seeder
{
    public function run(): void
    {
        PenempatanPKL::factory(5)->create();
    }
}
