<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use App\Services\DailyReportPhotoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDailyReport extends CreateRecord
{
    protected static string $resource = DailyReportResource::class;

    /** @var list<string> */
    protected array $photoPaths = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        static::assertUniqueSiteDate($data);

        $this->photoPaths = $data['file_path'] ?? [];

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->record instanceof DailyReport) {
            return;
        }

        $service = app(DailyReportPhotoService::class);

        foreach ($this->photoPaths as $path) {
            $this->record->photos()->create($service->metadataFor($path));
        }
    }

    public static function assertUniqueSiteDate(array $data, ?DailyReport $ignore = null): void
    {
        $exists = DailyReport::query()
            ->where('site_id', $data['site_id'])
            ->where('report_date', $data['report_date'])
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'data.report_date' => 'A report already exists for this site on this date.',
            ]);
        }
    }
}
