<?php

use App\Enums\DailyReportStatus;
use App\Jobs\GeneratePdfJob;
use App\Models\DailyReport;
use App\Models\GeneratedDocument;
use App\Models\Project;
use App\Models\User;
use App\Notifications\PdfReadyNotification;
use App\Services\PdfDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('queues a daily progress job when none exists', function () {
    Bus::fake();
    [, , $report] = reportWithWorkersAndPhoto();
    $admin = User::factory()->admin()->create();

    $queued = app(PdfDocumentService::class)->queueDaily($report, $admin->id);

    expect($queued)->toBeTrue();
    Bus::assertDispatched(GeneratePdfJob::class);
});

it('reuses an existing daily document instead of regenerating', function () {
    Bus::fake();
    Notification::fake();
    [, , $report] = reportWithWorkersAndPhoto();
    $admin = User::factory()->admin()->create();

    GeneratedDocument::create([
        'daily_report_id' => $report->id,
        'document_type' => 'daily_progress',
        'file_path' => 'documents/existing.pdf',
        'generated_by_user_id' => $admin->id,
    ]);

    $queued = app(PdfDocumentService::class)->queueDaily($report, $admin->id);

    expect($queued)->toBeFalse();
    Bus::assertNotDispatched(GeneratePdfJob::class);
    Notification::assertSentTo($admin, PdfReadyNotification::class);
});

it('queues a weekly digest for a project', function () {
    Bus::fake();
    [$project] = reportWithWorkersAndPhoto();
    $admin = User::factory()->admin()->create();

    $queued = app(PdfDocumentService::class)->queueWeekly(
        $project,
        Carbon::now()->subDays(7),
        Carbon::now(),
        $admin->id,
    );

    expect($queued)->toBeTrue();
    Bus::assertDispatched(GeneratePdfJob::class);
});

it('reuses a weekly digest covering the same period', function () {
    Bus::fake();
    Notification::fake();
    [$project] = reportWithWorkersAndPhoto();
    $admin = User::factory()->admin()->create();
    $from = Carbon::now()->subDays(7);
    $to = Carbon::now();

    GeneratedDocument::create([
        'project_id' => $project->id,
        'document_type' => 'weekly_digest',
        'file_path' => 'documents/weekly.pdf',
        'period_from' => $from->toDateString(),
        'period_to' => $to->toDateString(),
        'generated_by_user_id' => $admin->id,
    ]);

    $queued = app(PdfDocumentService::class)->queueWeekly($project, $from, $to, $admin->id);

    expect($queued)->toBeFalse();
    Bus::assertNotDispatched(GeneratePdfJob::class);
});

it('queues an attendance roster job using only published reports', function () {
    Bus::fake();
    [$project, $site] = reportWithWorkersAndPhoto();
    $admin = User::factory()->admin()->create();

    DailyReport::factory()->create([
        'site_id' => $site->id,
        'status' => DailyReportStatus::Draft,
        'report_date' => Carbon::now()->subDays(2)->toDateString(),
    ]);

    $queued = app(PdfDocumentService::class)->queueAttendance(
        $project,
        Carbon::now()->subDays(7),
        Carbon::now(),
        $admin->id,
    );

    expect($queued)->toBeTrue();
    Bus::assertDispatched(GeneratePdfJob::class);
});

it('deletes the object and the record when the file exists on the pdf disk', function () {
    Storage::fake('pdfs');
    $admin = User::factory()->admin()->create();
    Storage::disk('pdfs')->put('documents/delete-me.pdf', 'pdf bytes');

    $document = GeneratedDocument::create([
        'project_id' => Project::factory()->create()->id,
        'document_type' => 'weekly_digest',
        'file_path' => 'documents/delete-me.pdf',
        'generated_by_user_id' => $admin->id,
    ]);

    app(PdfDocumentService::class)->delete($document);

    Storage::disk('pdfs')->assertMissing('documents/delete-me.pdf');
    expect($document->fresh()->trashed())->toBeTrue();
});

it('deletes only the record when the file is already gone from the pdf disk', function () {
    Storage::fake('pdfs');
    $admin = User::factory()->admin()->create();

    $document = GeneratedDocument::create([
        'project_id' => Project::factory()->create()->id,
        'document_type' => 'weekly_digest',
        'file_path' => 'documents/orphan.pdf',
        'generated_by_user_id' => $admin->id,
    ]);

    Storage::disk('pdfs')->assertMissing('documents/orphan.pdf');
    app(PdfDocumentService::class)->delete($document);

    Storage::disk('pdfs')->assertMissing('documents/orphan.pdf');
    expect($document->fresh()->trashed())->toBeTrue();
});
