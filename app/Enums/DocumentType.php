<?php

namespace App\Enums;

enum DocumentType: string
{
    case DailyProgress = 'daily_progress';
    case WeeklyDigest = 'weekly_digest';
    case AttendanceRoster = 'attendance_roster';

    public function label(): string
    {
        return match ($this) {
            self::DailyProgress => 'Daily Site Progress Report',
            self::WeeklyDigest => 'Weekly Site Executive Digest',
            self::AttendanceRoster => 'Worker Attendance & Labor Roster',
        };
    }
}
