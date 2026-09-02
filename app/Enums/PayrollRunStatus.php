<?php

namespace App\Enums;

enum PayrollRunStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enum.payroll_run_status.draft'),
            self::PendingReview => __('enum.payroll_run_status.pending_review'),
            self::Approved => __('enum.payroll_run_status.approved'),
            self::Paid => __('enum.payroll_run_status.paid'),
        };
    }
}
