<?php

namespace App\Models;

use App\Enums\PayrollRunStatus;
use Database\Factories\PayrollRunFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Bi-weekly payroll run (PRD §5.4). System-generated in draft, then
 * draft → pending_review → approved → paid. `paid` is terminal.
 *
 * @property string $id
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property PayrollRunStatus $status
 * @property string|null $generated_by_user_id
 * @property string|null $approved_by_user_id
 * @property-read Collection<int, PayrollItem> $items
 * @property-read User|null $generatedBy
 * @property-read User|null $approvedBy
 */
class PayrollRun extends Model
{
    /** @use HasFactory<PayrollRunFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'period_start',
        'period_end',
        'status',
        'generated_by_user_id',
        'approved_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => PayrollRunStatus::class,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function totalPay(): string
    {
        return bcadd((string) $this->items()->sum('total_pay'), '0', 2);
    }

    /**
     * Guard: only the expected current status allows the transition.
     */
    protected function ensureStatus(PayrollRunStatus $expected, string $action): void
    {
        if ($this->status !== $expected) {
            throw new \DomainException(
                "Cannot {$action} a [{$this->status->value}] payroll run (must be [{$expected->value}])."
            );
        }
    }

    /**
     * Draft → Pending Review.
     */
    public function submitForReview(?User $user = null): static
    {
        $this->ensureStatus(PayrollRunStatus::Draft, 'submit for review');

        $this->forceFill(['status' => PayrollRunStatus::PendingReview])->save();

        return $this;
    }

    /**
     * Pending Review → Approved. Records the approving user.
     */
    public function approve(?User $user = null): static
    {
        $this->ensureStatus(PayrollRunStatus::PendingReview, 'approve');

        $approverId = $user !== null ? $user->id : auth()->id();

        $this->forceFill([
            'status' => PayrollRunStatus::Approved,
            'approved_by_user_id' => $approverId,
        ])->save();

        return $this;
    }

    /**
     * Approved → Paid. Terminal state.
     */
    public function markPaid(?User $user = null): static
    {
        $this->ensureStatus(PayrollRunStatus::Approved, 'mark paid');

        $this->forceFill(['status' => PayrollRunStatus::Paid])->save();

        return $this;
    }
}
