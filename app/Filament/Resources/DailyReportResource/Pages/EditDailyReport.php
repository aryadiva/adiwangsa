<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Enums\DailyReportStatus;
use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Throwable;

class EditDailyReport extends EditRecord
{
    protected static string $resource = DailyReportResource::class;

    protected static string $view = 'filament.pages.edit-daily-report';

    /** @var list<string> */
    protected array $originalPhotoPaths = [];

    /** @var array<string, mixed> */
    protected array $lastDraftState = [];

    public ?string $draftLastSavedAt = null;

    public bool $draftSaveFailed = false;

    public bool $draftSaveInProgress = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function shouldAutoSave(): bool
    {
        return $this->record instanceof DailyReport
            && $this->record->status === DailyReportStatus::Draft;
    }

    public function saveDraft(): void
    {
        if (! $this->shouldAutoSave() || $this->draftSaveInProgress) {
            return;
        }

        $data = $this->form->getState();

        if ($data === $this->lastDraftState) {
            return;
        }

        $this->draftSaveInProgress = true;

        try {
            if (! $this->record instanceof DailyReport) {
                return;
            }

            $this->record->update(Arr::only($data, $this->record->getFillable()));

            $this->lastDraftState = $data;
            $this->draftSaveFailed = false;
            $this->draftLastSavedAt = now()->format('H:i:s');

            $this->dispatch('draft-auto-saved', savedAt: $this->draftLastSavedAt);
        } catch (Throwable) {
            $this->draftSaveFailed = true;
            $this->dispatch('draft-auto-save-failed');
        } finally {
            $this->draftSaveInProgress = false;
        }
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
