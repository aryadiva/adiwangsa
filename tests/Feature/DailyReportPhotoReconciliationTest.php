<?php

use App\Filament\Resources\DailyReportResource\Pages\EditDailyReport;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('photos');
});

function reportWithPhotoPath(string $path): array
{
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');

    return [$engineer, $report];
}

it('does not create duplicate photo rows when a report is saved repeatedly', function () {
    [$engineer, $report] = reportWithPhotoPath('daily-report-photos/abc.jpg');
    $path = 'daily-report-photos/abc.jpg';
    Storage::disk('photos')->put($path, 'image-data');

    $formData = [
        'file_path' => [$path],
        'site_id' => $report->site_id,
        'report_date' => '2026-08-12',
        'weather_condition' => 'sunny',
        'work_summary' => 'Reconciliation',
    ];

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm($formData)
        ->call('save');

    expect($report->fresh()->photos()->count())->toBe(1)
        ->and($report->fresh()->photos()->pluck('file_path'))->toContain($path);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm($formData)
        ->call('save');

    expect($report->fresh()->photos()->count())->toBe(1);
});

it('removes a photo row when its path is removed from the form', function () {
    [$engineer, $report] = reportWithPhotoPath('daily-report-photos/keep.jpg');
    Storage::disk('photos')->put('daily-report-photos/keep.jpg', 'image-data');

    $report->photos()->create([
        'file_path' => 'daily-report-photos/keep.jpg',
        'thumbnail_path' => 'daily-report-photos/thumbs/keep.jpg',
        'file_size_bytes' => 10,
    ]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm([
            'file_path' => [],
            'site_id' => $report->site_id,
            'report_date' => '2026-08-12',
            'weather_condition' => 'sunny',
            'work_summary' => 'Removed photo',
        ])
        ->call('save');

    expect($report->fresh()->photos()->count())->toBe(0);
});

it('dedupes pre-existing duplicate photo rows on save', function () {
    [$engineer, $report] = reportWithPhotoPath('daily-report-photos/dup.jpg');
    Storage::disk('photos')->put('daily-report-photos/dup.jpg', 'image-data');

    $report->photos()->create(['file_path' => 'daily-report-photos/dup.jpg', 'thumbnail_path' => 'x', 'file_size_bytes' => 1]);
    $report->photos()->create(['file_path' => 'daily-report-photos/dup.jpg', 'thumbnail_path' => 'x', 'file_size_bytes' => 1]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm([
            'file_path' => ['daily-report-photos/dup.jpg'],
            'site_id' => $report->site_id,
            'report_date' => '2026-08-12',
            'weather_condition' => 'sunny',
            'work_summary' => 'Dedupe',
        ])
        ->call('save');

    expect($report->fresh()->photos()->count())->toBe(1);
});

it('exposes missing photo paths so the UI can warn the user', function () {
    [$engineer, $report] = reportWithPhotoPath('daily-report-photos/missing.jpg');

    $report->photos()->create([
        'file_path' => 'daily-report-photos/missing.jpg',
        'thumbnail_path' => 'daily-report-photos/thumbs/missing.jpg',
        'file_size_bytes' => 10,
    ]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->assertSet('missingPhotoPaths', ['daily-report-photos/missing.jpg']);
});

it('prunes daily_report_photos rows whose file is missing from storage', function () {
    [$engineer, $report] = reportWithPhotoPath('daily-report-photos/orphan.jpg');

    // Not placed on the (faked) disk → orphaned.
    $orphan = $report->photos()->create([
        'file_path' => 'daily-report-photos/orphan.jpg',
        'thumbnail_path' => 'daily-report-photos/thumbs/orphan.jpg',
        'file_size_bytes' => 10,
    ]);

    // Present on the disk → must be retained.
    Storage::disk('photos')->put('daily-report-photos/ok.jpg', 'image-data');
    $keep = $report->photos()->create([
        'file_path' => 'daily-report-photos/ok.jpg',
        'thumbnail_path' => 'daily-report-photos/thumbs/ok.jpg',
        'file_size_bytes' => 10,
    ]);

    $this->artisan('photos:prune')
        ->expectsOutput('Pruned 1 missing and 0 duplicate photo row(s).');

    expect($orphan->fresh()?->trashed())->toBeTrue()
        ->and($keep->fresh()?->trashed())->toBeFalse();
});
