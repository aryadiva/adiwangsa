<?php

namespace App\Models;

use App\Enums\MilestoneSubJobStatus;
use App\Services\MilestoneWeightNotificationService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $project_milestone_id
 * @property string $title
 * @property string|null $description
 * @property Carbon $start_date
 * @property int $working_days
 * @property string $quantity
 * @property string $weight_percentage
 * @property MilestoneSubJobStatus $status
 * @property int $sort_order
 * @property-read ProjectMilestone $projectMilestone
 */
class MilestoneSubJob extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_milestone_id',
        'title',
        'description',
        'start_date',
        'working_days',
        'quantity',
        'weight_percentage',
        'status',
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
            'status' => MilestoneSubJobStatus::class,
            'start_date' => 'date',
            'quantity' => 'decimal:2',
            'weight_percentage' => 'decimal:2',
        ];
    }

    public function projectMilestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class);
    }

    protected static function booted(): void
    {
        static::saved(function (self $subJob): void {
            $projectId = $subJob->projectMilestone()->withTrashed()->value('project_id');

            if ($projectId !== null) {
                MilestoneWeightNotificationService::reconcile((string) $projectId);
            }
        });

        static::deleted(function (self $subJob): void {
            $projectId = $subJob->projectMilestone()->withTrashed()->value('project_id');

            if ($projectId !== null) {
                MilestoneWeightNotificationService::reconcile((string) $projectId);
            }
        });
    }
}
