<?php

use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an admin to list the daily reports resource', function () {
    $admin = adminUser();
    Project::factory()->count(2)->create();

    $this->actingAs($admin)->get('/admin/daily-reports')->assertOk();
});

it('scopes the reports list for a site engineer to assigned sites only', function () {
    $assigned = Project::factory()->create();
    $unassigned = Project::factory()->create();
    $engineer = engineerAssignedTo($assigned);

    $site = Site::factory()->create(['project_id' => $assigned->id]);
    $foreignSite = Site::factory()->create(['project_id' => $unassigned->id]);

    DailyReport::factory()->count(3)->create(['site_id' => $site->id]);
    $hidden = DailyReport::factory()->create(['site_id' => $foreignSite->id]);

    $this->actingAs($engineer)->get('/admin/daily-reports')
        ->assertOk()
        ->assertDontSee('PRJ');
});

it('shows a client only published reports from their projects', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);
    $site = Site::factory()->create(['project_id' => $own->id]);

    DailyReport::factory()->published()->create([
        'site_id' => $site->id,
        'work_summary' => 'Published summary text',
    ]);
    DailyReport::factory()->create([
        'site_id' => $site->id,
        'work_summary' => 'Draft should be hidden text',
    ]);

    $this->actingAs($clientUser)->get('/client/dashboard')
        ->assertOk()
        ->assertSee('Published summary text')
        ->assertDontSee('Draft should be hidden text');
});

it('hides a foreign report from a site engineer by UUID', function () {
    Project::factory()->create();
    $unassigned = Project::factory()->create();
    $engineer = engineerAssignedTo(Project::factory()->create());

    $foreignSite = Site::factory()->create(['project_id' => $unassigned->id]);
    $foreign = DailyReport::factory()->create(['site_id' => $foreignSite->id]);

    $this->actingAs($engineer)->get("/admin/daily-reports/{$foreign->id}/edit")
        ->assertNotFound();
});

it('denies a client the create page of the reports resource', function () {
    $own = Project::factory()->create();
    [$clientUser] = clientLinkedTo($own);

    $this->actingAs($clientUser)->get('/admin/daily-reports/create')
        ->assertForbidden();
});
