<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dudi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dudi>
 */
class DudiFactory extends Factory
{
    protected $model = Dudi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = fake()->company();

        return [
            'user_id' => User::factory()->withRole('DUDI')->state([
                'name' => $company,
            ]),
            'nama_perusahaan' => $company,
            'penanggung_jawab' => fake()->name(),
            'email_perusahaan' => fake()->unique()->companyEmail(),
            'no_telepon' => fake()->phoneNumber(),
            'logo' => fake()->optional()->imageUrl(400, 400, 'business'),
            'website' => fake()->optional()->url(),
            'bidang_usaha' => fake()->randomElement([
                'Teknologi Informasi',
                'Manufaktur',
                'Perdagangan',
                'Jasa',
            ]),
            'alamat' => fake()->address(),
        ];
    }
}
