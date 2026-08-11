<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string|null $daily_report_id
 * @property string|null $project_id
 * @property DocumentType $document_type
 * @property string $file_path
 * @property Carbon|null $period_from
 * @property Carbon|null $period_to
 * @property string|null $generated_by_user_id
 * @property-read DailyReport|null $dailyReport
 * @property-read Project|null $project
 * @property-read User|null $generatedBy
 */
class GeneratedDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'daily_report_id',
        'project_id',
        'document_type',
        'file_path',
        'period_from',
        'period_to',
        'generated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'period_from' => 'date',
            'period_to' => 'date',
        ];
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function signedUrl(int $expiresInMinutes = 1440): string
    {
        return Storage::disk('pdfs')->temporaryUrl($this->file_path, now()->addMinutes($expiresInMinutes));
    }
}
