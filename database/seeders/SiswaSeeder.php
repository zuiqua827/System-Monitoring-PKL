<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed Siswa records.
 *
 * NOTE: In production the real students come from the SiPintu sync. This
 * seeder is only used for local development / testing. Every created student
 * gets a User account with the default password "password" (hashed) and
 * must_change_password = false.
 */
class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $kelasId = \App\Models\Kelas::query()->value('id');

        Siswa::factory(10)->create()->each(function (Siswa $siswa) use ($kelasId): void {
            $user = User::create([
                'name' => $siswa->nama,
                'email' => $siswa->email ?? $siswa->nis.'@smk1bangsri.sch.id',
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]);

            $user->assignRole(UserRole::SISWA->value);

            $siswa->forceFill([
                'user_id' => $user->id,
                'class_id' => $kelasId,
            ])->save();
        });
    }
}
