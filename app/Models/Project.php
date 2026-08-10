<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Project extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'code',
        'status',
        'start_date',
        'target_end_date',
        'budget',
        'timezone',
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
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'target_end_date' => 'date',
            'budget' => 'decimal:2',
            'meta_data' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'status', 'budget'])
            ->logOnlyDirty();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function engineers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function timezoneInstance(): \DateTimeZone
    {
        return new \DateTimeZone($this->timezone ?: 'UTC');
    }

    public function localize(\DateTimeInterface $utc): Carbon
    {
        return Carbon::parse($utc)->setTimezone($this->timezoneInstance());
    }
}
