<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Per-worker payroll line for a payroll run. Computed from
 * `worker_attendance` (NOT `daily_report_workers`) by PayrollService.
 *
 * @property string $id
 * @property string $payroll_run_id
 * @property string $worker_id
 * @property string $regular_hours_total
 * @property string $overtime_hours_total
 * @property string $regular_pay
 * @property string $overtime_pay
 * @property string $total_pay
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read PayrollRun $payrollRun
 * @property-read Worker $worker
 */
class PayrollItem extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'payroll_run_id',
        'worker_id',
        'regular_hours_total',
        'overtime_hours_total',
        'regular_pay',
        'overtime_pay',
        'total_pay',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'regular_hours_total' => 'decimal:2',
            'overtime_hours_total' => 'decimal:2',
            'regular_pay' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'total_pay' => 'decimal:2',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
