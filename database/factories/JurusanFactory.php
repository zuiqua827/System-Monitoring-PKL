<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jurusan>
 */
class JurusanFactory extends Factory
{
    protected $model = Jurusan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nama = fake()->randomElement([
            'Rekayasa Perangkat Lunak',
            'Teknik Komputer dan Jaringan',
            'Multimedia',
            'Akuntansi',
            'Administrasi Perkantoran',
        ]).' '.fake()->unique()->bothify('##');

        return [
            'kode' => fake()->unique()->bothify('JRS-###'),
            'nama' => $nama,
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }
}
