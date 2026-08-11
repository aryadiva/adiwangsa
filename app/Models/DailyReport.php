<?php

namespace App\Models;

use App\Enums\DailyReportStatus;
use App\Enums\UserRole;
use App\Notifications\ReportApprovedNotification;
use App\Notifications\ReportPublishedNotification;
use App\Notifications\ReportSubmittedNotification;
use App\Notifications\RevisionRequestedNotification;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property DailyReportStatus $status
 * @property string $site_id
 * @property-read Collection<int, DailyReportWorker> $workerAllocations
 * @property-read Collection<int, DailyReportRevision> $revisions
 * @property-read Collection<int, DailyReportPhoto> $photos
 *
 * @method static Builder<static> forSiteEngineer(User $user)
 * @method static Builder<static> forClient(User $user)
 */
class DailyReport extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'site_id',
        'created_by_user_id',
        'reviewed_by_user_id',
        'report_date',
        'weather_condition',
        'work_summary',
        'delays_or_issues',
        'status',
        'admin_notes',
        'meta_data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'status' => DailyReportStatus::class,
            'meta_data' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'work_summary', 'weather_condition', 'delays_or_issues'])
            ->logOnlyDirty();
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(DailyReportRevision::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DailyReportPhoto::class);
    }

    public function workerAllocations(): HasMany
    {
        return $this->hasMany(DailyReportWorker::class);
    }

    public function scopeForSiteEngineer(Builder $query, User $user): Builder
    {
        return $query->whereHas('site.project', function (Builder $q) use ($user): void {
            $q->whereHas('engineers', fn (Builder $engineers) => $engineers->whereKey($user->id));
        });
    }

    public function scopeForClient(Builder $query, User $user): Builder
    {
        $projectIds = $user->client?->projects()->pluck('id') ?? collect();

        return $query
            ->where('status', DailyReportStatus::Published)
            ->whereHas('site', fn (Builder $sites) => $sites->whereIn('project_id', $projectIds));
    }

    /**
     * @return list<DailyReportStatus>
     */
    public function allowedNextStatuses(): array
    {
        $map = [
            DailyReportStatus::Draft->value => [DailyReportStatus::NeedApproval],
            DailyReportStatus::NeedApproval->value => [DailyReportStatus::Published, DailyReportStatus::RevisionRequested],
            DailyReportStatus::RevisionRequested->value => [DailyReportStatus::NeedApproval],
            DailyReportStatus::Published->value => [],
        ];

        foreach ($map as $source => $next) {
            if ($source === $this->status->value) {
                return $next;
            }
        }

        return [];
    }

    protected function assertCanTransitionTo(DailyReportStatus $next): void
    {
        if (! in_array($next, $this->allowedNextStatuses(), true)) {
            throw new DomainException(
                "Illegal status transition from [{$this->status->value}] to [{$next->value}]."
            );
        }
    }

    public function submitForApproval(): void
    {
        $this->assertCanTransitionTo(DailyReportStatus::NeedApproval);
        $this->forceFill(['status' => DailyReportStatus::NeedApproval])->save();
        $this->notifyAdminsSubmitted();
    }

    public function approveAndPublish(?string $reviewedByUserId = null): void
    {
        $this->assertCanTransitionTo(DailyReportStatus::Published);
        $this->forceFill([
            'status' => DailyReportStatus::Published,
            'reviewed_by_user_id' => $reviewedByUserId,
        ])->save();

        $this->createdBy?->notify(new ReportApprovedNotification($this));

        $clientUser = $this->site?->project?->client?->user;
        $clientUser?->notify(new ReportPublishedNotification($this));
    }

    public function requestRevision(?string $adminNotes = null): void
    {
        $this->assertCanTransitionTo(DailyReportStatus::RevisionRequested);
        $this->forceFill([
            'status' => DailyReportStatus::RevisionRequested,
            'admin_notes' => $adminNotes,
        ])->save();

        $this->createdBy?->notify(new RevisionRequestedNotification($this));
    }

    public function resubmitForApproval(?string $editedByUserId = null): void
    {
        $this->assertCanTransitionTo(DailyReportStatus::NeedApproval);

        $this->revisions()->create([
            'snapshot' => $this->buildSnapshot(),
            'edited_by_user_id' => $editedByUserId,
        ]);

        $this->forceFill(['status' => DailyReportStatus::NeedApproval])->save();
        $this->notifyAdminsSubmitted();
    }

    protected function notifyAdminsSubmitted(): void
    {
        $admins = User::query()->where('role', UserRole::Admin)->get();
        Notification::send($admins, new ReportSubmittedNotification($this));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSnapshot(): array
    {
        return array_merge($this->getAttributes(), [
            'worker_allocations' => $this->workerAllocations
                ->map(fn (DailyReportWorker $worker): array => [
                    'worker_id' => $worker->worker_id,
                    'hours_worked' => (string) $worker->hours_worked,
                    'remarks' => $worker->remarks,
                ])
                ->values()
                ->all(),
            'photo_paths' => $this->photos()->pluck('file_path')->all(),
        ]);
    }
}
