<?php

use App\Models\Client;
use App\Models\Project;
use App\Models\User;

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

function adminUser(): User
{
    return User::factory()->admin()->create();
}
