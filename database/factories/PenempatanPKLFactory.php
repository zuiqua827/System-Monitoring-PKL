<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dudi;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\PeriodePKL;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenempatanPKL>
 */
class PenempatanPKLFactory extends Factory
{
    protected $model = PenempatanPKL::class;

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
            'periode_pkl_id' => PeriodePKL::factory(),
            'guru_id' => Guru::factory(),
            'dudi_id' => Dudi::factory(),
            'siswa_id' => Siswa::factory(),
            'dibuat_oleh' => User::factory(),
            'approved_by' => null,
            'nomor_surat' => fake()->unique()->bothify('PKL/###/??/'.date('Y')),
            'tanggal_mulai' => $tanggalMulai->format('Y-m-d'),
            'tanggal_selesai' => $tanggalSelesai->format('Y-m-d'),
            'status' => fake()->randomElement(['pending', 'aktif', 'selesai', 'dibatalkan']),
            'approved_at' => null,
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
