<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seed Guru records.
 *
 * NOTE: In production the real teachers come from the SiPintu sync
 * (app/Services/SiPintuService::syncTeachers). No dummy teachers are
 * created here.
 */
class GuruSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('GuruSeeder: Tidak ada data dummy guru. Guru diisi melalui sinkronisasi SiPintu.');
    }
}
