<?php

namespace Database\Factories;

use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'trade_skill' => fake()->randomElement(['Mason', 'Electrician', 'General Laborer', 'Carpenter', 'Rigger']),
            'daily_rate' => fake()->numberBetween(1_300_000, 4_000_000),
            'is_active' => true,
            'meta_data' => [],
        ];
    }
}
