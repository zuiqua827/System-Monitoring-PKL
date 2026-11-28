<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\PenempatanPKL;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absensi>
 */
class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penempatan_pkl_id' => PenempatanPKL::factory(),
            'tanggal' => fake()->unique()->date(),
            'status' => fake()->randomElement(['hadir', 'izin', 'sakit', 'alpha']),
            'jam_masuk' => fake()->optional()->time('H:i:s'),
            'jam_keluar' => fake()->optional()->time('H:i:s'),
            'device' => fake()->optional()->word(),
            'browser' => fake()->optional()->randomElement(['Chrome', 'Firefox', 'Edge', 'Safari']),
            'ip_address' => fake()->optional()->ipv4(),
            'latitude_masuk' => fake()->optional()->randomFloat(7, -7.5, -6.5),
            'longitude_masuk' => fake()->optional()->randomFloat(7, 106.0, 108.0),
            'latitude_keluar' => fake()->optional()->randomFloat(7, -7.5, -6.5),
            'longitude_keluar' => fake()->optional()->randomFloat(7, 106.0, 108.0),
            'radius' => fake()->optional()->numberBetween(50, 250),
            'jarak' => fake()->optional()->randomFloat(2, 0, 500),
            'lokasi_valid' => fake()->boolean(80),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
