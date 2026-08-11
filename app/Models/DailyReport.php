<?php

namespace App\Models;

use App\Enums\DailyReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property DailyReportStatus $status
 * @property string $site_id
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
}
