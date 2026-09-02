<?php

namespace App\Policies;

use App\Enums\PayrollRunStatus;
use App\Enums\UserRole;
use App\Models\PayrollRun;
use App\Models\User;

class PayrollRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, PayrollRun $payrollRun): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PayrollRun $payrollRun): bool
    {
        return false;
    }

    public function delete(User $user, PayrollRun $payrollRun): bool
    {
        return false;
    }

    public function submitForReview(User $user, PayrollRun $payrollRun): bool
    {
        return $user->role === UserRole::Admin && $payrollRun->status === PayrollRunStatus::Draft;
    }

    public function approve(User $user, PayrollRun $payrollRun): bool
    {
        return $user->role === UserRole::Admin && $payrollRun->status === PayrollRunStatus::PendingReview;
    }

    public function markPaid(User $user, PayrollRun $payrollRun): bool
    {
        return $user->role === UserRole::Admin && $payrollRun->status === PayrollRunStatus::Approved;
    }
}
