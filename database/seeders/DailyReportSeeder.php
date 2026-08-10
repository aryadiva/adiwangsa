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

        $sites->take(2)->each(function (Site $site) use ($engineer, $workers) {
            $report = DailyReport::create([
                'site_id' => $site->id,
                'created_by_user_id' => $engineer->id,
                'report_date' => today(),
                'weather_condition' => 'sunny',
                'work_summary' => 'Foundation excavation and rebar installation progressed according to plan.',
                'delays_or_issues' => null,
                'status' => DailyReportStatus::Published,
                'meta_data' => ['manpower' => $workers->count()],
            ]);

            $report->workerAllocations()->createMany(
                $workers->take(3)->map(fn (Worker $worker, int $i) => [
                    'worker_id' => $worker->id,
                    'hours_worked' => 8.0,
                    'remarks' => $i === 0 ? 'Lead rigger' : null,
                ])->all()
            );

            $report->photos()->create([
                'file_path' => 'photos/'.$report->id.'/progress.jpg',
                'thumbnail_path' => 'photos/'.$report->id.'/progress_thumb.jpg',
                'file_size_bytes' => 10240,
            ]);
        });
    }
}
