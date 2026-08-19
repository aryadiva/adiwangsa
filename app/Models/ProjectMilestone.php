<?php

namespace App\Models;

use App\Enums\ProjectMilestoneStatus;
use App\Services\MilestoneWeightNotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property string $project_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $start_date
 * @property Carbon $target_date
 * @property Carbon|null $completed_at
 * @property ProjectMilestoneStatus $status
 * @property string $weight_percentage
 * @property int $sort_order
 * @property-read Project $project
 * @property-read Collection<int, MilestoneSubJob> $subJobs
 */
class ProjectMilestone extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'start_date',
        'target_date',
        'completed_at',
        'status',
        'weight_percentage',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectMilestoneStatus::class,
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_at' => 'date',
            'weight_percentage' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'start_date', 'target_date', 'completed_at'])
            ->logOnlyDirty();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subJobs(): HasMany
    {
        return $this->hasMany(MilestoneSubJob::class);
    }

    protected static function booted(): void
    {
        static::saved(fn (self $milestone) => MilestoneWeightNotificationService::reconcile((string) $milestone->project_id));
        static::deleted(fn (self $milestone) => MilestoneWeightNotificationService::reconcile((string) $milestone->project_id));
    }
}
