<?php

use App\Enums\DailyReportStatus;
use App\Models\Client;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes reports to sites under the engineer project assignments', function () {
    $engineer = User::factory()->siteEngineer()->create();
    $assigned = Project::factory()->create();
    $unassigned = Project::factory()->create();
    $engineer->projects()->attach($assigned);

    $site = Site::factory()->create(['project_id' => $assigned->id]);
    $foreignSite = Site::factory()->create(['project_id' => $unassigned->id]);

    DailyReport::factory()->count(3)->create(['site_id' => $site->id]);
    DailyReport::factory()->count(2)->create(['site_id' => $foreignSite->id]);

    $visible = DailyReport::forSiteEngineer($engineer)->get();

    expect($visible)->toHaveCount(3)
        ->and($visible->pluck('site_id')->unique())->each->toBe($site->id);
});

it('excludes reports of unassigned sites for the engineer', function () {
    $engineer = User::factory()->siteEngineer()->create();
    $unassigned = Project::factory()->create();
    $foreignSite = Site::factory()->create(['project_id' => $unassigned->id]);

    DailyReport::factory()->count(2)->create(['site_id' => $foreignSite->id]);

    expect(DailyReport::forSiteEngineer($engineer)->count())->toBe(0);
});

it('scopes reports to published status on the client projects only', function () {
    $client = Client::factory()->create();
    $clientUser = User::factory()->client()->create();
    $client->update(['user_id' => $clientUser->id]);

    $own = Project::factory()->create(['client_id' => $client->id]);
    $other = Project::factory()->create();

    $ownSite = Site::factory()->create(['project_id' => $own->id]);
    $otherSite = Site::factory()->create(['project_id' => $other->id]);

    DailyReport::factory()->published()->create(['site_id' => $ownSite->id]);
    DailyReport::factory()->create(['site_id' => $ownSite->id]);
    DailyReport::factory()->published()->create(['site_id' => $otherSite->id]);

    $visible = DailyReport::forClient($clientUser)->get();

    expect($visible)->toHaveCount(1)
        ->and($visible->first()->status)->toBe(DailyReportStatus::Published);
});

it('returns nothing for a client with no projects', function () {
    $clientUser = User::factory()->client()->create();
    Client::factory()->create(['user_id' => $clientUser->id]);

    expect(DailyReport::forClient($clientUser)->count())->toBe(0);
});
