<?php

namespace App\Policies;

use App\Enums\DailyReportStatus;
use App\Enums\UserRole;
use App\Models\DailyReport;
use App\Models\User;

class DailyReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DailyReport $report): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->isAssignedToSite($report->site_id),
            UserRole::Client => $report->status === DailyReportStatus::Published && $user->isClientOfSite($report->site_id),
        };
    }

    public function create(User $user): bool
    {
        return match ($user->role) {
            UserRole::Admin, UserRole::SiteEngineer => true,
            UserRole::Client => false,
        };
    }

    public function update(User $user, DailyReport $report): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->isAssignedToSite($report->site_id),
            UserRole::Client => false,
        };
    }

    public function delete(User $user, DailyReport $report): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::SiteEngineer => $user->isAssignedToSite($report->site_id),
            UserRole::Client => false,
        };
    }
}
