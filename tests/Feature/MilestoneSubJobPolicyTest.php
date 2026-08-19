<?php

use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function milestoneWithSubJob(): array
{
    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 100]);
    $subJob = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestone->id, 'weight_percentage' => 100]);

    return [$project, $milestone, $subJob];
}

it('lets an admin view any sub-job', function () {
    $admin = adminUser();
    [, , $subJob] = milestoneWithSubJob();

    expect($admin->can('viewAny', MilestoneSubJob::class))->toBeTrue()
        ->and($admin->can('view', $subJob))->toBeTrue();
});

it('lets an admin create, update, and delete sub-jobs', function () {
    $admin = adminUser();
    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 100]);
    $subJob = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestone->id, 'weight_percentage' => 100]);

    expect($admin->can('create', MilestoneSubJob::class))->toBeTrue()
        ->and($admin->can('update', $subJob))->toBeTrue()
        ->and($admin->can('delete', $subJob))->toBeTrue();
});

it('lets a site engineer view sub-jobs of assigned projects only', function () {
    $assigned = Project::factory()->create();
    $other = Project::factory()->create();
    $milestoneA = ProjectMilestone::factory()->create(['project_id' => $assigned->id, 'weight_percentage' => 100]);
    $milestoneB = ProjectMilestone::factory()->create(['project_id' => $other->id, 'weight_percentage' => 100]);
    $subJobA = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestoneA->id, 'weight_percentage' => 100]);
    $subJobB = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestoneB->id, 'weight_percentage' => 100]);
    $engineer = engineerAssignedTo($assigned);

    expect($engineer->can('view', $subJobA))->toBeTrue()
        ->and($engineer->can('view', $subJobB))->toBeFalse();
});

it('denies a site engineer sub-job mutation', function () {
    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 100]);
    $subJob = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestone->id, 'weight_percentage' => 100]);
    $engineer = engineerAssignedTo($project);

    expect($engineer->can('create', MilestoneSubJob::class))->toBeFalse()
        ->and($engineer->can('update', $subJob))->toBeFalse()
        ->and($engineer->can('delete', $subJob))->toBeFalse();
});

it('lets a client view sub-jobs of their own project only', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    $milestoneOwn = ProjectMilestone::factory()->create(['project_id' => $own->id, 'weight_percentage' => 100]);
    $milestoneOther = ProjectMilestone::factory()->create(['project_id' => $other->id, 'weight_percentage' => 100]);
    $subJobOwn = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestoneOwn->id, 'weight_percentage' => 100]);
    $subJobOther = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestoneOther->id, 'weight_percentage' => 100]);
    [$clientUser] = clientLinkedTo($own);

    expect($clientUser->can('view', $subJobOwn))->toBeTrue()
        ->and($clientUser->can('view', $subJobOther))->toBeFalse();
});

it('denies a client sub-job mutation', function () {
    $own = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $own->id, 'weight_percentage' => 100]);
    $subJob = MilestoneSubJob::factory()->create(['project_milestone_id' => $milestone->id, 'weight_percentage' => 100]);
    [$clientUser] = clientLinkedTo($own);

    expect($clientUser->can('create', MilestoneSubJob::class))->toBeFalse()
        ->and($clientUser->can('update', $subJob))->toBeFalse()
        ->and($clientUser->can('delete', $subJob))->toBeFalse();
});
