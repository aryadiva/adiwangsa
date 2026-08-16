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
