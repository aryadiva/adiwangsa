<?php

use App\Enums\DailyReportStatus;
use App\Models\Client;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Notifications\ReportApprovedNotification;
use App\Notifications\ReportPublishedNotification;
use App\Notifications\ReportSubmittedNotification;
use App\Notifications\RevisionRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function needApprovalReport(): array
{
    $engineer = User::factory()->siteEngineer()->create();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->create(['user_id' => $clientUser->id]);
    $project = Project::factory()->create(['client_id' => $client->id]);
    $site = Site::factory()->create(['project_id' => $project->id]);

    $report = DailyReport::factory()->create([
        'site_id' => $site->id,
        'created_by_user_id' => $engineer->id,
        'status' => DailyReportStatus::NeedApproval,
    ]);

    return [$report, $engineer, $clientUser];
}

it('notifies admins when a report is submitted for approval', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::Draft]);

    $report->submitForApproval();

    Notification::assertSentTo($admin, ReportSubmittedNotification::class);
});

it('notifies admins on resubmission after a revision', function () {
    Notification::fake();
    $admin = User::factory()->admin()->create();
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::RevisionRequested]);

    $report->resubmitForApproval();

    Notification::assertSentTo($admin, ReportSubmittedNotification::class);
});

it('notifies the engineer and client when a report is published', function () {
    Notification::fake();
    [$report, $engineer, $clientUser] = needApprovalReport();

    $report->approveAndPublish();

    Notification::assertSentTo($engineer, ReportApprovedNotification::class);
    Notification::assertSentTo($clientUser, ReportPublishedNotification::class);
});

it('notifies the engineer when a revision is requested', function () {
    Notification::fake();
    [$report, $engineer] = needApprovalReport();

    $report->requestRevision('Please add site photos');

    Notification::assertSentTo($engineer, RevisionRequestedNotification::class);
    Notification::assertNotSentTo($engineer, ReportApprovedNotification::class);
});

it('does not notify the client on any intermediate transition', function () {
    Notification::fake();
    [$report, $engineer, $clientUser] = needApprovalReport();

    $report->requestRevision('Needs work');

    Notification::assertNothingSentTo($clientUser);
});

it('does not fire the client published notification on an illegal draft publish', function () {
    Notification::fake();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->create(['user_id' => $clientUser->id]);
    $project = Project::factory()->create(['client_id' => $client->id]);
    $site = Site::factory()->create(['project_id' => $project->id]);
    $report = DailyReport::factory()->create([
        'site_id' => $site->id,
        'status' => DailyReportStatus::Draft,
    ]);

    expect(fn () => $report->approveAndPublish())
        ->toThrow(DomainException::class);

    Notification::assertNothingSentTo($clientUser);
});
