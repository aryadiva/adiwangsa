<?php

use App\Models\Client;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;

function engineerAssignedTo(Project $project): User
{
    $engineer = User::factory()->siteEngineer()->create();
    $engineer->projects()->attach($project);

    return $engineer;
}

function draftFor(Project $project, string $date): array
{
    $engineer = engineerAssignedTo($project);
    $site = Site::factory()->create(['project_id' => $project->id]);

    $report = DailyReport::factory()->create([
        'site_id' => $site->id,
        'created_by_user_id' => $engineer->id,
        'report_date' => $date,
    ]);

    return [$engineer, $site, $report];
}

function clientLinkedTo(Project $project, ?Project $other = null): array
{
    $user = User::factory()->client()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $client->projects()->save($project);

    return [$user, $client];
}

function adminUser(): User
{
    return User::factory()->admin()->create();
}
