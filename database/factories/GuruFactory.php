<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guru>
 */
class GuruFactory extends Factory
{
    protected $model = Guru::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory()->state([
                'name' => $name,
            ]),
            'nip' => fake()->unique()->numerify('19##############'),
            'nama' => $name,
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'no_hp' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
        ];
    }
}
