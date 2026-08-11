<?php

use App\Enums\DailyReportStatus;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function clientReport(Project $project): DailyReport
{
    $site = Site::factory()->create(['project_id' => $project->id]);

    return DailyReport::factory()->create(['site_id' => $site->id]);
}

it('hides every non-published status from a client even when the UUID is known', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $statuses = [
        DailyReportStatus::Draft,
        DailyReportStatus::NeedApproval,
        DailyReportStatus::RevisionRequested,
    ];

    foreach ($statuses as $status) {
        $report = clientReport($own);
        $report->update(['status' => $status]);

        expect($clientUser->can('view', $report))
            ->toBeFalse("client must not see status {$status->value} of their own report by UUID");
    }
});

it('hides non-published reports from a client through the scoped query too', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    foreach ([DailyReportStatus::Draft, DailyReportStatus::NeedApproval, DailyReportStatus::RevisionRequested] as $status) {
        $report = clientReport($own);
        $report->update(['status' => $status]);
    }

    expect(DailyReport::forClient($clientUser)->count())->toBe(0);
});

it('exposes only published reports to the client scope', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);
    $site = Site::factory()->create(['project_id' => $own->id]);

    DailyReport::factory()->count(2)->published()->create(['site_id' => $site->id]);
    clientReport($own);

    expect(DailyReport::forClient($clientUser)->count())->toBe(2);
});

it('hides another clients published report by UUID', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $otherSite = Site::factory()->create(['project_id' => $other->id]);
    $foreign = DailyReport::factory()->published()->create(['site_id' => $otherSite->id]);

    expect($clientUser->can('view', $foreign))->toBeFalse();
});
