<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Aktivitas;
use App\Models\PenempatanPKL;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aktivitas>
 */
class AktivitasFactory extends Factory
{
    protected $model = Aktivitas::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penempatan_pkl_id' => PenempatanPKL::factory(),
            'approved_by' => null,
            'tanggal' => fake()->date(),
            'judul' => fake()->sentence(4),
            'deskripsi' => fake()->paragraph(),
            'status' => fake()->randomElement(['draft', 'dikirim', 'disetujui', 'ditolak']),
            'catatan_reviewer' => fake()->optional()->sentence(),
            'rejected_reason' => null,
            'dikirim_pada' => fake()->optional()->dateTime(),
            'approved_at' => null,
        ];
    }
}
