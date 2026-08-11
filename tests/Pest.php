<?php

use App\Models\DailyReportPhoto;
use App\Models\DailyReportWorker;
use App\Models\Worker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * @return array{0: Project, 1: Site, 2: DailyReport}
 */
function reportWithWorkersAndPhoto(): array
{
    $project = App\Models\Project::factory()->create();
    $site = App\Models\Site::factory()->create(['project_id' => $project->id]);
    $report = App\Models\DailyReport::factory()->published()->create([
        'site_id' => $site->id,
        'report_date' => Carbon::now()->subDays(5)->toDateString(),
        'weather_condition' => 'rainy',
        'work_summary' => 'Excavation completed for Block A.',
        'meta_data' => ['moisture' => 12, 'safety_incidents' => 0],
    ]);

    $worker = Worker::factory()->create(['full_name' => 'Budi Santoso', 'trade_skill' => 'Mason']);
    DailyReportWorker::create([
        'daily_report_id' => $report->id,
        'worker_id' => $worker->id,
        'hours_worked' => 8,
        'remarks' => 'Site foreman',
    ]);

    DailyReportPhoto::create([
        'daily_report_id' => $report->id,
        'file_path' => 'daily-report-photos/abc.jpg',
        'thumbnail_path' => 'daily-report-photos/thumbs/abc.jpg',
        'caption' => 'Block A foundation',
    ]);

    return [$project, $site, $report];
}
