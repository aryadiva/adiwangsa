<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $milestone_sub_job_id
 * @property string|null $project_id
 * @property string|null $site_id
 * @property string|null $daily_report_id
 * @property Carbon $report_date
 * @property string|null $baseline_target
 * @property string|null $carried_deficit
 * @property string|null $daily_target
 * @property string|null $actual_progress
 * @property string|null $deficit
 * @property Carbon|null $first_triggered_at
 * @property Carbon|null $resolved_at
 * @property array $meta_data
 * @property-read MilestoneSubJob $subJob
 * @property-read Project|null $project
 * @property-read Site|null $site
 * @property-read DailyReport|null $dailyReport
 */
class TargetDelayWarning extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'milestone_sub_job_id',
        'project_id',
        'site_id',
        'daily_report_id',
        'report_date',
        'baseline_target',
        'carried_deficit',
        'daily_target',
        'actual_progress',
        'deficit',
        'first_triggered_at',
        'resolved_at',
        'meta_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'baseline_target' => 'decimal:2',
            'carried_deficit' => 'decimal:2',
            'daily_target' => 'decimal:2',
            'actual_progress' => 'decimal:2',
            'deficit' => 'decimal:2',
            'first_triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
            'meta_data' => 'array',
        ];
    }

    public function subJob(): BelongsTo
    {
        return $this->belongsTo(MilestoneSubJob::class, 'milestone_sub_job_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function isActive(): bool
    {
        return $this->resolved_at === null;
    }

    public function isFirstTrigger(): bool
    {
        return $this->first_triggered_at === null;
    }
}
