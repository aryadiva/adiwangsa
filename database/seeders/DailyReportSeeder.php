<?php

namespace Database\Seeders;

use App\Enums\DailyReportStatus;
use App\Models\DailyReport;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Seeder;

class DailyReportSeeder extends Seeder
{
    public function run(): void
    {
        $engineer = User::where('role', 'site_engineer')->first();
        $sites = Site::query()->get();
        $workers = Worker::query()->get();

        if (! $engineer || $sites->isEmpty() || $workers->isEmpty()) {
            return;
        }

        $sites->take(3)->each(function (Site $site, int $index) use ($engineer, $workers) {
            $status = match ($index) {
                0 => DailyReportStatus::Published,
                1 => DailyReportStatus::NeedApproval,
                default => DailyReportStatus::Draft,
            };

            $report = DailyReport::create([
                'site_id' => $site->id,
                'created_by_user_id' => $engineer->id,
                'report_date' => today()->subDays($index),
                'weather_condition' => $index === 2 ? 'rainy' : 'sunny',
                'work_summary' => $index === 2
                    ? 'Excavation continued; heavy rain halted crane operations after noon.'
                    : 'Foundation excavation and rebar installation progressed according to plan.',
                'delays_or_issues' => $index === 2 ? 'Weather delay (rain).' : null,
                'status' => $status,
                'meta_data' => ['manpower' => $workers->count()],
            ]);

            $report->workerAllocations()->createMany(
                $workers->take(3)->map(fn (Worker $worker, int $i) => [
                    'worker_id' => $worker->id,
                    'hours_worked' => $index === 2 && $i === 0 ? 6.0 : 8.0,
                    'remarks' => $i === 0 ? 'Lead rigger' : null,
                ])->all()
            );

            $report->photos()->create([
                'before_file_path' => 'photos/'.$report->id.'/before.jpg',
                'before_thumbnail_path' => 'photos/'.$report->id.'/before_thumb.jpg',
                'after_file_path' => 'photos/'.$report->id.'/after.jpg',
                'after_thumbnail_path' => 'photos/'.$report->id.'/after_thumb.jpg',
                'description' => 'End-of-shift progress pair',
                'captured_at' => now(),
                'file_size_bytes' => 20480,
            ]);
        });
    }
}
