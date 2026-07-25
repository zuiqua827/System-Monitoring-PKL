<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notifikasi>
 */
class NotifikasiFactory extends Factory
{
    protected $model = Notifikasi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'judul' => fake()->sentence(4),
            'pesan' => fake()->sentence(),
            'tipe' => fake()->randomElement(['info', 'success', 'warning', 'danger']),
            'data' => [
                'url' => fake()->optional()->url(),
            ],
            'read_at' => fake()->optional()->dateTime(),
        ];
    }
}
