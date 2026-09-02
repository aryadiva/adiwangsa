<?php

use App\Enums\DelayEventStatus;
use App\Enums\MilestoneSubJobStatus;
use App\Enums\UserRole;
use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\SubJobDelayEvent;
use App\Models\User;
use App\Services\DelayCascadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function delayedProjectFixture(int $threshold = 2): array
{
    $asOf = Carbon::create(2026, 9, 1, 12);

    $project = Project::factory()->create([
        'start_date' => $asOf->copy()->subDays(40),
        'target_end_date' => $asOf->copy()->addDays(60),
        'delay_threshold_days' => $threshold,
    ]);

    $origin = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'title' => 'Origin',
        'weight_percentage' => 50,
        'sort_order' => 1,
        'start_date' => $asOf->copy()->subDays(30),
        'target_date' => $asOf->copy()->addDays(10),
    ]);

    // Planned end (start + working_days) = 16 days before asOf.
    // Cumulative delay = 16 days → breach of any small threshold.
    $subJob = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $origin->id,
        'title' => 'Late Sub-Job',
        'weight_percentage' => 100,
        'start_date' => $asOf->copy()->subDays(30),
        'working_days' => 14,
        'quantity' => 100,
        'status' => MilestoneSubJobStatus::InProgress,
        'sort_order' => 1,
    ]);

    $subsequentA = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'title' => 'Subsequent A',
        'weight_percentage' => 30,
        'sort_order' => 2,
        'start_date' => $asOf->copy()->addDays(20),
        'target_date' => $asOf->copy()->addDays(40),
    ]);

    $subsequentB = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'title' => 'Subsequent B',
        'weight_percentage' => 20,
        'sort_order' => 3,
        'start_date' => $asOf->copy()->addDays(45),
        'target_date' => $asOf->copy()->addDays(55),
    ]);

    return [$asOf, $project, $origin, $subJob, $subsequentA, $subsequentB];
}

it('detects a threshold breach and cascades dates atomically', function () {
    [$asOf, $project, $origin, $subJob, $subsequentA, $subsequentB] = delayedProjectFixture();

    $originalTargetA = $subsequentA->target_date->copy();
    $originalTargetB = $subsequentB->target_date->copy();
    $originalEnd = $project->target_end_date->copy();

    $created = DelayCascadeService::runDetection($asOf);

    expect($created)->toHaveCount(1);

    $event = SubJobDelayEvent::find($created[0]);
    expect($event->status)->toBe(DelayEventStatus::Red)
        ->and($event->delay_days)->toBeGreaterThan(2)
        ->and($event->triggered_at->toDateString())->toBe($asOf->toDateString())
        ->and($subsequentA->refresh()->target_date->toDateString())
        ->toBe($originalTargetA->addDays($event->delay_days)->toDateString())
        ->and($subsequentB->refresh()->target_date->toDateString())
        ->toBe($originalTargetB->addDays($event->delay_days)->toDateString())
        ->and($project->refresh()->target_end_date->toDateString())
        ->toBe($originalEnd->addDays($event->delay_days)->toDateString());
});

it('does not shift the originating milestone target date', function () {
    [$asOf, , $origin] = delayedProjectFixture();

    $original = $origin->target_date->copy();

    DelayCascadeService::runDetection($asOf);

    expect($origin->refresh()->target_date->toDateString())->toBe($original->toDateString());
});

it('does not create a second red event while one is still active', function () {
    [$asOf, , , $subJob] = delayedProjectFixture();

    DelayCascadeService::runDetection($asOf);
    DelayCascadeService::runDetection($asOf);

    expect(SubJobDelayEvent::query()->where('milestone_sub_job_id', $subJob->id)->count())->toBe(1);
});

it('does not breach when the delay is within the threshold', function () {
    [$asOf, , , $subJob] = delayedProjectFixture(threshold: 100);

    $created = DelayCascadeService::runDetection($asOf);

    expect($created)->toBe([])
        ->and(DelayCascadeService::isBreached($subJob, $asOf))->toBeFalse();
});

it('ignores completed sub-jobs', function () {
    $asOf = Carbon::create(2026, 9, 1, 12);

    $project = Project::factory()->create([
        'start_date' => $asOf->copy()->subDays(40),
        'target_end_date' => $asOf->copy()->addDays(60),
        'delay_threshold_days' => 2,
    ]);

    MilestoneSubJob::factory()->create([
        'project_milestone_id' => ProjectMilestone::factory()->create([
            'project_id' => $project->id,
            'sort_order' => 5,
        ])->id,
        'start_date' => $asOf->copy()->subDays(30),
        'working_days' => 1,
        'status' => MilestoneSubJobStatus::Completed,
    ]);

    $created = DelayCascadeService::runDetection($asOf);

    expect($created)->toBe([]);
});

