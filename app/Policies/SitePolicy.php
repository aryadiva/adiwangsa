<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->projects()->whereKey($site->project_id)->exists(),
            UserRole::Client => $user->client?->projects()->whereKey($site->project_id)->exists() ?? false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Site $site): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->role === UserRole::Admin;
    }
}
