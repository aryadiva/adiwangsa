<?php

namespace App\Models;

use App\Enums\DelayEventStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Sub-job delay event — lightweight red → yellow → green state machine
 * (PRD §5.3). A new delay after a green resolution creates a NEW event
 * back at red; there is no reopening of a resolved event.
 *
 * @property string $id
 * @property string $milestone_sub_job_id
 * @property DelayEventStatus $status
 * @property Carbon $triggered_at
 * @property string|null $mitigation_plan
 * @property string|null $mitigation_submitted_by_user_id
 * @property Carbon|null $resolved_at
 * @property int $delay_days
 * @property-read MilestoneSubJob $subJob
 * @property-read User|null $mitigationSubmittedBy
 */
class SubJobDelayEvent extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'milestone_sub_job_id',
        'status',
        'triggered_at',
        'mitigation_plan',
        'mitigation_submitted_by_user_id',
        'resolved_at',
        'delay_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DelayEventStatus::class,
            'triggered_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function subJob(): BelongsTo
    {
        return $this->belongsTo(MilestoneSubJob::class, 'milestone_sub_job_id');
    }

    public function mitigationSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mitigation_submitted_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status !== DelayEventStatus::Green;
    }

    /**
     * Guard: only the expected current status allows the transition.
     */
    protected function ensureStatus(DelayEventStatus $expected, string $action): void
    {
        if ($this->status !== $expected) {
            throw new \DomainException(
                "Cannot {$action} a [{$this->status->value}] delay event (must be [{$expected->value}])."
            );
        }
    }

    /**
     * Red → Yellow. Requires a non-empty mitigation plan.
     */
    public function submitMitigationPlan(string $plan, ?User $user = null): static
    {
        $this->ensureStatus(DelayEventStatus::Red, 'submit a mitigation plan on');

        if (trim($plan) === '') {
            throw new \InvalidArgumentException('A mitigation plan is required.');
        }

        $this->forceFill([
            'status' => DelayEventStatus::Yellow,
            'mitigation_plan' => $plan,
            'mitigation_submitted_by_user_id' => $user?->id ?? auth()->id(),
        ])->save();

        return $this;
    }

    /**
     * Yellow → Green. Recovery confirmed; the event is closed.
     */
    public function markRecovered(?User $user = null): static
    {
        $this->ensureStatus(DelayEventStatus::Yellow, 'mark recovered from');

        $this->forceFill([
            'status' => DelayEventStatus::Green,
            'resolved_at' => now(),
        ])->save();

        return $this;
    }
}