it('runs the full red to yellow to green cycle', function () {
    [$asOf, , , $subJob] = delayedProjectFixture();

    $event = DelayCascadeService::createEventWithCascade($subJob, $asOf);
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $event->submitMitigationPlan('Add a second crew and extend the workday.', $admin);

    expect($event->refresh()->status)->toBe(DelayEventStatus::Yellow)
        ->and($event->mitigation_plan)->toBe('Add a second crew and extend the workday.')
        ->and($event->mitigation_submitted_by_user_id)->toBe($admin->id)
        ->and($event->resolved_at)->toBeNull();

    $event->markRecovered($admin);

    expect($event->refresh()->status)->toBe(DelayEventStatus::Green)
        ->and($event->resolved_at)->not->toBeNull();
});

it('rejects illegal transitions', function () {
    [$asOf, , , $subJob] = delayedProjectFixture();

    $event = DelayCascadeService::createEventWithCascade($subJob, $asOf);

    // red → green skips the mitigation plan step.
    expect(fn () => $event->markRecovered())
        ->toThrow(DomainException::class);

    $event->submitMitigationPlan('Plan.');

    // yellow cannot re-submit a mitigation plan.
    expect(fn () => $event->submitMitigationPlan('Another plan.'))
        ->toThrow(DomainException::class);

    $event->markRecovered();

    // green is terminal.
    expect(fn () => $event->markRecovered())
        ->toThrow(DomainException::class)
        ->and(fn () => $event->submitMitigationPlan('Again.'))
        ->toThrow(DomainException::class);
});

it('rejects an empty mitigation plan', function () {
    [$asOf, , , $subJob] = delayedProjectFixture();

    $event = DelayCascadeService::createEventWithCascade($subJob, $asOf);

    expect(fn () => $event->submitMitigationPlan('   '))
        ->toThrow(InvalidArgumentException::class)
        ->and($event->refresh()->status)->toBe(DelayEventStatus::Red);
});

it('creates a new red event on recurrence after green', function () {
    [$asOf, , , $subJob] = delayedProjectFixture();

    $first = DelayCascadeService::createEventWithCascade($subJob, $asOf);
    $first->submitMitigationPlan('Overtime schedule.');
    $first->markRecovered();

    $later = $asOf->copy()->addDays(5);

    $created = DelayCascadeService::runDetection($later);

    expect($created)->toHaveCount(1)
        ->and($created[0])->not->toBe($first->id)
        ->and(SubJobDelayEvent::query()->where('milestone_sub_job_id', $subJob->id)->count())->toBe(2)
        ->and(SubJobDelayEvent::find($created[0])->status)->toBe(DelayEventStatus::Red);
});

function delayedProjectFixtureWithRoles(): array
{
    [$asOf, $project, $origin, $subJob] = delayedProjectFixture();

    DelayCascadeService::createEventWithCascade($subJob, $asOf);
    $event = SubJobDelayEvent::query()->firstOrFail();

    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $engineer = User::factory()->create(['role' => UserRole::SiteEngineer]);
    $engineer->projects()->attach($project);

    $outsider = User::factory()->create(['role' => UserRole::SiteEngineer]);

    return [$event, $admin, $engineer, $outsider];
}

it('lets admins view and manage delay events', function () {
    [$event, $admin] = delayedProjectFixtureWithRoles();

    expect($admin->can('view', $event))->toBeTrue()
        ->and($admin->can('update', $event))->toBeTrue()
        ->and($admin->can('submitMitigationPlan', $event))->toBeTrue()
        ->and($admin->can('markRecovered', $event))->toBeFalse();

    $event->submitMitigationPlan('Plan.', $admin);

    expect($admin->can('markRecovered', $event->refresh()))->toBeTrue()
        ->and($admin->can('submitMitigationPlan', $event))->toBeFalse();
});

it('lets an assigned site engineer view but not manage delay events', function () {
    [$event, , $engineer] = delayedProjectFixtureWithRoles();

    expect($engineer->can('view', $event))->toBeTrue()
        ->and($engineer->can('viewAny', $event))->toBeTrue()
        ->and($engineer->can('update', $event))->toBeFalse()
        ->and($engineer->can('submitMitigationPlan', $event))->toBeFalse()
        ->and($engineer->can('markRecovered', $event))->toBeFalse()
        ->and($engineer->can('delete', $event))->toBeFalse();
});

it('denies an unassigned site engineer all access', function () {
    [$event, , , $outsider] = delayedProjectFixtureWithRoles();

    expect($outsider->can('view', $event))->toBeFalse();
});

it('denies a client all access', function () {
    [$event] = delayedProjectFixtureWithRoles();

    $clientUser = User::factory()->create(['role' => UserRole::Client]);

    expect($clientUser->can('viewAny', $event))->toBeFalse()
        ->and($clientUser->can('view', $event))->toBeFalse()
        ->and($clientUser->can('update', $event))->toBeFalse();
});
