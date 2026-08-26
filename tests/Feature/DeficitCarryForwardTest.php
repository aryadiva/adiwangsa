<?php

use App\Enums\MilestoneSubJobStatus;
use App\Enums\ReportShift;
use App\Enums\UserRole;
use App\Filament\Resources\DailyReportResource\Pages\CreateDailyReport;
use App\Filament\Resources\DailyReportResource\Pages\EditDailyReport;
use App\Models\DailyReport;
use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Site;
use App\Models\TargetDelayWarning;
use App\Models\User;
use App\Notifications\TargetDelayWarningNotification;
use App\Services\DeficitCarryForwardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification as MailNotification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeSubJobWithReports(Carbon $date, array $reportOverrides = []): array
{
    $project = Project::factory()->create(['start_date' => $date->copy()->subDays(10)]);
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 100,
        'start_date' => $date->copy()->subDays(5),
        'target_date' => $date->copy()->addDays(20),
    ]);
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'start_date' => $date->copy()->subDays(5),
        'working_days' => 10,
        'quantity' => 100,
        'weight_percentage' => 100,
        'status' => MilestoneSubJobStatus::InProgress,
    ]);
    $site = Site::factory()->create(['project_id' => $project->id]);
    $engineer = User::factory()->create(['role' => UserRole::SiteEngineer]);
    $engineer->projects()->attach($project);

    $reports = collect($reportOverrides)->map(fn ($overrides) => DailyReport::factory()->create(array_merge([
        'site_id' => $site->id,
        'milestone_sub_job_id' => $subJob->id,
        'created_by_user_id' => $engineer->id,
        'shift' => ReportShift::Shift1,
    ], $overrides)));

    return [$project, $milestone, $subJob, $site, $engineer, $reports];
}

it('computes baseline target as quantity divided by working_days', function () {
    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 100,
        'start_date' => now()->subDays(5),
        'target_date' => now()->addDays(20),
    ]);
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'working_days' => 10,
        'quantity' => 100,
        'weight_percentage' => 100,
    ]);

    expect(DeficitCarryForwardService::baselineTarget($subJob))->toBe('10.00');
});

it('carries forward deficit when achievement is below target', function () {
    $today = Carbon::today();
    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today->copy()->subDays(2),
            'daily_target' => 10,
            'daily_achievement' => 6,
        ],
    ]);

    // Deficit = 10 - 6 = 4, so today's target = 10 + 4 = 14
    $target = DeficitCarryForwardService::computeDailyTarget($subJob, $today);

    expect($target)->toBe('14.00');
});

it('accumulates deficit across consecutive missed days', function () {
    $today = Carbon::today();

    // Day -2: target 10, achieved 6 → deficit 4
    // Day -1: target 14 (10+4), achieved 8 → deficit 6
    // Today: target 16 (10+6)
    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today->copy()->subDays(2),
            'daily_target' => 10,
            'daily_achievement' => 6,
        ],
        [
            'report_date' => $today->copy()->subDay(),
            'daily_target' => 14,
            'daily_achievement' => 8,
        ],
    ]);

    $target = DeficitCarryForwardService::computeDailyTarget($subJob, $today);

    expect($target)->toBe('16.00');
});

it('resets deficit to zero when achievement catches up or exceeds target', function () {
    $today = Carbon::today();

    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today->copy()->subDays(2),
            'daily_target' => 10,
            'daily_achievement' => 6,
        ],
        [
            'report_date' => $today->copy()->subDay(),
            'daily_target' => 14,
            'daily_achievement' => 20,
        ],
    ]);

    $target = DeficitCarryForwardService::computeDailyTarget($subJob, $today);

    expect($target)->toBe('10.00');
});

it('creates a target delay warning with first_triggered_at on first deficit', function () {
    MailNotification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $today = Carbon::today();

    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today,
            'daily_target' => 10,
            'daily_achievement' => 6,
        ],
    ]);

    $report = $reports->first();
    DeficitCarryForwardService::evaluateTargetDeficit($report);

    $warning = TargetDelayWarning::query()
        ->where('milestone_sub_job_id', $subJob->id)
        ->first();

    expect($warning)->not->toBeNull()
        ->and($warning->deficit)->toBe('4.00')
        ->and($warning->first_triggered_at)->not->toBeNull()
        ->and($warning->resolved_at)->toBeNull();

    MailNotification::assertSentTo($admin, TargetDelayWarningNotification::class);
});

it('does not fire a second notification on subsequent deficit evaluations', function () {
    MailNotification::fake();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $today = Carbon::today();

    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today,
            'daily_target' => 10,
            'daily_achievement' => 6,
        ],
        [
            'report_date' => $today->copy()->addDay(),
            'daily_target' => 10,
            'daily_achievement' => 4,
        ],
    ]);

    // First evaluation
    DeficitCarryForwardService::evaluateTargetDeficit($reports->first());

    MailNotification::assertSentTo($admin, TargetDelayWarningNotification::class, 1);

    // Second evaluation — should update but not notify again
    DeficitCarryForwardService::evaluateTargetDeficit($reports->skip(1)->first());

    MailNotification::assertSentTo($admin, TargetDelayWarningNotification::class, 1);

    // Warning deficit should be updated to 6 (10 - 4)
    $warning = TargetDelayWarning::query()
        ->where('milestone_sub_job_id', $subJob->id)
        ->first();

    expect($warning->deficit)->toBe('6.00');
});

