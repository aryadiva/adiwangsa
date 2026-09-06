<?php

namespace App\Filament\Resources\WorkerAttendanceResource\Pages;

use App\Filament\Components\LiveCapture;
use App\Filament\Resources\WorkerAttendanceResource;
use App\Models\WorkerAttendance;
use App\Services\PhotoCaptureService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditWorkerAttendance extends EditRecord
{
    protected static string $resource = WorkerAttendanceResource::class;

    /** @var array<string, mixed> */
    protected array $photoState = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record instanceof WorkerAttendance) {
            $data['attendance_photo'] = $this->record->photo_file_path;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->record instanceof WorkerAttendance) {
            return $data;
        }

        WorkerAttendance::ensureNotDuplicate($data['worker_id'], (string) $data['attendance_date'], $this->record->id);

        $this->photoState = Arr::only($data, ['attendance_photo']);

        return Arr::except($data, ['attendance_photo']);
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof WorkerAttendance) {
            return;
        }

        $resolved = LiveCapture::resolveState($this->photoState['attendance_photo'] ?? null, PhotoCaptureService::ATTENDANCE_DIRECTORY);

        if ($resolved === null) {
            return;
        }

        // Only stamp when the photo actually changed (a recapture produces a
        // different path than the stored one).
        if ($resolved['path'] === $this->record->photo_file_path) {
            return;
        }

        $this->record->forceFill([
            'photo_file_path' => $resolved['path'],
            'photo_thumbnail_path' => $resolved['thumbnail_path'] ?? app(PhotoCaptureService::class)->thumbnailPathFor($resolved['path']),
            'captured_at' => $resolved['captured_at'],
            'meta_data' => array_merge($this->record->meta_data ?? [], [
                'capture' => [
                    'captured_at' => $resolved['captured_at']->toIso8601String(),
                    'method' => 'live',
                    'recorded_by_user_id' => auth()->id(),
                ],
            ]),
        ])->save();
    }
}
