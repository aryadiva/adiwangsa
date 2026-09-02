<?php

namespace App\Models;

use Database\Factories\WorkerAttendanceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * HRD-recorded daily worker attendance (PRD §4 `worker_attendance`).
 * This table — not `daily_report_workers` — is the payroll source of truth.
 *
 * @property string $id
 * @property string $worker_id
 * @property string $site_id
 * @property string|null $recorded_by_user_id
 * @property Carbon $attendance_date
 * @property string $hours_worked
 * @property string $overtime_hours
 * @property string|null $photo_file_path
 * @property string|null $photo_thumbnail_path
 * @property Carbon|null $captured_at
 * @property array $meta_data
 * @property-read Worker $worker
 * @property-read Site $site
 * @property-read User|null $recordedBy
 */
class WorkerAttendance extends Model
{
    /** @use HasFactory<WorkerAttendanceFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'worker_attendance';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'worker_id',
        'site_id',
        'recorded_by_user_id',
        'attendance_date',
        'hours_worked',
        'overtime_hours',
        'photo_file_path',
        'photo_thumbnail_path',
        'captured_at',
        'meta_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'hours_worked' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'captured_at' => 'datetime',
            'meta_data' => 'array',
        ];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /**
     * App-layer duplicate guard: one active attendance record per worker per
     * day (mirrors the daily_reports friendly-error pattern). The partial
     * unique index in the migration is the backstop for soft-deleted rows.
     *
     * @throws ValidationException
     */
    public static function ensureNotDuplicate(string $workerId, string $attendanceDate, ?string $ignoreId = null): void
    {
        $exists = static::query()
            ->where('worker_id', $workerId)
            ->where('attendance_date', $attendanceDate)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'attendance_date' => __('Attendance has already been recorded for this worker on :date.', [
                    'date' => $attendanceDate,
                ]),
            ]);
        }
    }
}
