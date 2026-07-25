<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PenempatanPKL;
use App\Models\Penilaian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penilaian>
 */
class PenilaianFactory extends Factory
{
    protected $model = Penilaian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nilaiAkhir = fake()->numberBetween(70, 100);

        return [
            'penempatan_pkl_id' => PenempatanPKL::factory(),
            'dinilai_oleh' => null,
            'nilai_sikap' => fake()->numberBetween(70, 100),
            'nilai_keterampilan' => fake()->numberBetween(70, 100),
            'nilai_pengetahuan' => fake()->numberBetween(70, 100),
            'nilai_akhir' => $nilaiAkhir,
            'predikat' => $this->predikat($nilaiAkhir),
            'status' => fake()->randomElement(['draft', 'final']),
            'tanggal_penilaian' => fake()->optional()->date(),
            'catatan' => fake()->optional()->sentence(),
        ];
    }

    private function predikat(int $nilai): string
    {
        return match (true) {
            $nilai >= 90 => 'A',
            $nilai >= 80 => 'B',
            default => 'C',
        };
    }
}
