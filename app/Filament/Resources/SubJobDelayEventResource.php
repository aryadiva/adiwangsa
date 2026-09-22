<?php

namespace App\Filament\Resources;

use App\Enums\DelayEventStatus;
use App\Enums\UserRole;
use App\Filament\Resources\SubJobDelayEventResource\Pages;
use App\Models\SubJobDelayEvent;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubJobDelayEventResource extends Resource
{
    protected static ?string $model = SubJobDelayEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('app.nav.sub_job_delays');
    }

    public static function getNavigationGroup(): string
    {
        return __('app.nav.group_operations');
    }

    public static function getModelLabel(): string
    {
        return __('app.nav.sub_job_delay_event');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.nav.sub_job_delays');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<SubJobDelayEvent> $query */
        $query = parent::getEloquentQuery()->with(['subJob.projectMilestone.project', 'mitigationSubmittedBy']);
        $user = auth()->user();

        if ($user === null || $user->role === UserRole::Admin) {
            return $query;
        }

        if ($user->role === UserRole::SiteEngineer) {
            return $query->whereHas('subJob.projectMilestone.project.engineers', fn (Builder $e) => $e->whereKey($user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('triggered_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('subJob.title')
                    ->label(__('app.delay_event.sub_job'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subJob.projectMilestone.project.name')
                    ->label(__('app.common.project'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.common.status'))
                    ->badge()
                    ->color(fn (DelayEventStatus $state): string => match ($state) {
                        DelayEventStatus::Red => 'danger',
                        DelayEventStatus::Yellow => 'warning',
                        DelayEventStatus::Green => 'success',
                    })
                    ->formatStateUsing(fn (DelayEventStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('delay_days')
                    ->label(__('app.delay_event.delay_days'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('triggered_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mitigation_plan')
                    ->label(__('app.delay_event.mitigation_plan'))
                    ->limit(50)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('mitigationSubmittedBy.name')
                    ->label(__('app.delay_event.mitigation_by'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.common.status'))
                    ->options(DelayEventStatus::class),
            ])
            ->actions([
                Tables\Actions\Action::make('submit_mitigation_plan')
                    ->label(__('app.delay_event.action_submit_mitigation'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn (SubJobDelayEvent $record): bool => auth()->user()?->role === UserRole::Admin
                        && $record->status === DelayEventStatus::Red)
                    ->form([
                        Textarea::make('mitigation_plan')
                            ->label(__('app.delay_event.mitigation_plan'))
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->action(function (SubJobDelayEvent $record, array $data): void {
                        $record->submitMitigationPlan($data['mitigation_plan'], auth()->user());

                        Notification::make()
                            ->title(__('app.delay_event.mitigation_submitted'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mark_recovered')
                    ->label(__('app.delay_event.action_mark_recovered'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('app.delay_event.confirm_recovery'))
                    ->modalDescription(__('app.delay_event.recovery_description'))
                    ->visible(fn (SubJobDelayEvent $record): bool => auth()->user()?->role === UserRole::Admin
                        && $record->status === DelayEventStatus::Yellow)
                    ->action(function (SubJobDelayEvent $record): void {
                        $record->markRecovered(auth()->user());

                        Notification::make()
                            ->title(__('app.delay_event.recovered'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubJobDelayEvents::route('/'),
        ];
    }
}
