<?php

namespace Database\Factories;

use App\Enums\MilestoneSubJobStatus;
use App\Models\MilestoneSubJob;
use App\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneSubJob>
 */
class MilestoneSubJobFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_milestone_id' => ProjectMilestone::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'start_date' => fake()->date(),
            'working_days' => fake()->numberBetween(5, 30),
            'quantity' => fake()->randomFloat(2, 100, 1000),
            'weight_percentage' => 100.0,
            'status' => MilestoneSubJobStatus::Pending,
            'sort_order' => 0,
        ];
    }
}
