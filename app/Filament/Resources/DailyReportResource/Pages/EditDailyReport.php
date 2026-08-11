<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Enums\DailyReportStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
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
            Actions\Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->isEditable() && $this->currentStatus() === DailyReportStatus::Draft)
                ->action(function (): void {
                    if ($report = $this->report()) {
                        $report->submitForApproval();
                        $this->refreshForm();
                        Notification::make()->title('Report submitted for approval')->success()->send();
                    }
                }),
            Actions\Action::make('approveAndPublish')
                ->label('Approve & Publish')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->isAdmin() && $this->currentStatus() === DailyReportStatus::NeedApproval)
                ->action(function (): void {
                    if ($report = $this->report()) {
                        $report->approveAndPublish(auth()->id());
                        $this->refreshForm();
                        Notification::make()->title('Report published')->success()->send();
                    }
                }),
            Actions\Action::make('requestRevision')
                ->label('Request Revision')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->isAdmin() && $this->currentStatus() === DailyReportStatus::NeedApproval)
                ->form([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Revision feedback')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if ($report = $this->report()) {
                        $report->requestRevision($data['admin_notes']);
                        $this->refreshForm();
                        Notification::make()->title('Revision requested')->warning()->send();
                    }
                }),
            Actions\Action::make('resubmitForApproval')
                ->label('Resubmit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->isEditable() && $this->currentStatus() === DailyReportStatus::RevisionRequested)
                ->action(function (): void {
                    if ($report = $this->report()) {
                        $report->resubmitForApproval(auth()->id());
                        $this->refreshForm();
                        Notification::make()->title('Report resubmitted for approval')->success()->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function report(): ?DailyReport
    {
        return $this->record instanceof DailyReport ? $this->record : null;
    }

    protected function currentStatus(): ?DailyReportStatus
    {
        return $this->report()?->status;
    }

    protected function getFormActions(): array
    {
        return collect(parent::getFormActions())
            ->filter(fn ($action): bool => $this->isEditable() || $action->getName() !== 'save')
            ->values()
            ->all();
    }

    protected function isEditable(): bool
    {
        return $this->record instanceof DailyReport
            && in_array($this->record->status, [
                DailyReportStatus::Draft,
                DailyReportStatus::RevisionRequested,
            ], true);
    }

    protected function isAdmin(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function refreshForm(): void
    {
        if ($this->record instanceof DailyReport) {
            $this->record = $this->record->fresh();
        }

        $this->fillForm();
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

        if (! $this->isEditable()) {
            throw ValidationException::withMessages([
                'data' => 'This report is locked and cannot be edited in its current state.',
            ]);
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
