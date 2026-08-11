<?php

namespace App\Enums;

enum DocumentType: string
{
    case DailyProgress = 'daily_progress';
    case WeeklyDigest = 'weekly_digest';
    case AttendanceRoster = 'attendance_roster';

    public function label(): string
    {
        return __('enum.document_type.'.$this->value);
    }
}
