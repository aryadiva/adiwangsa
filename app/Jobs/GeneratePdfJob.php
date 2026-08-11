<?php

namespace App\Jobs;

use App\DTOs\ReportDataDTO;
use App\Enums\DocumentType;
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
 * configured S3-compatible `pdfs` disk, then notifies the requesting user.
 *
 * PDFs are ALWAYS generated here — never synchronously in an HTTP request.
 */
class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ReportDataDTO $dto,
        public ?string $onBehalfOfUserId = null,
    ) {}

    public function handle(PdfReportService $service): void
    {
        $bytes = $service->render($this->dto);

        $path = 'documents/'.$this->filename();
        Storage::disk('pdfs')->put($path, $bytes);

        $user = $this->onBehalfOfUserId ? User::find($this->onBehalfOfUserId) : null;
        $user?->notify(new PdfReadyNotification($this->dto->type, $path));
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
