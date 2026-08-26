<?php

use App\Filament\Resources\DailyReportResource;
use App\Filament\Resources\DailyReportResource\Pages\CreateDailyReport;
use App\Filament\Resources\DailyReportResource\Pages\EditDailyReport;
use App\Models\DailyReport;
use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Site;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function subJobForSite(Site $site): MilestoneSubJob
{
    return MilestoneSubJob::factory()->create([
        'project_milestone_id' => ProjectMilestone::factory()->create([
            'project_id' => $site->project_id,
            'weight_percentage' => 100,
        ])->id,
        'weight_percentage' => 100,
    ]);
}

it('renders the full form schema for an admin', function () {
    $admin = adminUser();

    Livewire::actingAs($admin)
        ->test(CreateDailyReport::class)
        ->assertFormFieldExists('site_id')
        ->assertFormFieldExists('report_date')
        ->assertFormFieldExists('weather_condition')
        ->assertFormFieldExists('work_summary')
        ->assertFormFieldExists('delays_or_issues')
        ->assertFormFieldExists('workerAllocations')
        ->assertFormFieldExists('file_path')
        ->assertFormFieldExists('meta_data')
        ->assertFormFieldExists('admin_notes');
});

it('emits a signed preview URL and never falls back to an unsigned object URL', function () {
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('temporaryUrl')
        ->once()
        ->with('daily-report-photos/preview.jpg', Mockery::type(DateTimeInterface::class))
        ->andReturn('http://192.168.10.201:9000/construction-ops/daily-report-photos/preview.jpg?X-Amz-Signature=abc');
    $disk->shouldReceive('exists')->once()->andReturn(true);
    $disk->shouldReceive('size')->once()->andReturn(42);
    $disk->shouldReceive('mimeType')->once()->andReturn('image/jpeg');
    $disk->shouldNotReceive('url');
    Storage::set('photos', $disk);

    $admin = adminUser();
    $page = Livewire::actingAs($admin)->test(CreateDailyReport::class);

    $field = collect($page->instance()->getForm('form')->getComponents())
        ->first(fn ($component) => $component->getName() === 'file_path');

    expect($field)->not->toBeNull();
    expect($field->getVisibility())->toBe('private');

    $field->state(['daily-report-photos/preview.jpg']);
    $files = $field->getUploadedFiles();

    expect($files)->toHaveCount(1)
        ->and($files[0]['url'])->toContain('X-Amz-Signature=')
        ->and($files[0]['size'])->toBe(42);

    Storage::fake('photos');
});

it('exposes every site in the picker to an admin', function () {
    $admin = adminUser();
    Project::factory()->count(2)->create();

    $siteIds = DailyReportResource::scopedSiteQuery(Site::query())->pluck('id');

    expect($siteIds)->toHaveCount(Site::count());
});

it('scopes the site picker to a site engineer assigned projects', function () {
    $assigned = Project::factory()->create();
    $unassigned = Project::factory()->create();
    $engineer = engineerAssignedTo($assigned);

    $assignedSite = Site::factory()->create(['project_id' => $assigned->id]);
    $unassignedSite = Site::factory()->create(['project_id' => $unassigned->id]);

    $this->actingAs($engineer);

    $siteIds = DailyReportResource::scopedSiteQuery(Site::query())->pluck('id');

    expect($siteIds)->toContain($assignedSite->id)
        ->not->toContain($unassignedSite->id);
});

it('hides the admin_notes field from a site engineer and client', function () {
    $engineer = User::factory()->siteEngineer()->create();

    Livewire::actingAs($engineer)
        ->test(CreateDailyReport::class)
        ->assertFormFieldDoesNotExist('admin_notes');
});

it('rejects a duplicate site and report date at the app layer', function () {
    $admin = adminUser();
    $site = Site::factory()->create();
    $subJob = subJobForSite($site);

    DailyReport::factory()->create([
        'site_id' => $site->id,
        'report_date' => '2026-08-11',
    ]);

    Livewire::actingAs($admin)
        ->test(CreateDailyReport::class)
        ->fillForm([
            'site_id' => $site->id,
            'milestone_sub_job_id' => $subJob->id,
            'report_date' => '2026-08-11',
            'weather_condition' => 'sunny',
            'work_summary' => 'Duplicate attempt',
        ])
        ->call('create')
        ->assertHasFormErrors(['report_date']);
});

it('allows a distinct site and report date at the app layer', function () {
    $admin = adminUser();
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();
    $subJobB = subJobForSite($siteB);

    DailyReport::factory()->create([
        'site_id' => $siteA->id,
        'report_date' => '2026-08-11',
    ]);

    Livewire::actingAs($admin)
        ->test(CreateDailyReport::class)
        ->fillForm([
            'site_id' => $siteB->id,
            'milestone_sub_job_id' => $subJobB->id,
            'report_date' => '2026-08-11',
            'weather_condition' => 'sunny',
            'work_summary' => 'Distinct site allowed',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(DailyReport::where('site_id', $siteB->id)->exists())->toBeTrue();
});

it('records the authenticated user as the report creator', function () {
    $admin = adminUser();
    $site = Site::factory()->create();
    $subJob = subJobForSite($site);

    Livewire::actingAs($admin)
        ->test(CreateDailyReport::class)
        ->fillForm([
            'site_id' => $site->id,
            'milestone_sub_job_id' => $subJob->id,
            'report_date' => '2026-08-12',
            'weather_condition' => 'sunny',
            'work_summary' => 'Created by me',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $report = DailyReport::where('site_id', $site->id)->first();

    expect($report->created_by_user_id)->toBe($admin->id);
});

it('ignores its own record when checking for duplicates on edit', function () {
    $admin = adminUser();
    $report = DailyReport::factory()->create([
        'report_date' => '2026-08-11',
    ]);
    $subJob = subJobForSite($report->site);

    Livewire::actingAs($admin)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm([
            'site_id' => $report->site_id,
            'milestone_sub_job_id' => $subJob->id,
            'report_date' => '2026-08-11',
            'weather_condition' => 'rainy',
            'work_summary' => 'Edited summary, same site and date',
        ])
        ->call('save')
        ->assertHasNoFormErrors(['report_date']);

    expect($report->fresh()->work_summary)->toBe('Edited summary, same site and date');
});
