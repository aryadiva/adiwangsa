<?php

namespace Database\Factories;

use App\Enums\ProjectMilestoneStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMilestone>
 */
class ProjectMilestoneFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'start_date' => fake()->date(),
            'target_date' => fake()->date(),
            'completed_at' => null,
            'status' => ProjectMilestoneStatus::Pending,
            'weight_percentage' => 100.0,
            'sort_order' => 0,
        ];
    }
}
