<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * One before/after progress-photo pair per daily report (PRD v3 §4).
 *
 * @property string $daily_report_id
 * @property string $before_file_path
 * @property string|null $before_thumbnail_path
 * @property string|null $after_file_path
 * @property string|null $after_thumbnail_path
 * @property string|null $description
 * @property string|null $captured_at
 * @property int|null $file_size_bytes
 * @property-read DailyReport $dailyReport
 */
class DailyReportPhoto extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'daily_report_id',
        'before_file_path',
        'before_thumbnail_path',
        'after_file_path',
        'after_thumbnail_path',
        'description',
        'captured_at',
        'file_size_bytes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'file_size_bytes' => 'integer',
        ];
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function signedBeforeUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('photos')->temporaryUrl($this->before_file_path, now()->addMinutes($expiresInMinutes));
    }

    public function signedAfterUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('photos')->temporaryUrl((string) $this->after_file_path, now()->addMinutes($expiresInMinutes));
    }

    public function signedBeforeThumbnailUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('photos')->temporaryUrl((string) $this->before_thumbnail_path, now()->addMinutes($expiresInMinutes));
    }

    public function signedAfterThumbnailUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('photos')->temporaryUrl((string) $this->after_thumbnail_path, now()->addMinutes($expiresInMinutes));
    }
}
