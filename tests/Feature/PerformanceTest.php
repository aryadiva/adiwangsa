<?php

use App\Filament\Resources\DailyReportResource;
use App\Filament\Resources\DailyReportResource\Pages\ListDailyReports;
use App\Filament\Resources\GeneratedDocumentResource;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('eager loads subject relations on the generated documents table query', function () {
    $eagerLoads = GeneratedDocumentResource::getEloquentQuery()->getEagerLoads();

    expect($eagerLoads)->toHaveKey('dailyReport.site')
        ->and($eagerLoads)->toHaveKey('project');
});

it('eager loads relations on the daily report table query', function () {
    $eagerLoads = DailyReportResource::scopedQuery()->getEagerLoads();

    expect($eagerLoads)->toHaveKey('site.project')
        ->and($eagerLoads)->toHaveKey('createdBy');
});

it('paginates the daily report list server-side instead of loading every row', function () {
    $admin = adminUser();
    $project = Project::factory()->create();
    $site = Site::factory()->create(['project_id' => $project->id]);
    DailyReport::factory()->count(15)->create(['site_id' => $site->id]);

    $component = Livewire::actingAs($admin)->test(ListDailyReports::class);

    $component->assertOk();

    expect($component->instance()->getTableRecordsPerPage())->toBe(10);
});
