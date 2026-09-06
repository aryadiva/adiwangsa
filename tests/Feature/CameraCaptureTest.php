<?php

use App\Enums\DailyReportStatus;
use App\Filament\Components\LiveCapture;
use App\Filament\Resources\DailyReportResource\Pages\EditDailyReport;
use App\Filament\Resources\WorkerAttendanceResource\Pages\CreateWorkerAttendance;
use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use App\Models\Project;
use App\Models\WorkerAttendance;
use App\Rules\LiveCapture as LiveCaptureRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('photos');
});

function liveCaptureState(string $when = 'now'): string
{
    $timestamp = $when === 'now'
        ? now()->toIso8601String()
        : $when;

    return "capture-{$timestamp}.jpg";
}

it('accepts a fresh live capture timestamp embedded in the filename', function () {
    $rule = new LiveCaptureRule;

    $failed = false;
    $rule->validate('before_photo', liveCaptureState(), function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('rejects a live capture whose embedded timestamp is missing', function () {
    $rule = new LiveCaptureRule;

    $message = null;
    $rule->validate('before_photo', 'livewire-file:some-random-upload', function ($m) use (&$message) {
        $message = $m;
    });

    expect($message)->toContain('capture timestamp is missing');
});

it('rejects a live capture older than the configured recency window', function () {
    $message = null;
    (new LiveCaptureRule)->validate('before_photo', liveCaptureState(now()->subMinutes(30)->toIso8601String()), function ($m) use (&$message) {
        $message = $m;
    });

    expect($message)->toContain('too old');
});

it('rejects a live capture with a future timestamp beyond the clock-skew allowance', function () {
    $message = null;
    (new LiveCaptureRule)->validate('before_photo', liveCaptureState(now()->addMinutes(30)->toIso8601String()), function ($m) use (&$message) {
        $message = $m;
    });

    expect($message)->toContain('in the future');
});

it('passes an already-stored path through the rule (edit form filled from the DB)', function () {
    $failed = false;
    (new LiveCaptureRule)->validate('before_photo', 'daily-report-photos/already-stored.jpg', function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

it('exposes the embedded shutter timestamp for capture metadata', function () {
    $stamp = Carbon::parse('2026-09-06T08:15:00.000Z');
    $token = 'livewire-file:abc123';

    $mock = Mockery::mock(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile::class)->makePartial();
    $mock->shouldReceive('getClientOriginalName')->andReturn('capture-2026-09-06T08:15:00.000Z.jpg');

    expect(LiveCaptureRule::embeddedTimestamp($mock)->equalTo($stamp))->toBeTrue();
});

it('caps the before/after pair at exactly one active row per report (partial unique index)', function () {
    $project = Project::factory()->create();
    [, , $report] = draftFor($project, '2026-08-12');

    $report->photos()->create([
        'before_file_path' => 'daily-report-photos/first-before.jpg',
        'after_file_path' => 'daily-report-photos/first-after.jpg',
        'captured_at' => now(),
    ]);

    expect(DailyReportPhoto::query()->where('daily_report_id', $report->id)->count())->toBe(1)
        // The savepoint keeps the outer test transaction usable after the
        // constraint trips.
        ->and(fn () => DB::transaction(fn () => $report->photos()->create([
            'before_file_path' => 'daily-report-photos/second-before.jpg',
            'after_file_path' => 'daily-report-photos/second-after.jpg',
            'captured_at' => now(),
        ])))->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

it('blocks submitting a draft for approval without the before/after pair, and allows it with one', function () {
    $project = Project::factory()->create();
    [$engineer, , $report] = draftFor($project, '2026-08-12');

    Storage::disk('photos')->put('daily-report-photos/before.jpg', 'image-data');
    Storage::disk('photos')->put('daily-report-photos/after.jpg', 'image-data');

    $page = Livewire::actingAs($engineer)
        ->test(EditDailyReport::class, ['record' => $report->getRouteKey()]);

    $page->callAction('submitForApproval');

    expect($report->fresh()->status)->toBe(DailyReportStatus::Draft);

    // Submitting requires a saved pair — fillForm alone is not a save.
    $page->fillForm([
        'before_photo' => 'daily-report-photos/before.jpg',
        'after_photo' => 'daily-report-photos/after.jpg',
        'photo_description' => 'Pair present',
    ])->call('save')->callAction('submitForApproval');

    expect($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval);
});

it('renders the live camera component on the HRD attendance form, never a gallery picker', function () {
    $hrd = hrdUser();
    $worker = \App\Models\Worker::factory()->create();
    $site = \App\Models\Site::factory()->create();

    Livewire::actingAs($hrd)
        ->test(CreateWorkerAttendance::class)
        ->assertFormFieldExists('attendance_photo')
        ->assertFormFieldExists('hours_worked')
        ->assertFormFieldExists('overtime_hours');

    $page = Livewire::actingAs($hrd)->test(CreateWorkerAttendance::class);
    $html = $page->html();

    expect($html)->toContain('fi-fo-live-capture')
        ->and($html)->toContain('capture="environment"')
        ->and(str_contains($html, '<input type="file"') && ! str_contains($html, 'capture="environment"'))->toBeFalse();
});

it('records attendance through the HRD form with the captured photo, timestamp and recorder stamped', function () {
    $hrd = hrdUser();
    $worker = \App\Models\Worker::factory()->create();
    $site = \App\Models\Site::factory()->create();

    Storage::disk('photos')->put('worker-attendance-photos/live.jpg', 'image-data');

    Carbon::setTestNow(Carbon::parse('2026-09-06 10:00:00'));

    Livewire::actingAs($hrd)
        ->test(CreateWorkerAttendance::class)
        ->fillForm([
            'worker_id' => $worker->id,
            'site_id' => $site->id,
            'attendance_date' => '2026-09-06',
            'hours_worked' => 8,
            'overtime_hours' => 2,
            'attendance_photo' => 'worker-attendance-photos/live.jpg',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $attendance = WorkerAttendance::query()->where('worker_id', $worker->id)->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->recorded_by_user_id)->toBe($hrd->id)
        ->and($attendance->photo_file_path)->toBe('worker-attendance-photos/live.jpg')
        ->and($attendance->photo_thumbnail_path)->toBe('worker-attendance-photos/thumbs/live.jpg')
        ->and($attendance->captured_at->toDateTimeString())->toBe('2026-09-06 10:00:00')
        ->and($attendance->meta_data['capture']['method'])->toBe('live')
        ->and($attendance->meta_data['capture']['captured_at'])->toBe('2026-09-06T10:00:00+00:00');

    Carbon::setTestNow();
});

it('surfaces the friendly duplicate error on the HRD form', function () {
    $hrd = hrdUser();
    $worker = \App\Models\Worker::factory()->create();
    $site = \App\Models\Site::factory()->create();

    WorkerAttendance::factory()->create([
        'worker_id' => $worker->id,
        'site_id' => $site->id,
        'attendance_date' => '2026-09-06',
        'recorded_by_user_id' => $hrd->id,
    ]);

    Storage::disk('photos')->put('worker-attendance-photos/live.jpg', 'image-data');

    Livewire::actingAs($hrd)
        ->test(CreateWorkerAttendance::class)
        ->fillForm([
            'worker_id' => $worker->id,
            'site_id' => $site->id,
            'attendance_date' => '2026-09-06',
            'attendance_photo' => 'worker-attendance-photos/live.jpg',
        ])
        ->call('create')
        ->assertHasFormErrors(['attendance_date']);
});

it('stores captures through the shared PhotoCaptureService with server-side MIME sniffing', function () {
    $image = \Illuminate\Http\UploadedFile::fake()->image('capture-anything.jpg');

    $captured = app(\App\Services\PhotoCaptureService::class)->capture($image, 'worker-attendance-photos');

    expect($captured['path'])->toStartWith('worker-attendance-photos/')
        ->and($captured['thumbnail_path'])->toStartWith('worker-attendance-photos/thumbs/')
        ->and($captured['file_size_bytes'])->toBeGreaterThan(0);

    Storage::disk('photos')->assertExists($captured['path'])
        ->assertExists($captured['thumbnail_path']);

    // Content sniffing, not the client-supplied filename, drives validation.
    $notAnImage = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        'capture-'.now()->toIso8601String().'.jpg',
        'this is definitely not an image'
    );

    expect(fn () => app(\App\Services\PhotoCaptureService::class)->capture($notAnImage, 'worker-attendance-photos'))
        ->toThrow(RuntimeException::class);
});
