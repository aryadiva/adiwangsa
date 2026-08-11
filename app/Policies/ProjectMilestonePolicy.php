<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProjectMilestone;
use App\Models\User;

class ProjectMilestonePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectMilestone $milestone): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->projects()->whereKey($milestone->project_id)->exists(),
            UserRole::Client => $user->client?->projects()->whereKey($milestone->project_id)->exists() ?? false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, ProjectMilestone $milestone): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, ProjectMilestone $milestone): bool
    {
        return $user->role === UserRole::Admin;
    }
}
