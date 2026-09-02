<?php

namespace App\Services;

use App\Enums\DelayEventStatus;
use App\Enums\MilestoneSubJobStatus;
use App\Models\MilestoneSubJob;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\SubJobDelayEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Automated Delay Cascade (PRD §5.3).
 *
 * Detection: a sub-job that is not completed and whose elapsed days past its
 * planned end (start_date + working_days) exceed the project's
 * delay_threshold_days breaches the threshold.
 *
 * On breach (and only when no active event exists for the sub-job):
 *   1. A SubJobDelayEvent row is created in red.
 *   2. All subsequent milestones (sort_order > the delayed sub-job's
 *      milestone) have their target_date shifted by the delay delta, and the
 *      project's target_end_date is shifted likewise — atomically in one
 *      transaction (AGENTS.md gotcha).
 *
 * After a green resolution, a fresh breach creates a NEW red event and the
 * cascade repeats.
 */
class DelayCascadeService
{
    /**
     * Planned end date of a sub-job (inclusive working window).
     */
    public static function plannedEndDate(MilestoneSubJob $subJob): CarbonInterface
    {
        return $subJob->start_date->copy()->addDays(max(0, $subJob->working_days - 1))->endOfDay();
    }

    /**
     * Cumulative delay in days: how far "now" is past the planned end.
     * Zero when the sub-job is on/ahead of schedule or completed.
     */
    public static function cumulativeDelayDays(MilestoneSubJob $subJob, CarbonInterface $asOf): int
    {
        if ($subJob->status === MilestoneSubJobStatus::Completed) {
            return 0;
        }

        return max(0, (int) $subJob->start_date
            ->copy()
            ->addDays(max(0, $subJob->working_days))
            ->startOfDay()
            ->diffInDays($asOf->copy()->startOfDay(), false));
    }

    /**
     * Whether the sub-job's cumulative delay exceeds the project threshold.
     */
    public static function isBreached(MilestoneSubJob $subJob, CarbonInterface $asOf): bool
    {
        $threshold = (int) ($subJob->projectMilestone->project->delay_threshold_days ?? 2);

        return self::cumulativeDelayDays($subJob, $asOf) > $threshold;
    }

    /**
     * Run detection over all open sub-jobs and create red events + cascade
     * date shifts for every threshold breach without an active event.
     *
     * @return list<string> IDs of newly created delay events.
     */
    public static function runDetection(CarbonInterface $asOf): array
    {
        $created = [];

        $subJobs = MilestoneSubJob::query()
            ->with(['projectMilestone.project'])
            ->whereIn('status', [
                MilestoneSubJobStatus::Pending,
                MilestoneSubJobStatus::InProgress,
                MilestoneSubJobStatus::Delayed,
            ])
            ->get();

        foreach ($subJobs as $subJob) {
            if (! self::isBreached($subJob, $asOf)) {
                continue;
            }

            $hasActiveEvent = SubJobDelayEvent::query()
                ->where('milestone_sub_job_id', $subJob->id)
                ->whereNot('status', DelayEventStatus::Green->value)
                ->exists();

            if ($hasActiveEvent) {
                continue;
            }

            $created[] = self::createEventWithCascade($subJob, $asOf)->id;
        }

        return $created;
    }

    /**
     * Atomically create the red event and shift downstream dates.
     */
    public static function createEventWithCascade(MilestoneSubJob $subJob, ?CarbonInterface $asOf = null): SubJobDelayEvent
    {
        $asOf ??= now();
        $delayDays = self::cumulativeDelayDays($subJob, $asOf);
        $project = $subJob->projectMilestone->project;

        return DB::transaction(function () use ($subJob, $project, $asOf, $delayDays): SubJobDelayEvent {
            $event = SubJobDelayEvent::create([
                'milestone_sub_job_id' => $subJob->id,
                'status' => DelayEventStatus::Red,
                'triggered_at' => $asOf,
                'delay_days' => $delayDays,
            ]);

            self::cascadeDateShift($project, $subJob->projectMilestone, $delayDays);

            return $event;
        });
    }

    /**
     * Shift the target_date of all milestones after the given one (by
     * sort_order) and the project's target_end_date by the delay delta.
     */
    protected static function cascadeDateShift(Project $project, ProjectMilestone $originMilestone, int $delayDays): void
    {
        if ($delayDays <= 0) {
            return;
        }

        ProjectMilestone::query()
            ->where('project_id', $project->id)
            ->where('sort_order', '>', $originMilestone->sort_order)
            ->whereNotNull('target_date')
            ->each(function (ProjectMilestone $milestone) use ($delayDays): void {
                $milestone->forceFill([
                    'target_date' => $milestone->target_date->copy()->addDays($delayDays),
                ])->save();
            });

        if ($project->target_end_date !== null) {
            $project->forceFill([
                'target_end_date' => $project->target_end_date->copy()->addDays($delayDays),
            ])->save();
        }
    }
}
