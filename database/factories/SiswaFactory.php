<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Siswa>
 */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $nis = fake()->unique()->numerify('########');
        $tanggalLahir = fake()->dateTimeBetween('-19 years', '-15 years');

        return [
            'user_id' => User::factory()->state([
                'name' => $name,
                'email' => $nis.'@siswa.monitoringpkl.test',
                'password' => Hash::make($tanggalLahir->format('Ymd')),
                'must_change_password' => true,
            ]),
            'class_id' => Kelas::factory(),
            'nis' => $nis,
            'nisn' => fake()->unique()->numerify('##########'),
            'nama' => $name,
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'tanggal_lahir' => $tanggalLahir->format('Y-m-d'),
            'no_telepon' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
        ];
    }
}
