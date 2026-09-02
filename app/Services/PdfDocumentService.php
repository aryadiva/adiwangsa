<?php

namespace App\Services;

use App\DTOs\ReportDataDTO;
use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Jobs\GeneratePdfJob;
use App\Models\DailyReport;
use App\Models\GeneratedDocument;
use App\Models\Project;
use App\Models\User;
use App\Notifications\PdfReadyNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Central dispatcher for PDF generation. Refuses to regenerate a document
 * that already exists for the same subject and period — it instead re-sends
 * the download link so PDFs are never rebuilt on every request.
 */
class PdfDocumentService
{
    /**
     * @return bool true when a new job was queued, false when an existing document was reused
     */
    public function queueDaily(DailyReport $report, ?string $userId): bool
    {
        $existing = GeneratedDocument::query()
            ->where('daily_report_id', $report->id)
            ->where('document_type', DocumentType::DailyProgress)
            ->latest()
            ->first();

        if ($existing !== null) {
            $this->notifyExisting($existing, $userId);

            return false;
        }

        GeneratePdfJob::dispatch(
            ReportDataDTO::forDailyReport($report, $this->userLocale($userId)),
            $userId,
            dailyReportId: $report->id,
        );

        return true;
    }

    /**
     * @return bool true when a new job was queued, false when an existing document was reused
     */
    public function queueWeekly(Project $project, Carbon $from, Carbon $to, ?string $userId): bool
    {
        $existing = $this->existingProjectDocument($project, DocumentType::WeeklyDigest, $from, $to);
        if ($existing !== null) {
            $this->notifyExisting($existing, $userId);

            return false;
        }

        GeneratePdfJob::dispatch(
            ReportDataDTO::forWeeklyDigest($project, $from, $to, $this->userLocale($userId)),
            $userId,
            projectId: $project->id,
        );

        return true;
    }

    /**
     * @return bool true when a new job was queued, false when an existing document was reused
     */
    public function queueAttendance(Project $project, Carbon $from, Carbon $to, ?string $userId): bool
    {
        $existing = $this->existingProjectDocument($project, DocumentType::AttendanceRoster, $from, $to);
        if ($existing !== null) {
            $this->notifyExisting($existing, $userId);

            return false;
        }

        $reports = DailyReport::query()
            ->where('status', DailyReportStatus::Published)
            ->whereHas('site', fn ($query) => $query->where('project_id', $project->id))
            ->whereBetween('report_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        GeneratePdfJob::dispatch(
            ReportDataDTO::forAttendanceRoster($reports, $from, $to, $this->userLocale($userId)),
            $userId,
            projectId: $project->id,
        );

        return true;
    }

    protected function existingProjectDocument(Project $project, DocumentType $type, Carbon $from, Carbon $to): ?GeneratedDocument
    {
        return GeneratedDocument::query()
            ->where('project_id', $project->id)
            ->where('document_type', $type)
            ->whereDate('period_from', '<=', $from->toDateString())
            ->whereDate('period_to', '>=', $to->toDateString())
            ->latest()
            ->first();
    }

    protected function notifyExisting(GeneratedDocument $document, ?string $userId): void
    {
        $user = $userId ? User::find($userId) : null;
        $user?->notify(new PdfReadyNotification(
            $document->document_type,
            route('generated-documents.download', $document),
        ));
    }

    protected function userLocale(?string $userId): ?string
    {
        return $userId ? User::find($userId)?->locale : null;
    }

    /**
     * Build the DailyProgress DTO for a report (locale from config override
     * or the requesting user's saved locale).
     */
    public function dailyReportDtoFor(DailyReport $report, ?string $locale = null): ReportDataDTO
    {
        return ReportDataDTO::forDailyReport($report, $locale ?? $this->userLocale(auth()->id()));
    }

