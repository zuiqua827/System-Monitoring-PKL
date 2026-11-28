<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Aktivitas;
use App\Models\Komentar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Komentar>
 */
class KomentarFactory extends Factory
{
    protected $model = Komentar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'aktivitas_id' => Aktivitas::factory(),
            'isi' => fake()->sentence(),
            'is_internal' => fake()->boolean(20),
        ];
    }
}
