<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->company().' '.fake()->randomElement(['Tower', 'Villa', 'Facility']),
            'code' => 'PRJ-'.fake()->unique()->numerify('####'),
            'status' => ProjectStatus::Active,
            'start_date' => fake()->date(),
            'target_end_date' => fake()->optional()->date(),
            'budget' => fake()->randomFloat(2, 1_000_000, 50_000_000),
            'timezone' => fake()->randomElement(['UTC', 'Asia/Jakarta']),
            'meta_data' => [],
        ];
    }
}
