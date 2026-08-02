<?php

namespace Database\Seeders;

use App\Models\PeriodePKL;
use Illuminate\Database\Seeder;

class PeriodePKLSeeder extends Seeder
{
    public function run(): void
    {
        PeriodePKL::factory(2)->create();
    }
}
