<?php

use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only reports for sites under assigned projects', function () {
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

it('returns nothing for an engineer with no project assignments', function () {
    $engineer = User::factory()->siteEngineer()->create();
    $site = Site::factory()->create();

    DailyReport::factory()->create(['site_id' => $site->id]);

    expect(DailyReport::forSiteEngineer($engineer)->count())->toBe(0);
});

it('cannot reach an unassigned site report by UUID through policy or scope', function () {
    $engineer = User::factory()->siteEngineer()->create();
    $unassigned = Project::factory()->create();
    $foreignSite = Site::factory()->create(['project_id' => $unassigned->id]);
    $foreign = DailyReport::factory()->create(['site_id' => $foreignSite->id]);

    expect($engineer->can('view', $foreign))->toBeFalse()
        ->and($engineer->can('update', $foreign))->toBeFalse()
        ->and($engineer->can('delete', $foreign))->toBeFalse()
        ->and(DailyReport::forSiteEngineer($engineer)->whereKey($foreign->id)->exists())->toBeFalse();
});

it('includes all statuses for the engineer on assigned sites', function () {
    $engineer = User::factory()->siteEngineer()->create();
    $assigned = Project::factory()->create();
    $engineer->projects()->attach($assigned);
    $site = Site::factory()->create(['project_id' => $assigned->id]);

    DailyReport::factory()->published()->create(['site_id' => $site->id]);
    DailyReport::factory()->create(['site_id' => $site->id]);

    expect(DailyReport::forSiteEngineer($engineer)->count())->toBe(2);
});
