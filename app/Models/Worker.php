<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $full_name
 * @property string|null $trade_skill
 * @property string|null $daily_rate
 * @property bool $is_active
 * @property Carbon|null $active_start_date
 * @property Carbon|null $deactivation_date
 * @property string|null $phone_number
 * @property string|null $bank_account_number
 * @property string|null $bank_account_name
 * @property array $meta_data
 * @property-read Collection<int, DailyReportWorker> $reportAllocations
 * @property-read Collection<int, WorkerAttendance> $attendance
 */
class Worker extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'trade_skill',
        'daily_rate',
        'is_active',
        'active_start_date',
        'deactivation_date',
        'phone_number',
        'bank_account_number',
        'bank_account_name',
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
            'daily_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'active_start_date' => 'date',
            'deactivation_date' => 'date',
            'meta_data' => 'array',
        ];
    }

    public function reportAllocations(): HasMany
    {
        return $this->hasMany(DailyReportWorker::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(WorkerAttendance::class);
    }
}
