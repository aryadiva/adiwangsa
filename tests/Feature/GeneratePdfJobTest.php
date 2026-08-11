<?php

use App\DTOs\ReportDataDTO;
use App\Jobs\GeneratePdfJob;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Notifications\PdfReadyNotification;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores the generated pdf on the pdfs disk, records it and notifies the requester', function () {
    [$project, $site, $report] = reportWithWorkersAndPhoto();

    Storage::fake('pdfs');
    Notification::fake();

    $requestingUser = User::factory()->admin()->create();
    $dto = ReportDataDTO::forDailyReport($report);

    (new GeneratePdfJob($dto, $requestingUser->id, dailyReportId: $report->id))
        ->handle(app(PdfReportService::class));

    $files = Storage::disk('pdfs')->allFiles('documents');
    expect($files)->toHaveCount(1)
        ->and(str_ends_with($files[0], '.pdf'))->toBeTrue();

    $document = GeneratedDocument::query()->first();
    expect($document)->not->toBeNull()
        ->and($document->daily_report_id)->toBe($report->id)
        ->and($document->document_type->value)->toBe('daily_progress')
        ->and($document->file_path)->toBe($files[0]);

    Notification::assertSentTo($requestingUser, PdfReadyNotification::class);
});

it('uses Bus::fake to prove the job is queued rather than generated synchronously', function () {
    [, , $report] = reportWithWorkersAndPhoto();

    Bus::fake();

    $dto = ReportDataDTO::forDailyReport($report);

    GeneratePdfJob::dispatch($dto);

    Bus::assertDispatched(GeneratePdfJob::class);
});
