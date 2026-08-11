<?php

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an admin view any project', function () {
    $admin = adminUser();
    $project = Project::factory()->create();

    expect($admin->can('view', $project))->toBeTrue()
        ->and($admin->can('viewAny', Project::class))->toBeTrue();
});

it('lets an admin create, update, and delete projects', function () {
    $admin = adminUser();
    $project = Project::factory()->create();

    expect($admin->can('create', Project::class))->toBeTrue()
        ->and($admin->can('update', $project))->toBeTrue()
        ->and($admin->can('delete', $project))->toBeTrue();
});

it('lets a site engineer view only assigned projects', function () {
    $assigned = Project::factory()->create();
    $other = Project::factory()->create();
    $engineer = engineerAssignedTo($assigned);

    expect($engineer->can('view', $assigned))->toBeTrue()
        ->and($engineer->can('view', $other))->toBeFalse();
});

it('denies a site engineer project mutation', function () {
    $project = Project::factory()->create();
    $engineer = engineerAssignedTo($project);

    expect($engineer->can('create', Project::class))->toBeFalse()
        ->and($engineer->can('update', $project))->toBeFalse()
        ->and($engineer->can('delete', $project))->toBeFalse();
});

it('lets a client view only their own projects', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own, $other);

    expect($clientUser->can('view', $own))->toBeTrue()
        ->and($clientUser->can('view', $other))->toBeFalse();
});

it('denies a client project mutation', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    expect($clientUser->can('create', Project::class))->toBeFalse()
        ->and($clientUser->can('update', $own))->toBeFalse()
        ->and($clientUser->can('delete', $own))->toBeFalse();
});

it('scopes site and milestone visibility like projects', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    $engineer = engineerAssignedTo($own);
    [$clientUser] = clientLinkedTo($own, $other);
    $admin = adminUser();

    $ownSite = Site::factory()->create(['project_id' => $own->id]);
    $foreignSite = Site::factory()->create(['project_id' => $other->id]);

    $ownMilestone = ProjectMilestone::create(['project_id' => $own->id, 'title' => 'Foundation', 'target_date' => now()->addDays(30), 'sort_order' => 1]);
    $foreignMilestone = ProjectMilestone::create(['project_id' => $other->id, 'title' => 'Roof', 'target_date' => now()->addDays(60), 'sort_order' => 1]);

    expect($admin->can('view', $ownSite))->toBeTrue()
        ->and($engineer->can('view', $ownSite))->toBeTrue()
        ->and($engineer->can('view', $foreignSite))->toBeFalse()
        ->and($clientUser->can('view', $ownSite))->toBeTrue()
        ->and($clientUser->can('view', $foreignSite))->toBeFalse()
        ->and($clientUser->can('update', $ownSite))->toBeFalse()
        ->and($engineer->can('view', $ownMilestone))->toBeTrue()
        ->and($engineer->can('view', $foreignMilestone))->toBeFalse()
        ->and($clientUser->can('view', $ownMilestone))->toBeTrue()
        ->and($clientUser->can('view', $foreignMilestone))->toBeFalse()
        ->and($engineer->can('create', ProjectMilestone::class))->toBeFalse();
});
