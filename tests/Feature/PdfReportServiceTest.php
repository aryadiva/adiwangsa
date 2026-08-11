<?php

use App\DTOs\ReportDataDTO;
use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Enums\ProjectMilestoneStatus;
use App\Models\DailyReport;
use App\Models\ProjectMilestone;
use App\Services\PdfReportService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('maps a daily report into a queue-safe DTO with header, workers and jsonb meta', function () {
    [, , $report] = reportWithWorkersAndPhoto();

    $dto = ReportDataDTO::forDailyReport($report);

    expect($dto->type)->toBe(DocumentType::DailyProgress)
        ->and($dto->siteName)->not->toBeNull()
        ->and($dto->reportDate)->toBe($report->report_date->toDateString())
        ->and($dto->weather)->toBe('rainy')
        ->and($dto->workSummary)->toBe('Excavation completed for Block A.')
        ->and($dto->workerCount)->toBe(1)
        ->and($dto->workerRows)->toHaveCount(1)
        ->and($dto->workerRows[0]['name'])->toBe('Budi Santoso')
        ->and($dto->workerRows[0]['trade'])->toBe('Mason')
        ->and($dto->photos)->toHaveCount(1)
        ->and($dto->photos[0]['path'])->toBe('daily-report-photos/abc.jpg')
        ->and($dto->metaData)->toMatchArray(['moisture' => 12, 'safety_incidents' => 0]);
});

it('excludes non-published and out-of-range reports from the weekly digest DTO', function () {
    [$project, $site] = reportWithWorkersAndPhoto();

    ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Foundation Slab',
        'target_date' => Carbon::now()->addDays(10)->toDateString(),
        'status' => ProjectMilestoneStatus::Completed,
        'completed_at' => Carbon::now()->toDateString(),
    ]);

    DailyReport::factory()->published()->create([
        'site_id' => $site->id,
        'report_date' => Carbon::now()->subDays(3)->toDateString(),
        'work_summary' => 'Second published report.',
    ]);

    DailyReport::factory()->create([
        'site_id' => $site->id,
        'status' => DailyReportStatus::Draft,
        'report_date' => Carbon::now()->subDays(2)->toDateString(),
        'work_summary' => 'Draft must never appear.',
    ]);

    DailyReport::factory()->create([
        'site_id' => $site->id,
        'status' => DailyReportStatus::NeedApproval,
        'report_date' => Carbon::now()->subDays(1)->toDateString(),
        'work_summary' => 'Under review must never appear.',
    ]);

    DailyReport::factory()->published()->create([
        'site_id' => $site->id,
        'report_date' => Carbon::now()->subDays(30)->toDateString(),
        'work_summary' => 'Outside range must never appear.',
    ]);

    $start = Carbon::now()->subDays(7);
    $end = Carbon::now();

    $dto = ReportDataDTO::forWeeklyDigest($project, $start, $end);

    expect($dto->type)->toBe(DocumentType::WeeklyDigest)
        ->and($dto->reportSummaries)->toHaveCount(2)
        ->and($dto->milestones)->toHaveCount(1)
        ->and($dto->milestones[0]['title'])->toBe('Foundation Slab');
});

it('passes the daily report DTO to the daily-progress Blade view', function () {
    [, , $report] = reportWithWorkersAndPhoto();
    $dto = ReportDataDTO::forDailyReport($report);

    $this->mock(PDF::class, function ($mock) use ($dto) {
        $mock->shouldReceive('loadView')
            ->once()
            ->with('pdf.daily-progress', Mockery::on(
                fn (array $data): bool => ($data['dto'] ?? null) === $dto
                    && $data['dto']->type === DocumentType::DailyProgress
                    && $data['dto']->workSummary === 'Excavation completed for Block A.'
            ))
            ->andReturnSelf();
        $mock->shouldReceive('output')->andReturn('rendered-pdf-bytes');
    });

    $bytes = $this->app->make(PdfReportService::class)->render($dto);

    expect($bytes)->toBe('rendered-pdf-bytes');
});

it('passes the weekly digest DTO to the weekly-digest Blade view', function () {
    [$project] = reportWithWorkersAndPhoto();
    $dto = ReportDataDTO::forWeeklyDigest($project, Carbon::now()->subDays(7), Carbon::now());

    $this->mock(PDF::class, function ($mock) use ($dto) {
        $mock->shouldReceive('loadView')
            ->once()
            ->with('pdf.weekly-digest', Mockery::on(
                fn (array $data): bool => ($data['dto'] ?? null) === $dto
                    && $data['dto']->type === DocumentType::WeeklyDigest
            ))
            ->andReturnSelf();
        $mock->shouldReceive('output')->andReturn('digest-bytes');
    });

    $bytes = $this->app->make(PdfReportService::class)->render($dto);

    expect($bytes)->toBe('digest-bytes');
});

it('builds an attendance roster DTO grouped by worker', function () {
    [, , $report] = reportWithWorkersAndPhoto();

    $dto = ReportDataDTO::forAttendanceRoster(
        collect([$report]),
        Carbon::now()->subDays(1),
        Carbon::now(),
    );

    expect($dto->type)->toBe(DocumentType::AttendanceRoster)
        ->and($dto->workerRows)->toHaveCount(1)
        ->and($dto->workerRows[0]['name'])->toBe('Budi Santoso')
        ->and($dto->workerRows[0]['hours'])->toBe('8.00')
        ->and($dto->totalHours)->toBe('8.00');
});

it('excludes non-published states from the weekly digest even when in range', function () {
    [$project, $site] = reportWithWorkersAndPhoto();

    foreach ([
        [DailyReportStatus::Draft, 3],
        [DailyReportStatus::NeedApproval, 1],
        [DailyReportStatus::RevisionRequested, 2],
    ] as [$status, $daysAgo]) {
        DailyReport::factory()->create([
            'site_id' => $site->id,
            'status' => $status,
            'report_date' => Carbon::now()->subDays($daysAgo)->toDateString(),
        ]);
    }

    $dto = ReportDataDTO::forWeeklyDigest(
        $project,
        Carbon::now()->subDays(7),
        Carbon::now(),
    );

    expect($dto->reportSummaries)->toHaveCount(1);
});
