<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerAttendance;
use Illuminate\Support\Carbon;

/**
 * Records HRD attendance rows. `worker_attendance` is the payroll source of
 * truth; the daily duplicate check here backs the partial unique index with
 * a friendly app-layer error (same pattern as daily_reports).
 */
class AttendanceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function record(array $data, ?User $recorder = null): WorkerAttendance
    {
        $attendanceDate = $data['attendance_date'] instanceof Carbon
            ? $data['attendance_date']->toDateString()
            : (string) $data['attendance_date'];

        WorkerAttendance::ensureNotDuplicate($data['worker_id'], $attendanceDate);

        return WorkerAttendance::create([
            ...$data,
            'attendance_date' => $attendanceDate,
            'recorded_by_user_id' => $data['recorded_by_user_id'] ?? $recorder?->id,
            'captured_at' => $data['captured_at'] ?? now(),
        ]);
    }
}
