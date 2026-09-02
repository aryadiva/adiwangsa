<?php

use App\DTOs\ReportDataDTO;
use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Jobs\SendClientReportEmailJob;
use App\Mail\DailyReportPublished;
use App\Models\Client;
use App\Models\DailyReport;
use App\Models\DailyReportWorker;
use App\Models\GeneratedDocument;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use App\Services\PdfDocumentService;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function publishedReportFixture(array $clientMeta = []): array
{
    $engineer = User::factory()->siteEngineer()->create(['locale' => 'en']);
    $client = Client::factory()->create(['meta_data' => $clientMeta]);
    $project = Project::factory()->create(['client_id' => $client->id, 'code' => 'PRJ-2026-EMAIL']);
    $site = Site::factory()->create(['project_id' => $project->id, 'name' => 'Email Test Site']);

    $report = DailyReport::factory()->create([
        'site_id' => $site->id,
        'created_by_user_id' => $engineer->id,
        'status' => DailyReportStatus::Published,
        'work_summary' => 'Email delivery test summary.',
    ]);

    return [$report, $client, $project, $site];
}

it('emails the configured receivers with the PDF attached', function () {
    Mail::fake();
    Storage::fake('pdfs');

    [$report] = publishedReportFixture([
        'email_delivery' => [
            'sender_email' => 'reports@example.com',
            'sender_name' => 'Reports Bot',
            'receivers' => ['owner@client.test', 'pm@client.test'],
            'cc' => ['archive@client.test'],
        ],
    ]);

    (new SendClientReportEmailJob($report->id, locale: 'en'))->handle(app(PdfDocumentService::class));

    Mail::assertQueued(DailyReportPublished::class, function (DailyReportPublished $mail): bool {
        $envelope = $mail->envelope();

        $to = $envelope->to;
        $cc = $envelope->cc;

        $toAddresses = array_map(
            fn ($a) => is_object($a) ? $a->address : (string) $a,
            $to instanceof Collection ? $to->all() : (array) ($to ?? []),
        );
        $ccAddresses = array_map(
            fn ($a) => is_object($a) ? $a->address : (string) $a,
            $cc instanceof Collection ? $cc->all() : (array) ($cc ?? []),
        );

        return $toAddresses === ['owner@client.test', 'pm@client.test']
            && $ccAddresses === ['archive@client.test']
            && $mail->attachments()[0]->as !== null
            && str_ends_with($mail->attachments()[0]->as, '.pdf');
    });
});

it('falls back to the client email when no receivers are configured', function () {
    Mail::fake();
    Storage::fake('pdfs');

    [$report, $client] = publishedReportFixture();

    (new SendClientReportEmailJob($report->id))->handle(app(PdfDocumentService::class));

    Mail::assertQueued(DailyReportPublished::class, function (DailyReportPublished $mail) use ($client): bool {
        $to = $mail->envelope()->to;
        $to = $to instanceof Collection ? $to->all() : (array) $to;
        $toAddresses = array_map(fn ($a) => is_object($a) ? $a->address : (string) $a, $to);

        return count($toAddresses) === 1 && $toAddresses[0] === $client->email;
    });
});

it('skips sending when the report is not published', function () {
    Mail::fake();
    Storage::fake('pdfs');

    [$report] = publishedReportFixture();
    $report->forceFill(['status' => DailyReportStatus::Draft])->save();

    (new SendClientReportEmailJob($report->id))->handle(app(PdfDocumentService::class));

    Mail::assertNothingQueued();
});

it('stores a generated document when emailing a report that has none yet', function () {
    Mail::fake();
    Storage::fake('pdfs');

    [$report] = publishedReportFixture([
        'email_delivery' => ['receivers' => ['owner@client.test']],
    ]);

    (new SendClientReportEmailJob($report->id, locale: 'en'))->handle(app(PdfDocumentService::class));

    $document = GeneratedDocument::query()
        ->where('daily_report_id', $report->id)
        ->where('document_type', DocumentType::DailyProgress->value)
        ->first();

    expect($document)->not->toBeNull()
        ->and(Storage::disk('pdfs')->exists($document->file_path))->toBeTrue();
});

it('renders a valid daily progress PDF for the email DTO', function () {
    Storage::fake('pdfs');

    [$report, , $project, $site] = publishedReportFixture();

    $worker = Worker::factory()->create(['full_name' => 'Budi Santoso']);
    DailyReportWorker::create([
        'daily_report_id' => $report->id,
        'worker_id' => $worker->id,
        'hours_worked' => 8,
    ]);

    $dto = ReportDataDTO::forDailyReport($report, 'en');

    $bytes = app(PdfReportService::class)->render($dto);

    expect(str_starts_with($bytes, '%PDF'))->toBeTrue();
});
