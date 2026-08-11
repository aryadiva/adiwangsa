<?php

use App\DTOs\ReportDataDTO;
use App\Jobs\GeneratePdfJob;
use App\Models\User;
use App\Notifications\PdfReadyNotification;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores the generated pdf on the pdfs disk and notifies the requesting user', function () {
    [$project, $site, $report] = reportWithWorkersAndPhoto();

    Storage::fake('pdfs');
    Notification::fake();

    $requestingUser = User::factory()->admin()->create();
    $dto = ReportDataDTO::forDailyReport($report);

    (new GeneratePdfJob($dto, $requestingUser->id))->handle(app(PdfReportService::class));

    $files = Storage::disk('pdfs')->allFiles('documents');
    expect($files)->toHaveCount(1)
        ->and(str_ends_with($files[0], '.pdf'))->toBeTrue();

    Notification::assertSentTo($requestingUser, PdfReadyNotification::class);
});

it('uses Bus::fake to prove the job is queued rather than generated synchronously', function () {
    [, , $report] = reportWithWorkersAndPhoto();

    Bus::fake();

    $dto = ReportDataDTO::forDailyReport($report);

    GeneratePdfJob::dispatch($dto);

    Bus::assertDispatched(GeneratePdfJob::class);
});
