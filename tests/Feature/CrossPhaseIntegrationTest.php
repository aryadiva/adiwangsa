<?php

use App\DTOs\ReportDataDTO;
use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Enums\ProjectMilestoneStatus;
use App\Jobs\GeneratePdfJob;
use App\Jobs\SendClientReportEmailJob;
use App\Models\Client;
use App\Models\DailyReport;
use App\Models\DailyReportWorker;
use App\Models\GeneratedDocument;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use App\Notifications\ReportApprovedNotification;
use App\Notifications\ReportSubmittedNotification;
use App\Services\PdfDocumentService;
use App\Services\PdfReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('runs the full smoke flow across phases: setup -> submit -> approve -> publish -> pdf -> client email', function () {
    Storage::fake('pdfs');
    Notification::fake();
    Bus::fake();

    // --- Phase 1/2: entities + RBAC ---
    $admin = User::factory()->admin()->create();
    $engineer = User::factory()->siteEngineer()->create();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->create(['user_id' => $clientUser->id]);

    $project = Project::factory()->create(['client_id' => $client->id]);
    $engineer->projects()->attach($project);
    $site = Site::factory()->create(['project_id' => $project->id]);
    $milestone = ProjectMilestone::create([
        'project_id' => $project->id,
        'title' => 'Foundation Slab',
        'target_date' => now()->addDays(10)->toDateString(),
        'status' => ProjectMilestoneStatus::InProgress,
    ]);
    $worker = Worker::factory()->create();

    // Engineer only sees their project scope
    expect($engineer->can('view', $project))->toBeTrue();
    $other = Project::factory()->create();
    expect($engineer->can('view', $other))->toBeFalse();

    // --- Phase 3: draft -> submit -> need_approval + admin notification ---
    $report = DailyReport::create([
        'site_id' => $site->id,
        'created_by_user_id' => $engineer->id,
        'report_date' => now()->toDateString(),
        'weather_condition' => 'sunny',
        'work_summary' => 'Excavation progressing well.',
        'status' => DailyReportStatus::Draft,
        'meta_data' => ['moisture' => 11],
    ]);
    DailyReportWorker::create([
        'daily_report_id' => $report->id,
        'worker_id' => $worker->id,
        'hours_worked' => 8,
    ]);

    $report->submitForApproval();
    expect($report->fresh()->status)->toBe(DailyReportStatus::NeedApproval);
    Notification::assertSentTo($admin, ReportSubmittedNotification::class);

    // --- Phase 3/4: admin approves -> published + engineer/approved notification ---
    $report->approveAndPublish($admin->id);
    expect($report->fresh()->status)->toBe(DailyReportStatus::Published);
    Notification::assertSentTo($engineer, ReportApprovedNotification::class);

    // Published is terminal — no edits
    expect(fn () => $report->submitForApproval())->toThrow(DomainException::class);

    // Client has no panel access (v3 §3.1) — but the policy still denies edits
    expect($clientUser->can('view', $report))->toBeTrue();
    expect($clientUser->can('update', $report))->toBeFalse();

    // --- Phase 5.1/5.2/8.4: generate PDF, store, notify; client emailed instead of portal ---
    $queued = app(PdfDocumentService::class)->queueDaily($report, $admin->id);
    expect($queued)->toBeTrue();

    Bus::assertDispatched(GeneratePdfJob::class);

    // Run the generation job inline (queue is faked) to exercise storage.
    (new GeneratePdfJob(
        ReportDataDTO::forDailyReport($report),
        $admin->id,
        dailyReportId: $report->id,
    ))->handle(app(PdfReportService::class));

    $document = GeneratedDocument::query()->first();
    expect($document)->not->toBeNull()
        ->and($document->document_type)->toBe(DocumentType::DailyProgress)
        ->and(Storage::disk('pdfs')->exists($document->file_path))->toBeTrue();

    // Admin may download through the throttled route
    $this->actingAs($admin)
        ->get(route('generated-documents.download', $document))
        ->assertOk();

    // Client panel routes are gone — a client request 404s
    $this->actingAs($clientUser)
        ->get('/client/dashboard')
        ->assertNotFound();

    // Client email job was queued on publish (handled in depth by ClientReportEmailTest)
    Bus::assertDispatched(
        SendClientReportEmailJob::class,
        fn (SendClientReportEmailJob $job): bool => $job->dailyReportId === $report->id,
    );
});
