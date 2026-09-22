<?php

namespace App\Filament\Resources\PayrollRunResource\Pages;

use App\Filament\Resources\PayrollRunResource;
use App\Models\PayrollRun;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property PayrollRun $record
 */
class ViewPayrollRun extends ViewRecord
{
    protected static string $resource = PayrollRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit_for_review')
                ->label(__('app.payroll.submit_for_review'))
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()?->can('submitForReview', $this->record) ?? false)
                ->action(function (): void {
                    $this->record->submitForReview(auth()->user());

                    Notification::make()->title(__('app.payroll.submitted_for_review'))->success()->send();
                }),
            Action::make('approve')
                ->label(__('app.payroll.approve'))
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()?->can('approve', $this->record) ?? false)
                ->action(function (): void {
                    $this->record->approve(auth()->user());

                    Notification::make()->title(__('app.payroll.approved'))->success()->send();
                }),
            Action::make('mark_paid')
                ->label(__('app.payroll.mark_paid'))
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => auth()->user()?->can('markPaid', $this->record) ?? false)
                ->action(function (): void {
                    $this->record->markPaid(auth()->user());

                    Notification::make()->title(__('app.payroll.marked_paid'))->success()->send();
                }),
        ];
    }
}
