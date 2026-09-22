<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Components\LiveCapture;
use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use App\Services\DailyReportPhotoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CreateDailyReport extends CreateRecord
{
    protected static string $resource = DailyReportResource::class;

    /** @var array<string, mixed> */
    protected array $photoState = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        static::assertUniqueSiteDate($data);

        $data['created_by_user_id'] = auth()->id();

        $this->photoState = Arr::only($data, [
            'before_photo',
            'after_photo',
            'before_file_path',
            'after_file_path',
            'photo_description',
        ]);

        return Arr::except($data, array_keys($this->photoState));
    }

    protected function afterCreate(): void
    {
        if (! $this->record instanceof DailyReport) {
            return;
        }

        $pair = $this->resolvePhotoPair();

        if ($pair !== null) {
            $this->record->photos()->create($pair);
        }
    }

    /**
     * Resolves the one before/after pair row payload from the captured
     * form state (live camera captures or Admin device uploads).
     *
     * @return array<string, mixed>|null
     */
    protected function resolvePhotoPair(): ?array
    {
        $service = app(DailyReportPhotoService::class);

        $before = LiveCapture::resolveState($this->photoState['before_photo'] ?? $this->photoState['before_file_path'] ?? null, $service::DIRECTORY);
        $after = LiveCapture::resolveState($this->photoState['after_photo'] ?? $this->photoState['after_file_path'] ?? null, $service::DIRECTORY);

        if ($before === null || $after === null) {
            return null;
        }

        return [
            'before_file_path' => $before['path'],
            'before_thumbnail_path' => $before['thumbnail_path'] ?? $service->thumbnailPathFor($before['path']),
            'after_file_path' => $after['path'],
            'after_thumbnail_path' => $after['thumbnail_path'] ?? $service->thumbnailPathFor($after['path']),
            'description' => $this->photoState['photo_description'] ?? null,
            'captured_at' => max($before['captured_at'], $after['captured_at']),
            'file_size_bytes' => $before['file_size_bytes'] + $after['file_size_bytes'],
        ];
    }

    public static function assertUniqueSiteDate(array $data, ?DailyReport $ignore = null): void
    {
        $shift = $data['shift'] ?? 'shift_1';

        $exists = DailyReport::query()
            ->where('site_id', $data['site_id'])
            ->where('report_date', $data['report_date'])
            ->where('shift', $shift)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.report_date' => __('app.daily_report.duplicate'),
            ]);
        }
    }
}
