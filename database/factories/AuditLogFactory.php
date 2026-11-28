<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'deleted', 'login']),
            'table_name' => fake()->optional()->randomElement(['users', 'siswa', 'penempatan_pkl']),
            'record_id' => fake()->optional()->numberBetween(1, 1000),
            'old_values' => fake()->optional()->randomElement([['status' => 'draft'], ['name' => 'Old Name']]),
            'new_values' => fake()->optional()->randomElement([['status' => 'aktif'], ['name' => 'New Name']]),
            'ip_address' => fake()->optional()->ipv4(),
            'browser' => fake()->optional()->randomElement(['Chrome', 'Firefox', 'Edge', 'Safari']),
            'device' => fake()->optional()->word(),
            'platform' => fake()->optional()->randomElement(['Windows', 'macOS', 'Android', 'iOS']),
            'method' => fake()->optional()->randomElement(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']),
            'url' => fake()->optional()->url(),
            'user_agent' => fake()->optional()->userAgent(),
        ];
    }
}
