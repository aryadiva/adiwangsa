<?php

namespace App\Services;

use App\Enums\MilestoneSubJobStatus;
use App\Enums\UserRole;
use App\Models\DailyReport;
use App\Models\MilestoneSubJob;
use App\Models\TargetDelayWarning;
use App\Models\User;
use App\Notifications\TargetDelayWarningNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Notification;

/**
 * Daily Target & Deficit Carry-Forward Engine (PRD §5.2).
 *
 * Baseline target = sub_job.quantity / sub_job.working_days.
 * Daily target     = baseline + carried_deficit_from_prior_day.
 *
 * The deficit accumulates forward until achievement catches up, at which
 * point the carried deficit resets to zero.
 */
class DeficitCarryForwardService
{
    /**
     * Compute the baseline daily target for a sub-job.
     */
    public static function baselineTarget(MilestoneSubJob $subJob): string
    {
        if ($subJob->working_days <= 0) {
            return '0.00';
        }

        return bcdiv($subJob->quantity, (string) $subJob->working_days, 2);
    }

    /**
     * Compute the carried deficit for a sub-job on a given date.
     *
     * The deficit is the most recent prior day's shortfall, floored at zero:
     * carried = max(0, prior_day_target - prior_day_achievement).
     *
     * Because each day's target already embeds the carried deficit, only the
     * immediately preceding day is consulted — summing every historical day
     * would double-count the shortfall.
     */
    public static function carriedDeficit(MilestoneSubJob $subJob, CarbonInterface $date): string
    {
        $latestDate = DailyReport::query()
            ->where('milestone_sub_job_id', $subJob->id)
            ->where('report_date', '<', $date->toDateString())
            ->max('report_date');

        if ($latestDate === null) {
            return '0.00';
        }

        $reports = DailyReport::query()
            ->where('milestone_sub_job_id', $subJob->id)
            ->whereDate('report_date', $latestDate)
            ->get();

        $baseline = self::baselineTarget($subJob);
        $firstReport = $reports->first();
        $target = $firstReport !== null && $firstReport->daily_target !== null
            ? $firstReport->daily_target
            : $baseline;
        $achievement = $reports->sum(fn (DailyReport $r): float => (float) ($r->daily_achievement ?? 0));

        $deficit = bcsub((string) $target, (string) $achievement, 2);

        return bccomp($deficit, '0.00', 2) > 0 ? $deficit : '0.00';
    }

    /**
     * Compute the daily target for a sub-job on a given date.
     */
    public static function computeDailyTarget(MilestoneSubJob $subJob, CarbonInterface $date): string
    {
        return bcadd(self::baselineTarget($subJob), self::carriedDeficit($subJob, $date), 2);
    }

    /**
     * Compute and set daily_target on all shift reports for a given sub-job
     * on a given date. Called by the nightly scheduled job.
     *
     * @return int Number of reports updated.
     */
    public static function recomputeDailyTargets(MilestoneSubJob $subJob, CarbonInterface $date): int
    {
        $target = self::computeDailyTarget($subJob, $date);

        $reports = DailyReport::query()
            ->where('milestone_sub_job_id', $subJob->id)
            ->whereDate('report_date', $date->toDateString())
            ->get();

        foreach ($reports as $report) {
            $report->forceFill(['daily_target' => $target])->save();
        }

        return $reports->count();
    }

    /**
     * Compute and assign the daily_target on a single report.
     * Called when a shift report is saved with a milestone_sub_job_id.
     */
    public static function computeAndAssignDailyTarget(DailyReport $report, CarbonInterface $date): void
    {
        if ($report->milestone_sub_job_id === null) {
            return;
        }

        $subJob = $report->milestoneSubJob()->first();

        if ($subJob === null) {
            return;
        }

        $target = self::computeDailyTarget($subJob, $date);
        $report->forceFill(['daily_target' => $target])->save();
    }

    /**
     * Evaluate whether a deficit exists for the given report and manage
     * the target_delay_warning lifecycle.
     *
     * - If achievement < target and no active warning exists: create one,
     *   set first_triggered_at, notify admin (once only).
     * - If achievement < target and an active warning exists: update its
     *   deficit snapshot but do NOT re-notify.
     * - If achievement >= target and an active warning exists: resolve it.
     */
    public static function evaluateTargetDeficit(DailyReport $report, ?CarbonInterface $asOf = null): void
    {
        if ($report->milestone_sub_job_id === null) {
            return;
        }

        if ($report->daily_target === null || $report->daily_achievement === null) {
            return;
        }

        $deficit = bcsub((string) $report->daily_target, (string) $report->daily_achievement, 2);
        $hasDeficit = bccomp($deficit, '0.00', 2) > 0;

        $subJob = $report->milestoneSubJob()->first();

        if ($subJob === null) {
            return;
        }

        /** @var TargetDelayWarning|null $warning */
        $warning = TargetDelayWarning::query()
            ->where('milestone_sub_job_id', $report->milestone_sub_job_id)
            ->whereNull('resolved_at')
            ->latest()
            ->first();

        $project = $subJob->projectMilestone->project;
        $asOf ??= now();

        if ($hasDeficit) {
            if ($warning === null) {
                $warning = TargetDelayWarning::create([
                    'milestone_sub_job_id' => $report->milestone_sub_job_id,
                    'project_id' => $project->id,
                    'site_id' => $report->site_id,
                    'daily_report_id' => $report->id,
                    'report_date' => $report->report_date,
                    'baseline_target' => self::baselineTarget($subJob),
                    'carried_deficit' => self::carriedDeficit($subJob, $report->report_date),
                    'daily_target' => $report->daily_target,
                    'actual_progress' => $report->daily_achievement,
                    'deficit' => $deficit,
                    'first_triggered_at' => $asOf,
                    'resolved_at' => null,
                ]);

                self::notifyAdmins($warning);
            } else {
                $warning->update([
                    'deficit' => $deficit,
                    'actual_progress' => $report->daily_achievement,
                    'daily_target' => $report->daily_target,
                    'daily_report_id' => $report->id,
                ]);
            }
        } else {
            if ($warning !== null) {
                $warning->update([
                    'resolved_at' => $asOf,
                    'deficit' => '0.00',
                    'actual_progress' => $report->daily_achievement,
                ]);
            }
        }
    }

    /**
     * Run the full nightly evaluation: walk all open sub-jobs, recompute
     * daily targets, and evaluate deficits for all published reports on
     * the given date.
     */
    public static function runNightlyEvaluation(CarbonInterface $date): void
    {
        $subJobs = MilestoneSubJob::query()
            ->whereIn('status', [
                MilestoneSubJobStatus::Pending,
                MilestoneSubJobStatus::InProgress,
                MilestoneSubJobStatus::Delayed,
            ])
            ->get();

        foreach ($subJobs as $subJob) {
            self::recomputeDailyTargets($subJob, $date);

            $reports = DailyReport::query()
                ->where('milestone_sub_job_id', $subJob->id)
                ->whereDate('report_date', $date->toDateString())
                ->whereNotNull('daily_achievement')
                ->get();

            foreach ($reports as $report) {
                self::evaluateTargetDeficit($report, $date);
            }
        }
    }

    /**
     * Notify all admins about a new target delay warning (first occurrence only).
     */
    protected static function notifyAdmins(TargetDelayWarning $warning): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->get();

        Notification::send($admins, new TargetDelayWarningNotification($warning));
    }
}
