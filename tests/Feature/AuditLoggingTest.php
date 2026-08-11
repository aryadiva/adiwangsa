<?php

use App\Enums\DailyReportStatus;
use App\Enums\ProjectMilestoneStatus;
use App\Enums\ProjectStatus;
use App\Filament\Resources\DailyReportResource\Pages\ListDailyReports;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logs who changed a daily report status with old and new values', function () {
    Notification::fake();
    $engineer = User::factory()->siteEngineer()->create();
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::Draft]);

    $this->actingAs($engineer);
    $report->submitForApproval();

    $activity = $report->activitiesAsSubject()->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity)->toBeInstanceOf(Activity::class)
        ->and($activity->event)->toBe('updated')
        ->and($activity->causer_id)->toBe($engineer->id)
        ->and($activity->attribute_changes['old']['status'])->toBe('draft')
        ->and($activity->attribute_changes['attributes']['status'])->toBe('need_approval')
        ->and($activity->created_at)->not->toBeNull();
});

it('logs project status changes with old and new values', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);

    $project->update(['status' => ProjectStatus::Active]);

    $activity = $project->activitiesAsSubject()->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('updated')
        ->and($activity->attribute_changes['old']['status'])->toBe('planning')
        ->and($activity->attribute_changes['attributes']['status'])->toBe('active');
});

it('logs project milestone status changes', function () {
    $project = Project::factory()->create();
    $milestone = $project->milestones()->create([
        'title' => 'Concrete pour',
        'target_date' => now()->addMonth()->toDateString(),
        'status' => ProjectMilestoneStatus::Pending,
    ]);

    $milestone->update(['status' => ProjectMilestoneStatus::InProgress]);

    $activity = $milestone->activitiesAsSubject()->where('event', 'updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('updated')
        ->and($activity->attribute_changes['old']['status'])->toBe('pending')
        ->and($activity->attribute_changes['attributes']['status'])->toBe('in_progress');
});

it('exposes the view activity log action to an admin', function () {
    $report = DailyReport::factory()->create();

    Livewire::actingAs(adminUser())
        ->test(ListDailyReports::class)
        ->assertTableActionVisible('view_activity_log', $report);
});

it('hides the view activity log action from a site engineer', function () {
    $project = Project::factory()->create();
    $engineer = engineerAssignedTo($project);
    $site = Site::factory()->create(['project_id' => $project->id]);
    $report = DailyReport::factory()->create(['site_id' => $site->id]);

    Livewire::actingAs($engineer)
        ->test(ListDailyReports::class)
        ->assertTableActionHidden('view_activity_log', $report);
});
