<?php

namespace Database\Factories;

use App\Enums\DailyReportStatus;
use App\Enums\WeatherCondition;
use App\Models\DailyReport;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'report_date' => fake()->unique()->date(),
            'weather_condition' => fake()->randomElement(WeatherCondition::cases())->value,
            'work_summary' => fake()->paragraph(),
            'delays_or_issues' => fake()->optional()->sentence(),
            'status' => DailyReportStatus::Draft,
            'created_by_user_id' => User::factory(),
            'meta_data' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DailyReportStatus::Published,
        ]);
    }
}
