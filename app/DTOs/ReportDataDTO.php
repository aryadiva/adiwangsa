<?php

namespace App\DTOs;

use App\Enums\DailyReportStatus;
use App\Enums\DocumentType;
use App\Enums\ProjectMilestoneStatus;
use App\Models\DailyReport;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Project;
use App\Models\Site;
use App\Models\Worker;
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
        public readonly array $sections = [],
        public readonly array $metaData = [],
        public readonly array $payrollItems = [],
        public readonly ?string $payrollTotal = null,
        public readonly string $generatedAt = '',
        public readonly ?string $periodFrom = null,
        public readonly ?string $periodTo = null,
        public readonly string $locale = 'en',
    ) {}

    public static function forDailyReport(DailyReport $report, ?string $locale = null): self
    {
        $site = $report->site;
        $project = $site->project;
        $workers = $report->workerAllocations;

        return new self(
            type: DocumentType::DailyProgress,
            title: DocumentType::DailyProgress->label(),
            projectName: $project->name,
            projectCode: $project->code,
            clientCompany: $project->client->company_name,
            siteName: $site->name,
            reportDate: $report->report_date->toDateString(),
            weather: $report->weather_condition->value,
            workSummary: $report->work_summary,
            delaysOrIssues: $report->delays_or_issues,
            workerCount: $workers->count(),
            totalHours: number_format((float) $workers->sum('hours_worked'), 2),
            photos: $report->photos
                ->map(fn ($photo): array => [
                    'before_path' => $photo->before_file_path ?: '',
                    'before_thumbnail' => $photo->before_thumbnail_path ?: '',
                    'after_path' => $photo->after_file_path ?: '',
                    'after_thumbnail' => $photo->after_thumbnail_path ?: '',
                    'description' => $photo->description,
                ])
                ->values()
                ->all(),
            workerRows: $workers
                ->map(fn ($allocation): array => [
                    'name' => $allocation->worker->full_name,
                    'trade' => $allocation->worker->trade_skill,
                    'hours' => (string) $allocation->hours_worked,
                    'remarks' => $allocation->remarks,
                ])
                ->values()
                ->all(),
            metaData: $report->meta_data ?? [],
            sections: self::extractSections($report),
            generatedAt: now()->toDateTimeString(),
            locale: $locale ?? app()->getLocale(),
        );
    }

    /**
     * Extensible typed content blocks (PRD §7.4) — read from the report's
     * meta_data['sections'] when present. Each entry is ['type' => ..., 'payload' => [...]];
     * the Blade template skips unrecognized types gracefully.
     *
     * @return list<array{type: string, payload: array}>
     */
    protected static function extractSections(DailyReport $report): array
    {
        $sections = $report->meta_data['sections'] ?? [];

        return collect(is_array($sections) ? $sections : [])
            ->filter(fn ($section): bool => is_array($section) && isset($section['type']))
            ->map(fn ($section): array => [
                'type' => (string) $section['type'],
                'payload' => (array) ($section['payload'] ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * Aggregates 7 days of PUBLISHED daily reports for a project, plus its
     * completed milestones. Non-published states are never included.
     */
    public static function forWeeklyDigest(Project $project, Carbon $start, Carbon $end, ?string $locale = null): self
    {
        $startDay = $start->copy()->startOfDay();
        $endDay = $end->copy()->endOfDay();

        /** @var Collection<int, Site> $sites */
        $sites = $project->sites()
            ->with(['dailyReports' => fn ($query) => $query
                ->where('status', DailyReportStatus::Published)
                ->whereBetween('report_date', [$startDay->toDateString(), $endDay->toDateString()])
                ->with('workerAllocations.worker'),
            ])
            ->get();

        $reports = $sites->flatMap(function (Site $site) {
            return $site->dailyReports->map(function (DailyReport $report) use ($site): array {
                return ['site' => $site->name, 'report' => $report];
            });
        })
            ->values();

        $reportSummaries = $reports
            ->map(fn (array $item): array => [
                'date' => $item['report']->report_date->toDateString(),
                'site' => $item['site'],
                'weather' => $item['report']->weather_condition->value,
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
            clientCompany: $project->client->company_name,
            dateRange: $startDay->toDateString().' — '.$endDay->toDateString(),
            workerCount: $reports->sum(fn (array $item): int => $item['report']->workerAllocations->count()),
            totalHours: number_format((float) $reports->sum(fn (array $item): float => $item['report']->workerAllocations->sum('hours_worked')), 2),
            reportSummaries: $reportSummaries,
            milestones: $milestones,
            generatedAt: now()->toDateTimeString(),
            periodFrom: $startDay->toDateString(),
            periodTo: $endDay->toDateString(),
            locale: $locale ?? app()->getLocale(),
        );
    }

    /**
     * Worker Allocation & Payroll Summary (PRD §7.4) — the worker-allocation
     * content split out of the Daily Progress PDF, aggregated across
     * (published) reports within a date range. When a payroll run is given,
     * the regular/overtime pay breakdown from its `payroll_items` (sourced
     * from `worker_attendance`, PRD §5.4) is included in the document.
     *
     * @param  Collection<int, DailyReport>  $reports
     */
    public static function forWorkerAllocation(Collection $reports, Carbon $start, Carbon $end, ?string $locale = null, ?PayrollRun $payrollRun = null): self
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

        [$payrollItems, $payrollTotal] = $payrollRun === null
            ? [[], null]
            : self::payrollSection($payrollRun);

        $first = $reports->first();

        return new self(
            type: DocumentType::WorkerAllocationPayroll,
            title: DocumentType::WorkerAllocationPayroll->label(),
            projectName: $first?->site->project->name ?? '',
            projectCode: $first?->site->project->code ?? '',
            clientCompany: $first?->site->project->client->company_name ?? '',
            dateRange: $start->toDateString().' — '.$end->toDateString(),
            workerCount: count($workerRows),
            totalHours: number_format((float) $allocations->sum('hours_worked'), 2),
            workerRows: $workerRows,
            payrollItems: $payrollItems,
            payrollTotal: $payrollTotal,
            generatedAt: now()->toDateTimeString(),
            periodFrom: $start->toDateString(),
            periodTo: $end->toDateString(),
            locale: $locale ?? app()->getLocale(),
        );
    }

    /**
     * Queue-safe payroll breakdown rows from a payroll run's items.
     *
     * @return array{0: list<array<string, string>>, 1: string}
     */
    protected static function payrollSection(PayrollRun $run): array
    {
        /** @var Collection<int, PayrollItem> $items */
        $items = $run->items()->orderBy('id')->get();

        $rows = $items
            ->map(function (PayrollItem $item): array {
                $worker = Worker::withTrashed()->find($item->worker_id);

                return [
                    'name' => $worker->full_name,
                    'trade' => $worker->trade_skill,
                    'regular_hours' => (string) $item->regular_hours_total,
                    'overtime_hours' => (string) $item->overtime_hours_total,
                    'regular_pay' => number_format((float) $item->regular_pay, 2),
                    'overtime_pay' => number_format((float) $item->overtime_pay, 2),
                    'total_pay' => number_format((float) $item->total_pay, 2),
                ];
            })
            ->values()
            ->all();

        return [$rows, number_format((float) $run->items()->sum('total_pay'), 2)];
    }

    /**
     * Builds a labor roster grouped by worker across the given (published)
     * reports within a date range.
     *
     * @param  Collection<int, DailyReport>  $reports
     */
    public static function forAttendanceRoster(Collection $reports, Carbon $start, Carbon $end, ?string $locale = null): self
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

        $first = $reports->first();

        return new self(
            type: DocumentType::AttendanceRoster,
            title: DocumentType::AttendanceRoster->label(),
            projectName: $first?->site->project->name ?? '',
            projectCode: $first?->site->project->code ?? '',
            clientCompany: $first?->site->project->client->company_name ?? '',
            dateRange: $start->toDateString().' — '.$end->toDateString(),
            workerCount: count($workerRows),
            totalHours: number_format((float) $allocations->sum('hours_worked'), 2),
            workerRows: $workerRows,
            generatedAt: now()->toDateTimeString(),
            periodFrom: $start->toDateString(),
            periodTo: $end->toDateString(),
            locale: $locale ?? app()->getLocale(),
        );
    }
}
