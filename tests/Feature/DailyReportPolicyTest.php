<?php

use App\Enums\DailyReportStatus;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportFor(Project $project): DailyReport
{
    $site = Site::factory()->create(['project_id' => $project->id]);

    return DailyReport::factory()->create(['site_id' => $site->id]);
}

it('lets an admin view, create, update, and delete any report', function () {
    $admin = adminUser();
    $report = reportFor(Project::factory()->create());

    expect($admin->can('view', $report))->toBeTrue()
        ->and($admin->can('viewAny', DailyReport::class))->toBeTrue()
        ->and($admin->can('create', DailyReport::class))->toBeTrue()
        ->and($admin->can('update', $report))->toBeTrue()
        ->and($admin->can('delete', $report))->toBeTrue();
});

it('lets a site engineer create, view, update, and delete reports on assigned sites', function () {
    $project = Project::factory()->create();
    $engineer = engineerAssignedTo($project);
    $report = reportFor($project);

    expect($engineer->can('create', DailyReport::class))->toBeTrue()
        ->and($engineer->can('view', $report))->toBeTrue()
        ->and($engineer->can('update', $report))->toBeTrue()
        ->and($engineer->can('delete', $report))->toBeTrue();
});

it('denies a site engineer any access to reports on unassigned sites', function () {
    $assigned = Project::factory()->create();
    $unassigned = Project::factory()->create();
    $engineer = engineerAssignedTo($assigned);

    $assignedReport = reportFor($assigned);
    $foreignReport = reportFor($unassigned);

    expect($engineer->can('view', $assignedReport))->toBeTrue()
        ->and($engineer->can('view', $foreignReport))->toBeFalse()
        ->and($engineer->can('update', $foreignReport))->toBeFalse()
        ->and($engineer->can('delete', $foreignReport))->toBeFalse();
});

it('lets a client view only published reports from their own projects', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own, $other);

    $site = Site::factory()->create(['project_id' => $own->id]);
    $published = DailyReport::factory()->published()->create(['site_id' => $site->id]);
    $draft = DailyReport::factory()->create(['site_id' => $site->id]);
    $foreignSite = Site::factory()->create(['project_id' => $other->id]);
    $foreignPublished = DailyReport::factory()->published()->create(['site_id' => $foreignSite->id]);

    expect($clientUser->can('view', $published))->toBeTrue()
        ->and($clientUser->can('view', $draft))->toBeFalse()
        ->and($clientUser->can('view', $foreignPublished))->toBeFalse();
});

it('denies a client all report mutation', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);
    $report = reportFor($own);

    expect($clientUser->can('create', DailyReport::class))->toBeFalse()
        ->and($clientUser->can('update', $report))->toBeFalse()
        ->and($clientUser->can('delete', $report))->toBeFalse();
});

it('keeps a published report immutable and draft hidden even when statuses change', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);
    $site = Site::factory()->create(['project_id' => $own->id]);

    $report = DailyReport::factory()->create(['site_id' => $site->id]);
    $report->update(['status' => DailyReportStatus::NeedApproval]);

    expect($clientUser->can('view', $report))->toBeFalse();
});
