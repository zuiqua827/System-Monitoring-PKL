<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tingkat = fake()->randomElement([10, 11, 12]);

        return [
            'jurusan_id' => Jurusan::factory(),
            'nama' => sprintf('%d %s', $tingkat, fake()->unique()->bothify('??-#')),
            'tingkat' => $tingkat,
            'tahun_ajaran' => fake()->randomElement(['2025/2026', '2026/2027']),
        ];
    }
}
