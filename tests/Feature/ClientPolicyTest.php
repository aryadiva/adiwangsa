<?php

use App\Models\Client;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an admin view, create, update, and delete clients', function () {
    $admin = adminUser();
    $client = Client::factory()->create();

    expect($admin->can('view', $client))->toBeTrue()
        ->and($admin->can('viewAny', Client::class))->toBeTrue()
        ->and($admin->can('create', Client::class))->toBeTrue()
        ->and($admin->can('update', $client))->toBeTrue()
        ->and($admin->can('delete', $client))->toBeTrue();
});

it('denies site engineers any client access', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->create();
    $engineer = engineerAssignedTo($project);

    expect($engineer->can('view', $client))->toBeFalse()
        ->and($engineer->can('viewAny', Client::class))->toBeFalse()
        ->and($engineer->can('create', Client::class))->toBeFalse()
        ->and($engineer->can('update', $client))->toBeFalse()
        ->and($engineer->can('delete', $client))->toBeFalse();
});

it('denies client users any client access', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);
    $client = Client::factory()->create();

    expect($clientUser->can('view', $client))->toBeFalse()
        ->and($clientUser->can('viewAny', Client::class))->toBeFalse()
        ->and($clientUser->can('create', Client::class))->toBeFalse();
});
