<?php

namespace App\Filament\Resources;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Services\PdfDocumentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function getNavigationLabel(): string
    {
        return __('app.nav.projects');
    }

    public static function getNavigationGroup(): string
    {
        return __('app.nav.group_operations');
    }

    public static function getModelLabel(): string
    {
        return __('app.nav.project');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.nav.projects');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if ($user === null) {
            return Project::query();
        }

        $query = Project::query();

        return match ($user->role) {
            UserRole::Admin => $query,
            UserRole::SiteEngineer => $query->whereHas('engineers', fn (Builder $q) => $q->whereKey($user->id)),
            UserRole::Client => $query->where('client_id', $user->client?->id),
            UserRole::Hrd => $query->whereRaw('1 = 0'),
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label(__('app.common.client'))
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label(__('app.project.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label(__('app.project.code'))
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(50),
                Forms\Components\Select::make('status')
                    ->label(__('app.project.status'))
                    ->options(ProjectStatus::class)
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('app.project.start_date'))
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make('target_end_date')
                    ->label(__('app.project.target_end_date'))
                    ->native(false)
                    ->afterOrEqual('start_date'),
                Forms\Components\TextInput::make('delay_threshold_days')
                    ->label(__('app.project.delay_threshold'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(2)
                    ->helperText(__('app.project.delay_threshold_helper')),
                Forms\Components\TextInput::make('budget')
                    ->label(__('app.project.budget'))
                    ->numeric()
                    ->minValue(0),
                Forms\Components\Select::make('timezone')
                    ->label(__('app.project.timezone'))
                    ->options(fn (): array => collect(\DateTimeZone::listIdentifiers())
                        ->filter(fn (string $tz): bool => str_starts_with($tz, 'UTC') || str_starts_with($tz, 'Asia/'))
                        ->values()
                        ->all())
                    ->default('UTC')
                    ->searchable(),
                Forms\Components\KeyValue::make('meta_data')
                    ->label(__('app.common.additional_fields'))
                    ->columnSpanFull(),
                Forms\Components\Select::make('engineers')
                    ->label(__('app.project.engineers'))
                    ->relationship('engineers', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.company_name')
                    ->label(__('app.project.column_client'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProjectStatus $state): string => match ($state) {
                        ProjectStatus::Planning => 'gray',
                        ProjectStatus::Active => 'info',
                        ProjectStatus::OnHold => 'warning',
                        ProjectStatus::Completed => 'success',
                    }),
                Tables\Columns\TextColumn::make('budget')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sites_count')
                    ->counts('sites')
                    ->label(__('app.project.column_sites')),
                Tables\Columns\TextColumn::make('engineers_count')
                    ->counts('engineers')
                    ->label(__('app.project.column_engineers')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.project.status'))
                    ->options(ProjectStatus::class),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label(__('app.project.filter_client'))
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('generate_weekly_digest')
                    ->label(__('app.project.action_weekly_digest'))
                    ->icon('heroicon-o-calendar-days')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('app.common.from'))
                            ->native(false)
                            ->default(now()->subDays(7))
                            ->required(),
                        Forms\Components\DatePicker::make('to')
                            ->label(__('app.common.until'))
                            ->native(false)
                            ->default(now())
                            ->afterOrEqual('from')
                            ->required(),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $queued = app(PdfDocumentService::class)->queueWeekly(
                            $record,
                            Carbon::parse($data['from']),
                            Carbon::parse($data['to']),
                            auth()->id(),
                        );

                        Notification::make()
                            ->title($queued
                                ? __('app.project.digest_queued')
                                : __('app.project.digest_exists'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('generate_attendance_roster')
                    ->label(__('app.project.action_attendance_roster'))
                    ->icon('heroicon-o-list-bullet')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('app.common.from'))
                            ->native(false)
                            ->default(now()->subDays(7))
                            ->required(),
                        Forms\Components\DatePicker::make('to')
                            ->label(__('app.common.until'))
                            ->native(false)
                            ->default(now())
                            ->afterOrEqual('from')
                            ->required(),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $queued = app(PdfDocumentService::class)->queueAttendance(
                            $record,
                            Carbon::parse($data['from']),
                            Carbon::parse($data['to']),
                            auth()->id(),
                        );

                        Notification::make()
                            ->title($queued
                                ? __('app.project.roster_queued')
                                : __('app.project.roster_exists'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProjectResource\RelationManagers\ProjectMilestonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
