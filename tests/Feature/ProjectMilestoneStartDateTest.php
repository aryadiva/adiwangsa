<?php

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Support\ScheduleValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('stores a start date on a project milestone', function () {
    $project = Project::factory()->create(['start_date' => '2026-01-01']);
    $milestone = ProjectMilestone::factory()->create([
        'project_id' => $project->id,
        'start_date' => '2026-02-01',
    ]);

    expect($milestone->start_date->toDateString())->toBe('2026-02-01');
});

it('accepts a milestone start date equal to or after the project start date', function () {
    $projectStart = Carbon::parse('2026-03-01');

    expect(ScheduleValidator::startDateOnOrAfter(Carbon::parse('2026-03-01'), $projectStart))->toBeTrue()
        ->and(ScheduleValidator::startDateOnOrAfter(Carbon::parse('2026-04-01'), $projectStart))->toBeTrue();
});

it('rejects a milestone start date before the project start date', function () {
    $projectStart = Carbon::parse('2026-03-01');

    expect(ScheduleValidator::startDateOnOrAfter(Carbon::parse('2026-02-01'), $projectStart))->toBeFalse();
});

it('accepts a null milestone start date or project start date guard', function () {
    expect(ScheduleValidator::startDateOnOrAfter(null, Carbon::parse('2026-03-01')))->toBeTrue()
        ->and(ScheduleValidator::startDateOnOrAfter(Carbon::parse('2026-02-01'), null))->toBeTrue();
});

it('enforces the ordering end-to-end: a too-early milestone start is caught by the guard', function () {
    $project = Project::factory()->create(['start_date' => '2026-05-01']);
    $milestone = ProjectMilestone::factory()->make([
        'project_id' => $project->id,
        'start_date' => '2026-04-01',
    ]);

    expect(ScheduleValidator::startDateOnOrAfter($milestone->start_date, $project->start_date))->toBeFalse();
});
