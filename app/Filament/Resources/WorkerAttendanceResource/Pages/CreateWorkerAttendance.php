<?php

namespace App\Filament\Resources\WorkerAttendanceResource\Pages;

use App\Filament\Components\LiveCapture;
use App\Filament\Resources\WorkerAttendanceResource;
use App\Models\WorkerAttendance;
use App\Services\PhotoCaptureService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class CreateWorkerAttendance extends CreateRecord
{
    protected static string $resource = WorkerAttendanceResource::class;

    /** @var array<string, mixed> */
    protected array $photoState = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            WorkerAttendance::ensureNotDuplicate($data['worker_id'], (string) $data['attendance_date']);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'data.attendance_date' => $exception->errors()['attendance_date'][0],
            ]);
        }

        $this->photoState = Arr::only($data, ['attendance_photo']);

        $data = Arr::except($data, ['attendance_photo']);
        $data['recorded_by_user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->record instanceof WorkerAttendance) {
            return;
        }

        $resolved = LiveCapture::resolveState($this->photoState['attendance_photo'] ?? null, PhotoCaptureService::ATTENDANCE_DIRECTORY);

        if ($resolved === null) {
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
