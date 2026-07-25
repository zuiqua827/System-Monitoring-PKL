<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\PenempatanPKL;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laporan>
 */
class LaporanFactory extends Factory
{
    protected $model = Laporan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penempatan_pkl_id' => PenempatanPKL::factory(),
            'validated_by' => null,
            'judul' => fake()->sentence(5),
            'version' => 1,
            'isi' => fake()->optional()->paragraphs(3, true),
            'file_path' => fake()->optional()->filePath(),
            'status' => fake()->randomElement(['draft', 'dikirim', 'direvisi', 'disetujui']),
            'dikumpulkan_pada' => fake()->optional()->dateTime(),
            'validated_at' => null,
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
