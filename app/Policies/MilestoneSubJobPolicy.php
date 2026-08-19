<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MilestoneSubJob;
use App\Models\User;

class MilestoneSubJobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MilestoneSubJob $subJob): bool
    {
        $projectId = $subJob->projectMilestone->project_id;

        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->projects()->whereKey($projectId)->exists(),
            UserRole::Client => $user->client?->projects()->whereKey($projectId)->exists() ?? false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, MilestoneSubJob $subJob): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, MilestoneSubJob $subJob): bool
    {
        return $user->role === UserRole::Admin;
    }
}
