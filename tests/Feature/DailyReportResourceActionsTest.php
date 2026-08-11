<?php

use App\Enums\DailyReportStatus;
use App\Filament\Resources\DailyReportResource\Pages\EditDailyReport;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function engineerEditReport(): array
{
    $project = Project::factory()->create();
    $engineer = engineerAssignedTo($project);
    $site = Site::factory()->create(['project_id' => $project->id]);
    $report = DailyReport::factory()->create([
        'site_id' => $site->id,
        'created_by_user_id' => $engineer->id,
    ]);

    return [$engineer, $site, $report];
}

it('lets a site engineer submit a draft report for approval', function () {
    [$engineer, , $report] = engineerEditReport();

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->callAction('submitForApproval')
        ->assertNotified();

    expect($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval);
});

it('lets an admin approve and publish a need_approval report', function () {
    [$engineer, , $report] = engineerEditReport();
    $report->update(['status' => DailyReportStatus::NeedApproval]);

    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->callAction('approveAndPublish')
        ->assertNotified();

    expect($report->fresh()->status)->toBe(DailyReportStatus::Published)
        ->and($report->fresh()->reviewed_by_user_id)->toBe($admin->id);
});

it('lets an admin request a revision with admin_notes', function () {
    [$engineer, , $report] = engineerEditReport();
    $report->update(['status' => DailyReportStatus::NeedApproval]);

    Livewire::actingAs(adminUser())
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->callAction('requestRevision', data: ['admin_notes' => 'Add the north wall photos'])
        ->assertNotified();

    expect($report->fresh()->status)->toBe(DailyReportStatus::RevisionRequested)
        ->and($report->fresh()->admin_notes)->toBe('Add the north wall photos');
});

it('lets a site engineer resubmit a revision_requested report', function () {
    [$engineer, , $report] = engineerEditReport();
    $report->update(['status' => DailyReportStatus::RevisionRequested]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->callAction('resubmitForApproval')
        ->assertNotified();

    expect($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval)
        ->and($report->revisions()->count())->toBe(1);
});

it('hides the approve action from a site engineer', function () {
    [$engineer, , $report] = engineerEditReport();
    $report->update(['status' => DailyReportStatus::NeedApproval]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->assertActionHidden('approveAndPublish')
        ->assertActionHidden('requestRevision');
});

it('locks a published report against any edits', function () {
    [$engineer, , $report] = engineerEditReport();
    $report->update(['status' => DailyReportStatus::Published]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->assertActionHidden('submitForApproval')
        ->assertActionHidden('resubmitForApproval')
        ->fillForm(['work_summary' => 'Should not persist'])
        ->call('save')
        ->assertHasErrors();

    expect($report->fresh()->work_summary)->not->toBe('Should not persist');
});

it('exposes the approve and publish action to an admin', function () {
    [$engineer, , $report] = engineerEditReport();
    $report->update(['status' => DailyReportStatus::NeedApproval]);

    Livewire::actingAs(adminUser())
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->assertActionVisible('approveAndPublish')
        ->assertActionVisible('requestRevision');
});
