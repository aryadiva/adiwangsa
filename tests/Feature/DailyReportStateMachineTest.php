<?php

use App\Enums\DailyReportStatus;
use App\Models\DailyReport;
use App\Models\User;
use App\Models\Worker;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function draftReport(): DailyReport
{
    return DailyReport::factory()->create(['status' => DailyReportStatus::Draft]);
}

it('transitions draft to need_approval via submitForApproval', function () {
    $report = draftReport();

    $report->submitForApproval();

    expect($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval);
});

it('transitions need_approval to published via approveAndPublish', function () {
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::NeedApproval]);
    $admin = User::factory()->admin()->create();

    $report->approveAndPublish($admin->id);

    expect($report->fresh()->status)->toBe(DailyReportStatus::Published)
        ->and($report->fresh()->reviewed_by_user_id)->toBe($admin->id);
});

it('transitions need_approval to revision_requested via requestRevision', function () {
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::NeedApproval]);

    $report->requestRevision('Please add site photos');

    expect($report->fresh()->status)->toBe(DailyReportStatus::RevisionRequested)
        ->and($report->fresh()->admin_notes)->toBe('Please add site photos');
});

it('transitions revision_requested to need_approval and writes a snapshot first', function () {
    $report = DailyReport::factory()->create([
        'status' => DailyReportStatus::RevisionRequested,
        'work_summary' => 'Revised content',
    ]);
    $engineer = User::factory()->create();
    $worker = Worker::factory()->create();
    $report->workerAllocations()->create([
        'worker_id' => $worker->id,
        'hours_worked' => 8,
        'remarks' => 'Mason',
    ]);

    $report->resubmitForApproval($engineer->id);

    expect($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval)
        ->and($report->revisions()->count())->toBe(1);

    $snapshot = $report->revisions()->first()->snapshot;

    expect($snapshot['work_summary'])->toBe('Revised content')
        ->and($snapshot['worker_allocations'][0]['worker_id'])->toBe($worker->id)
        ->and($report->revisions()->first()->edited_by_user_id)->toBe($engineer->id);
});

it('rejects an illegal draft to published transition', function () {
    $report = draftReport();

    expect(fn () => $report->approveAndPublish())->toThrow(DomainException::class)
        ->and($report->fresh()->status)->toBe(DailyReportStatus::Draft);
});

it('rejects an illegal need_approval to draft transition', function () {
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::NeedApproval]);

    expect(fn () => $report->submitForApproval())->toThrow(DomainException::class)
        ->and($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval);
});

it('treats published as terminal with no allowed transitions', function () {
    $report = DailyReport::factory()->create(['status' => DailyReportStatus::Published]);

    expect($report->allowedNextStatuses())->toBe([])
        ->and(fn () => $report->approveAndPublish())->toThrow(DomainException::class)
        ->and(fn () => $report->requestRevision())->toThrow(DomainException::class)
        ->and($report->fresh()->status)->toBe(DailyReportStatus::Published);
});