    /**
     * Resolve the Sender/Receiver/CC configuration for a client email
     * (PRD §7.3). Per-send overrides win; otherwise the client's saved
     * defaults (clients.meta_data.email_delivery) apply.
     *
     * @param  list<string>|null  $receiversOverride
     * @param  list<string>|null  $ccOverride
     * @return array{0: string, 1: string, 2: list<string>, 3: list<string>}
     */
    public function clientEmailConfig(
        DailyReport $report,
        ?string $senderEmail = null,
        ?string $senderName = null,
        ?array $receiversOverride = null,
        ?array $ccOverride = null,
    ): array {
        $client = $report->site->project->client;
        $defaults = $client?->meta_data['email_delivery'] ?? [];

        $senderEmail ??= $defaults['sender_email'] ?? (string) config('mail.from.address');
        $senderName ??= $defaults['sender_name'] ?? (string) config('mail.from.name');
        $receivers = $receiversOverride ?? array_values(array_filter(
            (array) ($defaults['receivers'] ?? []),
            fn ($r) => filled($r),
        ));

        // Fallback: the client record's own email.
        if ($receivers === [] && $client?->email) {
            $receivers = [$client->email];
        }

        $cc = $ccOverride ?? array_values(array_filter((array) ($defaults['cc'] ?? []), fn ($c) => filled($c)));

        return [$senderEmail, $senderName, $receivers, $cc];
    }

    /**
     * Ensure a DailyProgress GeneratedDocument exists for the report and
     * return it. Reuses an existing document when its file is on the
     * `pdfs` disk; otherwise renders + stores one synchronously —
     * safe here because this only ever runs inside a queued job.
     */
    public function ensureDailyProgressPdf(DailyReport $report, ReportDataDTO $dto): GeneratedDocument
    {
        $existing = GeneratedDocument::query()
            ->where('daily_report_id', $report->id)
            ->where('document_type', DocumentType::DailyProgress->value)
            ->latest()
            ->first();

        if ($existing !== null && Storage::disk('pdfs')->exists($existing->file_path)) {
            return $existing;
        }

        $bytes = app(PdfReportService::class)->render($dto);

        $document = GeneratedDocument::create([
            'daily_report_id' => $report->id,
            'document_type' => DocumentType::DailyProgress->value,
            'file_path' => 'documents/'.Str::uuid().'.pdf',
            'generated_by_user_id' => null,
        ]);

        Storage::disk('pdfs')->put($document->file_path, $bytes);

        return $document;
    }

    /**
     * Queue the Worker Allocation & Payroll Summary document (PRD §7.4) —
     * the worker-allocation content split out of the Daily Progress PDF.
     * Serves payroll and HRD needs; payroll-specific columns arrive in 8.5.
     *
     * @return bool true when a new job was queued, false when an existing document was reused
     */
    public function queueWorkerAllocation(Project $project, Carbon $from, Carbon $to, ?string $userId): bool
    {
        $existing = $this->existingProjectDocument($project, DocumentType::WorkerAllocationPayroll, $from, $to);
        if ($existing !== null) {
            $this->notifyExisting($existing, $userId);

            return false;
        }

        $reports = DailyReport::query()
            ->where('status', DailyReportStatus::Published)
            ->whereHas('site', fn ($query) => $query->where('project_id', $project->id))
            ->whereBetween('report_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        GeneratePdfJob::dispatch(
            ReportDataDTO::forWorkerAllocation($reports, $from, $to, $this->userLocale($userId)),
            $userId,
            projectId: $project->id,
        );

        return true;
    }

    /**
     * Remove a generated document. If the object still exists on the `pdfs`
     * disk it is deleted first, then the record is soft-deleted. A record
     * whose file is already gone (e.g. pruned in MinIO) is still removed.
     */
    public function delete(GeneratedDocument $document): void
    {
        $disk = Storage::disk('pdfs');
        $path = $document->file_path;

        if ($path !== '' && $disk->exists($path)) {
            $disk->delete($path);
        }

        $document->delete();
    }
}
