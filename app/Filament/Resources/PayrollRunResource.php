<?php

namespace App\Filament\Resources;

use App\Enums\PayrollRunStatus;
use App\Filament\Resources\PayrollRunResource\Pages;
use App\Filament\Resources\PayrollRunResource\RelationManagers\PayrollItemsRelationManager;
use App\Models\PayrollRun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayrollRunResource extends Resource
{
    protected static ?string $model = PayrollRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('app.nav.payroll_runs');
    }

    public static function getNavigationGroup(): string
    {
        return __('app.nav.group_finance');
    }

    public static function getModelLabel(): string
    {
        return __('app.nav.payroll_run');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.nav.payroll_runs');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('period_start')
                ->label(__('app.payroll.period_start'))
                ->disabled(),
            DatePicker::make('period_end')
                ->label(__('app.payroll.period_end'))
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('period_start', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('period_start')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_end')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.common.status'))
                    ->badge()
                    ->color(fn (PayrollRunStatus $state): string => match ($state) {
                        PayrollRunStatus::Draft => 'gray',
                        PayrollRunStatus::PendingReview => 'warning',
                        PayrollRunStatus::Approved => 'success',
                        PayrollRunStatus::Paid => 'info',
                    })
                    ->formatStateUsing(fn (PayrollRunStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label(__('app.payroll.column_workers')),
                Tables\Columns\TextColumn::make('items_sum_total_pay')
                    ->sum('items', 'total_pay')
                    ->label(__('app.payroll.column_total_pay'))
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('generatedBy.name')
                    ->label(__('app.common.generated_by'))
                    ->placeholder(__('app.common.system'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label(__('app.common.approved_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.common.status'))
                    ->options(PayrollRunStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('submit_for_review')
                    ->label(__('app.payroll.submit_for_review'))
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (PayrollRun $record): bool => auth()->user()?->can('submitForReview', $record) ?? false)
                    ->action(function (PayrollRun $record): void {
                        $record->submitForReview(auth()->user());

                        Notification::make()->title(__('app.payroll.submitted_for_review'))->success()->send();
                    }),
                Tables\Actions\Action::make('approve')
                    ->label(__('app.payroll.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PayrollRun $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->action(function (PayrollRun $record): void {
                        $record->approve(auth()->user());

                        Notification::make()->title(__('app.payroll.approved'))->success()->send();
                    }),
                Tables\Actions\Action::make('mark_paid')
                    ->label(__('app.payroll.mark_paid'))
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (PayrollRun $record): bool => auth()->user()?->can('markPaid', $record) ?? false)
                    ->action(function (PayrollRun $record): void {
                        $record->markPaid(auth()->user());

                        Notification::make()->title(__('app.payroll.marked_paid'))->success()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            PayrollItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollRuns::route('/'),
            'view' => Pages\ViewPayrollRun::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<PayrollRun> $query */
        $query = parent::getEloquentQuery();

        return $query->with(['generatedBy', 'approvedBy']);
    }
}
