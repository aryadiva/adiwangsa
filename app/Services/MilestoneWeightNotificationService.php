<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Notifications\WeightIncompleteNotification;
use App\Support\WeightValidation;
use Illuminate\Support\Facades\Notification;

class MilestoneWeightNotificationService
{
    /**
     * Reconcile the weight-incomplete bell notification for a project after any
     * milestone/sub-job change. Zero-milestone projects are ignored; a set with
     * at least one row must reach 100%. Behaves like a normal notification —
     * created once per project, deleted when the weights reach 100%, dismissible
     * by the user (no persistence, no re-hydration).
     */
    public static function reconcile(string $projectId): void
    {
        $project = Project::query()->with('milestones.subJobs')->find($projectId);

        if ($project !== null) {
            static::reconcileProject($project);
        }
    }

    public static function reconcileProject(Project $project): void
    {
        $incompleteSets = static::incompleteSets($project);
        $projectKey = $project->getKey();

        $existsForAnyAdmin = User::where('role', UserRole::Admin)
            ->get()
            ->contains(fn (User $user) => $user->notifications()
                ->whereType(WeightIncompleteNotification::class)
                ->where('data->project_id', $projectKey)
                ->exists());

        if ($incompleteSets === []) {
            User::where('role', UserRole::Admin)
                ->get()
                ->each(fn (User $user) => $user->notifications()
                    ->whereType(WeightIncompleteNotification::class)
                    ->where('data->project_id', $projectKey)
                    ->delete());

            return;
        }

        if ($existsForAnyAdmin) {
            return;
        }

        $admins = User::where('role', UserRole::Admin)->get();

        Notification::send($admins, new WeightIncompleteNotification($project, $incompleteSets));
    }

    /**
     * @return array<int, array{title: string, total: string}>
     */
    public static function incompleteSets(Project $project): array
    {
        $sets = [];

        $milestones = $project->milestones;

        if ($milestones->isNotEmpty()) {
            $sum = WeightValidation::sum($milestones->pluck('weight_percentage')->all());

            if (! WeightValidation::isFull($sum)) {
                $sets[] = ['title' => 'Milestone weights', 'total' => number_format($sum, 2)];
            }
        }

        foreach ($milestones as $milestone) {
            $subJobs = $milestone->subJobs;

            if ($subJobs->isEmpty()) {
                continue;
            }

            $subSum = WeightValidation::sum($subJobs->pluck('weight_percentage')->all());

            if (! WeightValidation::isFull($subSum)) {
                $sets[] = ['title' => "{$milestone->title} sub-jobs", 'total' => number_format($subSum, 2)];
            }
        }

        return $sets;
    }
}
