<?php

namespace Database\Factories;

use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PayrollRun>
 */
class PayrollRunFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = Carbon::today()->subDays(15);

        return [
            'period_start' => $start->toDateString(),
            'period_end' => $start->copy()->addDays(13)->toDateString(),
            'status' => PayrollRunStatus::Draft,
            'generated_by_user_id' => null,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn (): array => ['status' => PayrollRunStatus::PendingReview]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollRunStatus::Approved,
            'approved_by_user_id' => User::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['status' => PayrollRunStatus::Paid]);
    }
}
