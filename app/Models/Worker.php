<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $full_name
 * @property string|null $trade_skill
 * @property string|null $daily_rate
 * @property bool $is_active
 * @property array $meta_data
 * @property-read Collection<int, DailyReportWorker> $reportAllocations
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
            'meta_data' => 'array',
        ];
    }

    public function reportAllocations(): HasMany
    {
        return $this->hasMany(DailyReportWorker::class);
    }
}
