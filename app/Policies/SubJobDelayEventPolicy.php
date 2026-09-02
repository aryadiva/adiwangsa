<?php

namespace App\Policies;

use App\Enums\DelayEventStatus;
use App\Enums\UserRole;
use App\Models\SubJobDelayEvent;
use App\Models\User;

class SubJobDelayEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== UserRole::Client;
    }

    public function view(User $user, SubJobDelayEvent $event): bool
    {
        $projectId = $event->subJob->projectMilestone->project_id;

        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->projects()->whereKey($projectId)->exists(),
            UserRole::Client => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, SubJobDelayEvent $event): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, SubJobDelayEvent $event): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function submitMitigationPlan(User $user, SubJobDelayEvent $event): bool
    {
        return $user->role === UserRole::Admin && $event->status === DelayEventStatus::Red;
    }

    public function markRecovered(User $user, SubJobDelayEvent $event): bool
    {
        return $user->role === UserRole::Admin && $event->status === DelayEventStatus::Yellow;
    }
}
