<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $daily_report_id
 * @property string $file_path
 * @property string|null $thumbnail_path
 * @property string|null $caption
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
        'file_path',
        'thumbnail_path',
        'caption',
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
            'file_size_bytes' => 'integer',
        ];
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function signedUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('photos')->temporaryUrl($this->file_path, now()->addMinutes($expiresInMinutes));
    }

    public function signedThumbnailUrl(int $expiresInMinutes = 60): string
    {
        return Storage::disk('photos')->temporaryUrl($this->thumbnail_path, now()->addMinutes($expiresInMinutes));
    }
}
