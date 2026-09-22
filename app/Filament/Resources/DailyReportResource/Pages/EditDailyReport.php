<?php

namespace App\Filament\Resources\DailyReportResource\Pages;

use App\Enums\DailyReportStatus;
use App\Enums\UserRole;
use App\Filament\Components\LiveCapture;
use App\Filament\Resources\DailyReportResource;
use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use App\Services\DailyReportPhotoService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class EditDailyReport extends EditRecord
{
    protected static string $resource = DailyReportResource::class;

    protected static string $view = 'filament.pages.edit-daily-report';

    /** @var array<string, mixed> */
    protected array $lastDraftState = [];

    /** @var array<string, mixed> */
    protected array $photoState = [];

    public ?string $draftLastSavedAt = null;

    public bool $draftSaveFailed = false;

    public bool $draftSaveInProgress = false;

    /**
     * Paths of the persisted before/after pair whose files are missing from storage.
     *
     * @var list<string>
     */
    public array $missingPhotoPaths = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('submitForApproval')
                ->label(__('app.daily_report.submit_for_approval'))
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->isEditable() && $this->currentStatus() === DailyReportStatus::Draft)
                ->action(function (): void {
                    if ($report = $this->report()) {
                        /** @var DailyReportPhoto|null $photo */
                        $photo = $report->photos()->first();

                        if ($photo === null || ! filled($photo->before_file_path) || ! filled($photo->after_file_path)) {
                            Notification::make()
                                ->title(__('app.daily_report.photo_pair_required'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $report->submitForApproval();
                        $this->refreshForm();
                        Notification::make()->title(__('app.daily_report.report_submitted'))->success()->send();
                    }
                }),
            Actions\Action::make('approveAndPublish')
                ->label(__('app.daily_report.approve_and_publish'))
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->isAdmin() && $this->currentStatus() === DailyReportStatus::NeedApproval)
                ->action(function (): void {
                    if ($report = $this->report()) {
                        $report->approveAndPublish(auth()->id());
                        $this->refreshForm();
                        Notification::make()->title(__('app.daily_report.report_published'))->success()->send();
                    }
                }),
            Actions\Action::make('requestRevision')
                ->label(__('app.daily_report.request_revision'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->isAdmin() && $this->currentStatus() === DailyReportStatus::NeedApproval)
                ->form([
                    Forms\Components\Textarea::make('admin_notes')
                        ->label(__('app.daily_report.revision_feedback'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    if ($report = $this->report()) {
                        $report->requestRevision($data['admin_notes']);
                        $this->refreshForm();
                        Notification::make()->title(__('app.daily_report.revision_requested'))->warning()->send();
                    }
                }),
            Actions\Action::make('resubmitForApproval')
                ->label(__('app.daily_report.resubmit_for_approval'))
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->isEditable() && $this->currentStatus() === DailyReportStatus::RevisionRequested)
                ->action(function (): void {
                    if ($report = $this->report()) {
                        $report->resubmitForApproval(auth()->id());
                        $this->refreshForm();
                        Notification::make()->title(__('app.daily_report.report_resubmitted'))->success()->send();
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
            /** @var DailyReportPhoto|null $photo */
            $photo = $record->photos()->first();

            $data['before_photo'] = $photo?->before_file_path;
            $data['after_photo'] = $photo?->after_file_path;
            $data['before_file_path'] = $photo?->before_file_path;
            $data['after_file_path'] = $photo?->after_file_path;
            $data['photo_description'] = $photo?->description;

            $this->missingPhotoPaths = collect([
                $photo?->before_file_path,
                $photo?->after_file_path,
            ])
                ->filter()
                ->filter(fn (string $path): bool => ! Storage::disk('photos')->exists($path))
                ->values()
                ->all();
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
                'data' => __('app.daily_report.locked'),
            ]);
        }

        CreateDailyReport::assertUniqueSiteDate($data, $this->record);

        $this->photoState = Arr::only($data, [
            'before_photo',
            'after_photo',
            'before_file_path',
            'after_file_path',
            'photo_description',
        ]);

        return Arr::except($data, array_keys($this->photoState));
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof DailyReport) {
            return;
        }

        $pair = $this->resolvePhotoPair();

        if ($pair === null) {
            return;
        }

        $existing = $this->record->photos()->first();

        if ($existing === null) {
            // One pair per report — created once, updated in place afterwards.
            $this->record->photos()->create($pair);

            return;
        }

        $existing->fill($pair)->save();

        $this->syncMissingPhotoPaths([$pair['before_file_path'], $pair['after_file_path']]);
    }

    /**
     * Resolves the pair row payload from the captured form state (live
     * camera captures or Admin device uploads). Preserves the previously
     * stored paths for a side whose state was untouched.
     *
     * @return array<string, mixed>|null
     */
    protected function resolvePhotoPair(): ?array
    {
        $service = app(DailyReportPhotoService::class);

        /** @var DailyReportPhoto|null $existing */
        $existing = $this->record instanceof DailyReport ? $this->record->photos()->first() : null;

        $before = LiveCapture::resolveState($this->photoState['before_photo'] ?? $this->photoState['before_file_path'] ?? null, $service::DIRECTORY)
            ?? ($existing !== null ? [
                'path' => $existing->before_file_path,
                'thumbnail_path' => $existing->before_thumbnail_path,
                'file_size_bytes' => 0,
                'captured_at' => $existing->captured_at ?? now(),
            ] : null);

        $after = LiveCapture::resolveState($this->photoState['after_photo'] ?? $this->photoState['after_file_path'] ?? null, $service::DIRECTORY)
            ?? ($existing !== null ? [
                'path' => $existing->after_file_path,
                'thumbnail_path' => $existing->after_thumbnail_path,
                'file_size_bytes' => 0,
                'captured_at' => $existing->captured_at ?? now(),
            ] : null);

        if ($before === null || $after === null) {
            return null;
        }

        return [
            'before_file_path' => $before['path'],
            'before_thumbnail_path' => $before['thumbnail_path'] ?? $service->thumbnailPathFor($before['path']),
            'after_file_path' => $after['path'],
            'after_thumbnail_path' => $after['thumbnail_path'] ?? $service->thumbnailPathFor($after['path']),
            'description' => $this->photoState['photo_description'] ?? $existing?->description,
            'captured_at' => max($before['captured_at'], $after['captured_at']),
        ];
    }

    protected function syncMissingPhotoPaths(array $paths): void
    {
        $disk = Storage::disk('photos');

        $this->missingPhotoPaths = collect($paths)
            ->filter()
            ->filter(fn (string $path): bool => ! $disk->exists($path))
            ->values()
            ->all();
    }
}
