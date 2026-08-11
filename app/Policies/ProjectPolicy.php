<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->projects()->whereKey($project->id)->exists(),
            UserRole::Client => $user->client?->projects()->whereKey($project->id)->exists() ?? false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin;
    }
}
