<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyReport extends EditRecord
{
    protected static string $resource = DailyReportResource::class;

    /** @var list<string> */
    protected array $originalPhotoPaths = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        if ($record instanceof DailyReport) {
            $data['file_path'] = $record->photos()->pluck('file_path')->all();
            $this->originalPhotoPaths = $data['file_path'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->record instanceof DailyReport) {
            return $data;
        }

        CreateDailyReport::assertUniqueSiteDate($data, $this->record);

        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof DailyReport) {
            return;
        }

        $kept = $this->data['file_path'] ?? [];

        foreach ($kept as $path) {
            if (! in_array($path, $this->originalPhotoPaths, true)) {
                $this->record->photos()->create(['file_path' => $path]);
            }
        }

        $this->record->photos()
            ->whereIn('file_path', array_diff($this->originalPhotoPaths, $kept))
            ->delete();
    }
}
