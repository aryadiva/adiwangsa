<?php

use App\Filament\Resources\DailyReportResource\Pages\EditDailyReport;
use App\Models\DailyReport;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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

it('auto-saves draft scalar fields via saveDraft', function () {
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm([
            'work_summary' => 'Auto-saved summary',
            'delays_or_issues' => 'Rained in the afternoon',
        ])
        ->call('saveDraft')
        ->assertSet('draftSaveFailed', false)
        ->assertSet('draftLastSavedAt', fn ($value) => filled($value));

    expect($report->fresh()->work_summary)->toBe('Auto-saved summary')
        ->and($report->fresh()->delays_or_issues)->toBe('Rained in the afternoon');
});

it('does not auto-save a non-draft report', function () {
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');
    $report->update(['status' => 'published']);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(['work_summary' => 'Should never persist'])
        ->call('saveDraft')
        ->assertSet('draftSaveFailed', false)
        ->assertSet('draftLastSavedAt', null);

    expect($report->fresh()->work_summary)->not->toBe('Should never persist');
});

it('flags a failed auto-save for retry and recovers on the next attempt', function () {
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');

    $throwOnce = true;
    DailyReport::saving(function () use (&$throwOnce): void {
        if ($throwOnce) {
            $throwOnce = false;
            throw new Exception('simulated disconnect');
        }
    });

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(['work_summary' => 'Retry-friendly content'])
        ->call('saveDraft')
        ->assertSet('draftSaveFailed', true)
        ->call('saveDraft')
        ->assertSet('draftSaveFailed', false)
        ->assertSet('draftLastSavedAt', fn ($value) => filled($value));

    expect($report->fresh()->work_summary)->toBe('Retry-friendly content');
});

it('skips auto-save when the form state has not changed', function () {
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');

    $component = Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->call('saveDraft')
        ->assertSet('draftSaveFailed', false);

    $component->call('saveDraft');

    expect($report->fresh()->work_summary)->toBe($report->work_summary);
});

it('polls for auto-save only on a draft edit form', function () {
    $project = Project::factory()->create();
    [$engineer, , $draft] = draftFor($project, '2026-08-12');

    $published = DailyReport::factory()->published()->create([
        'site_id' => $draft->site_id,
        'created_by_user_id' => $engineer->id,
        'report_date' => '2026-08-13',
    ]);

    $this->actingAs($engineer)
        ->get("/admin/daily-reports/{$draft->id}/edit")
        ->assertOk()
        ->assertSee('wire:poll=')
        ->assertSee('saveDraft');

    $this->actingAs($engineer)
        ->get("/admin/daily-reports/{$published->id}/edit")
        ->assertOk()
        ->assertDontSee('wire:poll');
});
