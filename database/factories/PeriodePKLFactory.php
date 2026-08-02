<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PeriodePKL;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodePKL>
 */
class PeriodePKLFactory extends Factory
{
    protected $model = PeriodePKL::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalMulai = fake()->dateTimeBetween('-1 month', '+1 month');
        $tanggalSelesai = (clone $tanggalMulai)->modify('+3 months');

        return [
            'nama' => fake()->unique()->sentence(3),
            'tahun_ajaran' => fake()->randomElement(['2025/2026', '2026/2027']),
            'tanggal_mulai' => $tanggalMulai->format('Y-m-d'),
            'tanggal_selesai' => $tanggalSelesai->format('Y-m-d'),
            'status' => fake()->randomElement(['Persiapan', 'Aktif', 'Selesai', 'Ditutup']),
            'keterangan' => fake()->optional()->sentence(),
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
