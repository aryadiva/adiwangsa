<?php

use App\Enums\UserRole;
use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Notifications\WeightIncompleteNotification;
use App\Services\MilestoneWeightNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function weightAdmins(): array
{
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin2 = User::factory()->create(['role' => UserRole::Admin]);

    return [$admin, $admin2];
}

it('creates a persistent notification for all admins when a milestone set is incomplete', function () {
    [$admin, $admin2] = weightAdmins();
    $project = Project::factory()->create();

    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 60]);

    foreach ([$admin, $admin2] as $user) {
        $notification = $user->notifications()
            ->whereType(WeightIncompleteNotification::class)
            ->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['project_id'])->toBe($project->getKey());
    }
});

it('does not notify when the set reaches exactly 100', function () {
    [$admin] = weightAdmins();
    $project = Project::factory()->create();

    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 40]);
    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 60]);

    expect($admin->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(0);
});

it('does not notify while a project has no milestones', function () {
    [$admin] = weightAdmins();
    Project::factory()->create();

    expect($admin->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(0);
});

it('does not duplicate the notification on repeated saves while still incomplete', function () {
    [$admin] = weightAdmins();
    $project = Project::factory()->create();

    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 60]);
    $milestone->update(['description' => 'touched']);

    expect($admin->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(1);
});

it('deletes the notification once the milestone set reaches 100', function () {
    [$admin] = weightAdmins();
    $project = Project::factory()->create();

    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 60]);

    expect($admin->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(1);

    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 40]);

    expect($admin->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(0);
});

it('flags an incomplete sub-job set and clears when it reaches 100', function () {
    [$admin] = weightAdmins();
    $project = Project::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 100]);

    // Sub-jobs under the milestone must also total 100.
    $subJobA = MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'weight_percentage' => 60,
    ]);

    $notification = MilestoneWeightNotificationService::incompleteSets($project);

    expect($notification)->toHaveCount(1);

    MilestoneSubJob::factory()->create([
        'project_milestone_id' => $milestone->id,
        'weight_percentage' => 40,
    ]);

    $notification = $admin->notifications()
        ->where('type', WeightIncompleteNotification::class)
        ->where('data->project_id', $project->getKey())
        ->first();

    expect($notification)->toBeNull();
});

it('only notifies admin role users', function () {
    [$admin] = weightAdmins();
    $engineer = User::factory()->create(['role' => UserRole::SiteEngineer]);
    $project = Project::factory()->create();

    ProjectMilestone::factory()->create(['project_id' => $project->id, 'weight_percentage' => 60]);

    expect($admin->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(1)
        ->and($engineer->notifications()->where('type', WeightIncompleteNotification::class)->count())->toBe(0);
});
