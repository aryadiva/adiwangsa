<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkerAttendance;

/**
 * HRD owns `worker_attendance` (PRD v3 §3/§4). Confirmed scope: HRD manages
 * all records; Admin full; Site Engineer and Client have no access at all.
 */
class WorkerAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Hrd], true);
    }

    public function view(User $user, WorkerAttendance $attendance): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Hrd], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Hrd], true);
    }

    public function update(User $user, WorkerAttendance $attendance): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Hrd], true);
    }

    public function delete(User $user, WorkerAttendance $attendance): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Hrd], true);
    }
}
