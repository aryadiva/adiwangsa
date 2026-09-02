<?php

namespace App\Jobs;

use App\Mail\DailyReportPublished;
use App\Models\DailyReport;
use App\Services\PdfDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Emails the client a published Daily Site Progress Summary PDF
 * (PRD §7.3). Dispatched ONLY from DailyReport::approveAndPublish() —
 * never on draft/need_approval/revision_requested.
 *
 * The attached PDF reuses (or first creates) the DailyProgress
 * GeneratedDocument; attachment bytes come from the `pdfs` disk.
 */
class SendClientReportEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<string>|null  $receivers  overrides client defaults
     * @param  list<string>|null  $cc  overrides client defaults
     */
    public function __construct(
        public string $dailyReportId,
        public ?string $senderEmail = null,
        public ?string $senderName = null,
        public ?array $receivers = null,
        public ?array $cc = null,
        public ?string $locale = null,
    ) {}

    public function handle(PdfDocumentService $documents): void
    {
        $report = DailyReport::query()->find($this->dailyReportId);

        if ($report === null || ! $report->status->isPublished()) {
            return;
        }

        [$senderEmail, $senderName, $receivers, $cc] = $documents->clientEmailConfig(
            $report,
            $this->senderEmail,
            $this->senderName,
            $this->receivers,
            $this->cc,
        );

        if ($receivers === []) {
            return;
        }

        $dto = $documents->dailyReportDtoFor($report, $this->locale);
        $document = $documents->ensureDailyProgressPdf($report, $dto);

        Mail::send(
            (new DailyReportPublished($dto, $senderEmail, $senderName, $receivers, $cc))
                ->withPdfPath($document->file_path)
        );
    }
}
