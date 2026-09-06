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

function reportForPairForm(): array
{
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');

    return [$engineer, $report];
}

function pairFormData($report, array $overrides = []): array
{
    return array_merge([
        'before_photo' => 'daily-report-photos/before.jpg',
        'after_photo' => 'daily-report-photos/after.jpg',
        'photo_description' => 'Excavation pair',
        'site_id' => $report->site_id,
        'milestone_sub_job_id' => $report->milestone_sub_job_id,
        'report_date' => '2026-08-12',
        'weather_condition' => 'sunny',
        'work_summary' => 'Pair reconciliation',
    ], $overrides);
}

it('creates exactly one before/after pair row per report and does not duplicate it on repeated saves', function () {
    [$engineer, $report] = reportForPairForm();

    Storage::disk('photos')->put('daily-report-photos/before.jpg', 'image-data');
    Storage::disk('photos')->put('daily-report-photos/after.jpg', 'image-data');

    $formData = pairFormData($report);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm($formData)
        ->call('save');

    expect($report->fresh()->photos()->count())->toBe(1)
        ->and($report->fresh()->photos()->first()->before_file_path)->toBe('daily-report-photos/before.jpg')
        ->and($report->fresh()->photos()->first()->after_file_path)->toBe('daily-report-photos/after.jpg')
        ->and($report->fresh()->photos()->first()->description)->toBe('Excavation pair');

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm($formData)
        ->call('save');

    expect($report->fresh()->photos()->count())->toBe(1);
});

it('replaces the pair paths on the same row when a side is recaptured', function () {
    [$engineer, $report] = reportForPairForm();

    foreach (['before.jpg', 'after.jpg', 'before-2.jpg', 'after-2.jpg'] as $file) {
        Storage::disk('photos')->put("daily-report-photos/{$file}", 'image-data');
    }

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(pairFormData($report))
        ->call('save');

    $photoId = $report->fresh()->photos()->first()->id;

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(pairFormData($report, [
            'before_photo' => 'daily-report-photos/before-2.jpg',
            'after_photo' => 'daily-report-photos/after-2.jpg',
        ]))
        ->call('save');

    $photo = $report->fresh()->photos()->first();

    expect($report->fresh()->photos()->count())->toBe(1)
        ->and($photo->id)->toBe($photoId)
        ->and($photo->before_file_path)->toBe('daily-report-photos/before-2.jpg')
        ->and($photo->after_file_path)->toBe('daily-report-photos/after-2.jpg');
});

it('updates the description without touching the pair paths', function () {
    [$engineer, $report] = reportForPairForm();

    Storage::disk('photos')->put('daily-report-photos/before.jpg', 'image-data');
    Storage::disk('photos')->put('daily-report-photos/after.jpg', 'image-data');

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(pairFormData($report))
        ->call('save');

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->fillForm(pairFormData($report, ['photo_description' => 'Updated description']))
        ->call('save');

    $photo = $report->fresh()->photos()->first();

    expect($report->fresh()->photos()->count())->toBe(1)
        ->and($photo->description)->toBe('Updated description')
        ->and($photo->before_file_path)->toBe('daily-report-photos/before.jpg');
});

it('exposes missing pair paths so the UI can warn the user', function () {
    [$engineer, $report] = reportForPairForm();

    $report->photos()->create([
        'before_file_path' => 'daily-report-photos/missing-before.jpg',
        'before_thumbnail_path' => 'daily-report-photos/thumbs/missing-before.jpg',
        'after_file_path' => 'daily-report-photos/missing-after.jpg',
        'after_thumbnail_path' => 'daily-report-photos/thumbs/missing-after.jpg',
        'captured_at' => now(),
        'file_size_bytes' => 10,
    ]);

    Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()])
        ->assertSet('missingPhotoPaths', [
            'daily-report-photos/missing-before.jpg',
            'daily-report-photos/missing-after.jpg',
        ]);
});

it('prunes daily_report_photos rows whose before file is missing from storage', function () {
    $project = Project::factory()->create();
    [, , $reportWithOrphan] = draftFor($project, '2026-08-12');
    [, , $reportWithKeep] = draftFor($project, '2026-08-13');

    // Not placed on the (faked) disk → orphaned.
    $orphan = $reportWithOrphan->photos()->create([
        'before_file_path' => 'daily-report-photos/orphan.jpg',
        'before_thumbnail_path' => 'daily-report-photos/thumbs/orphan.jpg',
        'after_file_path' => 'daily-report-photos/after.jpg',
        'captured_at' => now(),
        'file_size_bytes' => 10,
    ]);

    // Present on the disk → must be retained.
    Storage::disk('photos')->put('daily-report-photos/ok.jpg', 'image-data');
    $keep = $reportWithKeep->photos()->create([
        'before_file_path' => 'daily-report-photos/ok.jpg',
        'before_thumbnail_path' => 'daily-report-photos/thumbs/ok.jpg',
        'after_file_path' => 'daily-report-photos/after.jpg',
        'captured_at' => now(),
        'file_size_bytes' => 10,
    ]);

    $this->artisan('photos:prune')
        ->expectsOutput('Pruned 1 missing and 0 duplicate photo row(s).');

    expect($orphan->fresh()?->trashed())->toBeTrue()
        ->and($keep->fresh()?->trashed())->toBeFalse();
});
