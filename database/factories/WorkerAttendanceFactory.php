<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<WorkerAttendance>
 */
class WorkerAttendanceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'worker_id' => Worker::factory(),
            'site_id' => Site::factory(),
            'recorded_by_user_id' => User::factory(),
            'attendance_date' => Carbon::today()->subDay()->toDateString(),
            'hours_worked' => 8.00,
            'overtime_hours' => 0.00,
            'captured_at' => now(),
            'meta_data' => [],
        ];
    }
}