it('resolves the warning when achievement meets or exceeds target', function () {
    MailNotification::fake();

    $today = Carbon::today();

    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today,
            'daily_target' => 10,
            'daily_achievement' => 6,
        ],
        [
            'report_date' => $today->copy()->addDay(),
            'daily_target' => 10,
            'daily_achievement' => 12,
        ],
    ]);

    DeficitCarryForwardService::evaluateTargetDeficit($reports->first());

    $warning = TargetDelayWarning::query()
        ->where('milestone_sub_job_id', $subJob->id)
        ->first();

    expect($warning->resolved_at)->toBeNull();

    DeficitCarryForwardService::evaluateTargetDeficit($reports->skip(1)->first());

    $warning->refresh();

    expect($warning->resolved_at)->not->toBeNull();
});

it('assigns the same full daily target to both shifts on the same day', function () {
    $today = Carbon::today();

    [$project, $milestone, $subJob, $site, $engineer, $reports] = makeSubJobWithReports($today, [
        [
            'report_date' => $today->copy()->subDay(),
            'daily_target' => 10,
            'daily_achievement' => 6,
            'shift' => ReportShift::Shift1,
        ],
    ]);

    // Create two shift reports for today
    $shift1 = DailyReport::factory()->create([
        'site_id' => $site->id,
        'milestone_sub_job_id' => $subJob->id,
        'created_by_user_id' => $engineer->id,
        'report_date' => $today,
        'shift' => ReportShift::Shift1,
    ]);

    $shift2 = DailyReport::factory()->create([
        'site_id' => $site->id,
        'milestone_sub_job_id' => $subJob->id,
        'created_by_user_id' => $engineer->id,
        'report_date' => $today,
        'shift' => ReportShift::Shift2,
    ]);

    $target1 = DeficitCarryForwardService::computeDailyTarget($subJob, $today);
    $target2 = DeficitCarryForwardService::computeDailyTarget($subJob, $today);

    expect($target1)->toBe('14.00')
        ->and($target2)->toBe('14.00');
});

it('rejects a duplicate (site_id, report_date, shift) combination at the app layer', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 100,
    ]);
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'weight_percentage' => 100,
    ]);
    $site = Site::factory()->create(['project_id' => $project->id]);

    DailyReport::factory()->create([
        'site_id' => $site->id,
        'report_date' => '2026-08-11',
        'shift' => ReportShift::Shift1,
        'milestone_sub_job_id' => $subJob->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CreateDailyReport::class)
        ->fillForm([
            'site_id' => $site->id,
            'milestone_sub_job_id' => $subJob->id,
            'report_date' => '2026-08-11',
            'shift' => ReportShift::Shift1,
            'weather_condition' => 'sunny',
            'work_summary' => 'Duplicate attempt',
        ])
        ->call('create')
        ->assertHasFormErrors(['report_date']);
});

it('allows the same site and date with a different shift', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 100,
    ]);
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'weight_percentage' => 100,
    ]);
    $site = Site::factory()->create(['project_id' => $project->id]);

    DailyReport::factory()->create([
        'site_id' => $site->id,
        'report_date' => '2026-08-11',
        'shift' => ReportShift::Shift1,
        'milestone_sub_job_id' => $subJob->id,
    ]);

    Livewire::actingAs($admin)
        ->test(CreateDailyReport::class)
        ->fillForm([
            'site_id' => $site->id,
            'milestone_sub_job_id' => $subJob->id,
            'report_date' => '2026-08-11',
            'shift' => ReportShift::Shift2,
            'weather_condition' => 'sunny',
            'work_summary' => 'Different shift, same site and date',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DailyReport::where('site_id', $site->id)
        ->where('report_date', '2026-08-11')
        ->where('shift', ReportShift::Shift2)
        ->exists())->toBeTrue();
});

it('ignores its own record when checking for duplicates on edit', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'weight_percentage' => 100,
    ]);
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'weight_percentage' => 100,
    ]);

    $report = DailyReport::factory()->create([
        'report_date' => '2026-08-11',
        'shift' => ReportShift::Shift1,
        'milestone_sub_job_id' => $subJob->id,
        'site_id' => Site::factory()->create(['project_id' => $project->id])->id,
    ]);

    Livewire::actingAs($admin)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm([
            'site_id' => $report->site_id,
            'milestone_sub_job_id' => $subJob->id,
            'report_date' => '2026-08-11',
            'shift' => ReportShift::Shift1,
            'weather_condition' => 'rainy',
            'work_summary' => 'Edited summary, same site date shift',
        ])
        ->call('save')
        ->assertHasNoFormErrors(['report_date']);

    expect($report->fresh()->work_summary)->toBe('Edited summary, same site date shift');
});
