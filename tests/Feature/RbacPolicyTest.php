<?php

use App\Enums\DailyReportStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function engineerAssignedTo(Project $project): User
{
    $engineer = User::factory()->siteEngineer()->create();
    $engineer->projects()->attach($project);

    return $engineer;
}

function clientLinkedTo(Project $project, ?Project $other = null): array
{
    $user = User::factory()->client()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $client->projects()->save($project);

    return [$user, $client];
}

it('allows an admin to view any project', function () {
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->create();

    expect($admin->can('view', $project))->toBeTrue();
});

it('allows a site engineer to view only assigned projects', function () {
    $assigned = Project::factory()->create();
    $other = Project::factory()->create();
    $engineer = engineerAssignedTo($assigned);

    expect($engineer->can('view', $assigned))->toBeTrue()
        ->and($engineer->can('view', $other))->toBeFalse();
});

it('allows a client to view only their own projects', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    [$user, $client] = clientLinkedTo($own, $other);

    expect($user->can('view', $own))->toBeTrue()
        ->and($user->can('view', $other))->toBeFalse();
});

it('restricts project mutation to admins', function () {
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->siteEngineer()->create();
    $clientUser = User::factory()->client()->create();
    $project = Project::factory()->create();

    expect($admin->can('create', Project::class))->toBeTrue()
        ->and($engineer->can('create', Project::class))->toBeFalse()
        ->and($clientUser->can('create', Project::class))->toBeFalse();
});

it('scopes site visibility per role', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    $ownSite = Site::factory()->create(['project_id' => $own->id]);
    $otherSite = Site::factory()->create(['project_id' => $other->id]);

    $engineer = engineerAssignedTo($own);
    [$clientUser, $client] = clientLinkedTo($own, $other);
    $admin = User::factory()->admin()->create();

    expect($admin->can('view', $ownSite))->toBeTrue()
        ->and($engineer->can('view', $ownSite))->toBeTrue()
        ->and($engineer->can('view', $otherSite))->toBeFalse()
        ->and($clientUser->can('view', $ownSite))->toBeTrue()
        ->and($clientUser->can('view', $otherSite))->toBeFalse()
        ->and($clientUser->can('update', $ownSite))->toBeFalse();
});

it('lets engineers manage reports only on assigned sites', function () {
    $assigned = Project::factory()->create();
    $other = Project::factory()->create();
    $engineer = engineerAssignedTo($assigned);

    $report = DailyReport::factory()->create(['site_id' => Site::factory()->create(['project_id' => $assigned->id])->id]);
    $foreign = DailyReport::factory()->create(['site_id' => Site::factory()->create(['project_id' => $other->id])->id]);

    expect($engineer->can('create', DailyReport::class))->toBeTrue()
        ->and($engineer->can('update', $report))->toBeTrue()
        ->and($engineer->can('update', $foreign))->toBeFalse()
        ->and($engineer->can('delete', $foreign))->toBeFalse();
});

it('lets a client see only published reports from their sites', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create();
    [$clientUser, $client] = clientLinkedTo($project, $other);

    $site = Site::factory()->create(['project_id' => $project->id]);

    $published = DailyReport::factory()->published()->create(['site_id' => $site->id]);
    $draft = DailyReport::factory()->create(['site_id' => $site->id]);
    $foreignPublished = DailyReport::factory()->published()->create(['site_id' => Site::factory()->create(['project_id' => $other->id])->id]);

    expect($clientUser->can('view', $published))->toBeTrue()
        ->and($clientUser->can('view', $draft))->toBeFalse()
        ->and($clientUser->can('view', $foreignPublished))->toBeFalse()
        ->and($published->status)->toBe(DailyReportStatus::Published);
});

it('blocks clients and grants engineers read-only and admins full access to workers', function () {
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->siteEngineer()->create();
    $clientUser = User::factory()->client()->create();
    $worker = Worker::factory()->create();

    expect($admin->can('create', Worker::class))->toBeTrue()
        ->and($engineer->can('view', $worker))->toBeTrue()
        ->and($engineer->can('update', $worker))->toBeFalse()
        ->and($clientUser->can('view', $worker))->toBeFalse()
        ->and($clientUser->can('viewAny', Worker::class))->toBeFalse();
});

it('scopes milestone visibility like projects', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    $engineer = engineerAssignedTo($own);
    [$clientUser, $client] = clientLinkedTo($own, $other);

    $milestone = ProjectMilestone::create(['project_id' => $own->id, 'title' => 'Foundation', 'target_date' => now()->addDays(30), 'sort_order' => 1]);
    $foreign = ProjectMilestone::create(['project_id' => $other->id, 'title' => 'Roof', 'target_date' => now()->addDays(60), 'sort_order' => 1]);

    expect($engineer->can('view', $milestone))->toBeTrue()
        ->and($engineer->can('view', $foreign))->toBeFalse()
        ->and($clientUser->can('view', $milestone))->toBeTrue()
        ->and($clientUser->can('view', $foreign))->toBeFalse()
        ->and($engineer->can('create', ProjectMilestone::class))->toBeFalse();
});

it('links a client user to a client record through the client_id flag', function () {
    $project = Project::factory()->create();
    [$user, $client] = clientLinkedTo($project, $project);
    $user->refresh();

    expect($user->role)->toBe(UserRole::Client)
        ->and($user->client->id)->toBe($client->id);
});
