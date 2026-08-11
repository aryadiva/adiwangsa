<?php

namespace App\Jobs;

use App\DTOs\ReportDataDTO;
use App\Enums\DocumentType;
use App\Models\GeneratedDocument;
use App\Models\User;
use App\Notifications\PdfReadyNotification;
use App\Services\PdfReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Renders a queued PDF from a fully-built DTO, stores the bytes on the
 * configured S3-compatible `pdfs` disk, records it in `generated_documents`,
 * then notifies the requesting user with a signed download link.
 *
 * PDFs are ALWAYS generated here — never synchronously in an HTTP request.
 */
class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ReportDataDTO $dto,
        public ?string $onBehalfOfUserId = null,
        public ?string $dailyReportId = null,
        public ?string $projectId = null,
    ) {}

    public function handle(PdfReportService $service): void
    {
        $bytes = $service->render($this->dto);

        $document = GeneratedDocument::create([
            'daily_report_id' => $this->dailyReportId,
            'project_id' => $this->projectId,
            'document_type' => $this->dto->type->value,
            'file_path' => 'documents/'.$this->filename(),
            'period_from' => $this->dto->periodFrom,
            'period_to' => $this->dto->periodTo,
            'generated_by_user_id' => $this->onBehalfOfUserId,
        ]);

        Storage::disk('pdfs')->put($document->file_path, $bytes);

        $user = $this->onBehalfOfUserId ? User::find($this->onBehalfOfUserId) : null;
        $user?->notify(new PdfReadyNotification(
            $this->dto->type,
            route('generated-documents.download', $document),
        ));
    }

    protected function filename(): string
    {
        $slug = match ($this->dto->type) {
            DocumentType::DailyProgress => 'daily-progress',
            DocumentType::WeeklyDigest => 'weekly-digest',
            DocumentType::AttendanceRoster => 'attendance-roster',
        };

        return Str::slug($slug.'-'.$this->dto->projectCode).'-'.now()->format('YmdHis').'.pdf';
    }
}
