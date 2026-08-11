<?php

use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a client reach the read-only client portal dashboard', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $this->actingAs($clientUser)->get('/client/dashboard')->assertOk();
});

it('denies a client access to the admin panel', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $this->actingAs($clientUser)->get('/admin')->assertForbidden();
});

it('denies a client the admin daily reports route', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $this->actingAs($clientUser)->get('/admin/daily-reports')->assertForbidden();
});

it('denies a non-client role access to the client panel', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get('/client/dashboard')->assertForbidden();
});

it('shows the client only published reports on the portal', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);
    $site = Site::factory()->create(['project_id' => $own->id]);

    DailyReport::factory()->published()->create([
        'site_id' => $site->id,
        'work_summary' => 'Published portal text',
    ]);
    DailyReport::factory()->create([
        'site_id' => $site->id,
        'work_summary' => 'Draft should be hidden text',
    ]);

    $this->actingAs($clientUser)->get('/client/dashboard')
        ->assertOk()
        ->assertSee('Published portal text')
        ->assertDontSee('Draft should be hidden text');
});

it('hides a foreign clients published report from the portal', function () {
    $own = Project::factory()->create();
    $other = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $foreignSite = Site::factory()->create(['project_id' => $other->id]);
    DailyReport::factory()->published()->create([
        'site_id' => $foreignSite->id,
        'work_summary' => 'Foreign clients report',
    ]);

    $this->actingAs($clientUser)->get('/client/dashboard')
        ->assertOk()
        ->assertDontSee('Foreign clients report');
});
