<?php

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Rules\MilestoneStartDateRule;
use App\Rules\MilestoneWeightsTotalRule;
use App\Rules\SubJobsWeightsTotalRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function collectFailures(callable $run): array
{
    $failures = [];

    $run(function (string $message) use (&$failures): void {
        $failures[] = $message;
    });

    return $failures;
}

it('accepts a partial sub-job set (single non-100 row)', function () {
    $rule = new SubJobsWeightsTotalRule;

    expect(collectFailures(fn ($fail) => $rule->validate('subJobs', [
        ['weight_percentage' => 60],
    ], $fail)))->toBeEmpty();
});

it('accepts a sub-job set that totals exactly 100', function () {
    $rule = new SubJobsWeightsTotalRule;

    expect(collectFailures(fn ($fail) => $rule->validate('subJobs', [
        ['weight_percentage' => 40],
        ['weight_percentage' => 60],
    ], $fail)))->toBeEmpty();
});

it('rejects a sub-job set that exceeds 100', function () {
    $rule = new SubJobsWeightsTotalRule;

    expect(collectFailures(fn ($fail) => $rule->validate('subJobs', [
        ['weight_percentage' => 60],
        ['weight_percentage' => 50],
    ], $fail)))->not->toBeEmpty();
});

it('rejects adding a sub-job when siblings already tally to 100', function () {
    $rule = new SubJobsWeightsTotalRule;

    expect(collectFailures(fn ($fail) => $rule->validate('subJobs', [
        ['weight_percentage' => 100],
        ['weight_percentage' => 5],
    ], $fail)))->not->toBeEmpty();
});

it('accepts an empty or missing sub-job set', function () {
    $rule = new SubJobsWeightsTotalRule;

    expect(collectFailures(fn ($fail) => $rule->validate('subJobs', [], $fail)))->toBeEmpty()
        ->and(collectFailures(fn ($fail) => $rule->validate('subJobs', null, $fail)))->toBeEmpty();
});

it('accepts a partial milestone weight (no restrictions on individual rows)', function () {
    $project = Project::factory()->create();

    $rule = new MilestoneWeightsTotalRule($project);
    $failures = collectFailures(fn ($fail) => $rule->validate('weight_percentage', 20, $fail));

    expect($failures)->toBeEmpty();
});

it('accepts milestone weights that make the set total exactly 100', function () {
    $project = Project::factory()->create();
    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 40]);

    $rule = new MilestoneWeightsTotalRule($project);
    $failures = collectFailures(fn ($fail) => $rule->validate('weight_percentage', 60, $fail));

    expect($failures)->toBeEmpty();
});

it('rejects a milestone weight that pushes the set past 100', function () {
    $project = Project::factory()->create();
    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 60]);

    $rule = new MilestoneWeightsTotalRule($project);
    $failures = collectFailures(fn ($fail) => $rule->validate('weight_percentage', 50, $fail));

    expect($failures)->not->toBeEmpty();
});

it('rejects adding a milestone when siblings already tally to 100', function () {
    $project = Project::factory()->create();
    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 100]);

    $rule = new MilestoneWeightsTotalRule($project);
    $failures = collectFailures(fn ($fail) => $rule->validate('weight_percentage', 5, $fail));

    expect($failures)->not->toBeEmpty();
});

it('does not double-count the milestone being edited', function () {
    $project = Project::factory()->create();
    $existing = ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 100]);

    $rule = new MilestoneWeightsTotalRule($project, $existing->getKey());

    expect(collectFailures(fn ($fail) => $rule->validate('weight_percentage', 100, $fail)))->toBeEmpty();
});

it('rejects a milestone start date before the project start date', function () {
    $rule = new MilestoneStartDateRule('2026-03-01');
    $failures = collectFailures(fn ($fail) => $rule->validate('start_date', '2026-02-01', $fail));

    expect($failures)->not->toBeEmpty();
});

it('accepts a milestone start date on or after the project start date', function () {
    $rule = new MilestoneStartDateRule('2026-03-01');

    expect(collectFailures(fn ($fail) => $rule->validate('start_date', '2026-03-01', $fail)))->toBeEmpty()
        ->and(collectFailures(fn ($fail) => $rule->validate('start_date', '2026-04-01', $fail)))->toBeEmpty();
});
