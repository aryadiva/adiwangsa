<?php

namespace App\DTOs;

use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Enums\ProjectMilestoneStatus;
use App\Models\DailyReport;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Immutable, queue-safe data container mapping DB + JSONB records into
 * display-ready values consumed by the Blade templates in resources/views/pdf/.
 *
 * Only primitives and arrays are stored so the DTO can be serialized onto the
 * queue with the GeneratePdfJob without carrying live Eloquent models.
 */
final class ReportDataDTO
{
    public function __construct(
        public readonly DocumentType $type,
        public readonly string $title,
        public readonly string $projectName = '',
        public readonly string $projectCode = '',
        public readonly string $clientCompany = '',
        public readonly ?string $siteName = null,
        public readonly ?string $reportDate = null,
        public readonly ?string $dateRange = null,
        public readonly ?string $weather = null,
        public readonly ?string $workSummary = null,
        public readonly ?string $delaysOrIssues = null,
        public readonly int $workerCount = 0,
        public readonly string $totalHours = '0.00',
        public readonly array $photos = [],
        public readonly array $workerRows = [],
        public readonly array $reportSummaries = [],
        public readonly array $milestones = [],
        public readonly array $metaData = [],
        public readonly string $generatedAt = '',
        public readonly ?string $periodFrom = null,
        public readonly ?string $periodTo = null,
    ) {}

    public static function forDailyReport(DailyReport $report): self
    {
        $site = $report->site;
        $project = $site?->project;
        $workers = $report->workerAllocations;

        return new self(
            type: DocumentType::DailyProgress,
            title: DocumentType::DailyProgress->label(),
            projectName: $project?->name ?? '',
            projectCode: $project?->code ?? '',
            clientCompany: $project?->client?->company_name ?? '',
            siteName: $site?->name,
            reportDate: $report->report_date?->toDateString(),
            weather: $report->weather_condition?->value,
            workSummary: $report->work_summary,
            delaysOrIssues: $report->delays_or_issues,
            workerCount: $workers->count(),
            totalHours: number_format((float) $workers->sum('hours_worked'), 2),
            photos: $report->photos
                ->map(fn ($photo): array => [
                    'path' => $photo->file_path ?: '',
                    'thumbnail' => $photo->thumbnail_path ?: '',
                    'caption' => $photo->caption,
                ])
                ->values()
                ->all(),
            workerRows: $workers
                ->map(fn ($allocation): array => [
                    'name' => $allocation->worker?->full_name,
                    'trade' => $allocation->worker?->trade_skill,
                    'hours' => (string) $allocation->hours_worked,
                    'remarks' => $allocation->remarks,
                ])
                ->values()
                ->all(),
            metaData: $report->meta_data ?? [],
            generatedAt: now()->toDateTimeString(),
        );
    }

    /**
     * Aggregates 7 days of PUBLISHED daily reports for a project, plus its
     * completed milestones. Non-published states are never included.
     */
    public static function forWeeklyDigest(Project $project, Carbon $start, Carbon $end): self
    {
        $startDay = $start->copy()->startOfDay();
        $endDay = $end->copy()->endOfDay();

        $sites = $project->sites()
            ->with(['dailyReports' => fn ($query) => $query
                ->where('status', DailyReportStatus::Published)
                ->whereBetween('report_date', [$startDay->toDateString(), $endDay->toDateString()])
                ->with('workerAllocations.worker'),
            ])
            ->get();

        $reports = $sites->flatMap(fn ($site) => $site->dailyReports
            ->map(fn ($report): array => ['site' => $site->name, 'report' => $report]))
            ->values();

        $reportSummaries = $reports
            ->map(fn (array $item): array => [
                'date' => $item['report']->report_date?->toDateString(),
                'site' => $item['site'],
                'weather' => $item['report']->weather_condition?->value,
                'summary' => $item['report']->work_summary,
                'hours' => number_format((float) $item['report']->workerAllocations->sum('hours_worked'), 2),
                'delay' => $item['report']->delays_or_issues,
            ])
            ->values()
            ->all();

        $milestones = $project->milestones
            ->filter(fn ($milestone): bool => $milestone->status === ProjectMilestoneStatus::Completed)
            ->map(fn ($milestone): array => [
                'title' => $milestone->title,
                'completed_at' => $milestone->completed_at?->toDateString(),
            ])
            ->values()
            ->all();

        return new self(
            type: DocumentType::WeeklyDigest,
            title: DocumentType::WeeklyDigest->label(),
            projectName: $project->name,
            projectCode: $project->code,
            clientCompany: $project->client?->company_name ?? '',
            dateRange: $startDay->toDateString().' — '.$endDay->toDateString(),
            workerCount: $reports->sum(fn (array $item): int => $item['report']->workerAllocations->count()),
            totalHours: number_format((float) $reports->sum(fn (array $item): float => $item['report']->workerAllocations->sum('hours_worked')), 2),
            reportSummaries: $reportSummaries,
            milestones: $milestones,
            generatedAt: now()->toDateTimeString(),
            periodFrom: $startDay->toDateString(),
            periodTo: $endDay->toDateString(),
        );
    }

    /**
     * Builds a labor roster grouped by worker across the given (published)
     * reports within a date range.
     *
     * @param  Collection<int, DailyReport>  $reports
     */
    public static function forAttendanceRoster(Collection $reports, Carbon $start, Carbon $end): self
    {
        $reports = (new \Illuminate\Database\Eloquent\Collection($reports->all()))
            ->load('site.project', 'workerAllocations.worker');
        $allocations = $reports->flatMap(fn ($report) => $report->workerAllocations);

        $workerRows = $allocations
            ->groupBy('worker_id')
            ->map(fn (Collection $group): array => [
                'name' => $group->first()?->worker?->full_name,
                'trade' => $group->first()?->worker?->trade_skill,
                'hours' => number_format((float) $group->sum('hours_worked'), 2),
                'days' => $group->count(),
                'site' => $group->first()?->dailyReport?->site?->name,
            ])
            ->values()
            ->all();

        return new self(
            type: DocumentType::AttendanceRoster,
            title: DocumentType::AttendanceRoster->label(),
            projectName: $reports->first()?->site?->project?->name ?? '',
            projectCode: $reports->first()?->site?->project?->code ?? '',
            clientCompany: $reports->first()?->site?->project?->client?->company_name ?? '',
            dateRange: $start->toDateString().' — '.$end->toDateString(),
            workerCount: count($workerRows),
            totalHours: number_format((float) $allocations->sum('hours_worked'), 2),
            workerRows: $workerRows,
            generatedAt: now()->toDateTimeString(),
            periodFrom: $start->toDateString(),
            periodTo: $end->toDateString(),
        );
    }
}
