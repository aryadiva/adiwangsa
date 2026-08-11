<?php

namespace App\Filament\Resources;

use App\Enums\DailyReportStatus;
use App\Enums\UserRole;
use App\Enums\WeatherCondition;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Services\DailyReportPhotoService;
use App\Services\PdfDocumentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;

class DailyReportResource extends Resource
{
    protected static ?string $model = DailyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Daily Reports';

    protected static ?string $navigationGroup = 'Site Activity';

    public static function scopedQuery(): Builder
    {
        /** @var Builder<DailyReport> $query */
        $query = DailyReport::query();
        $user = auth()->user();

        if ($user === null) {
            return $query;
        }

        return match ($user->role) {
            UserRole::Admin => $query,
            UserRole::SiteEngineer => $query->forSiteEngineer($user),
            UserRole::Client => $query->forClient($user),
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopedQuery();
    }

    public static function scopedSiteQuery(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->role === UserRole::SiteEngineer) {
            $query->whereHas(
                'project.engineers',
                fn (Builder $q) => $q->whereKey($user->id)
            );
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('site_id')
                    ->label('Site')
                    ->relationship(
                        'site',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => static::scopedSiteQuery($query)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('report_date')
                    ->required()
                    ->native(false)
                    ->displayFormat('Y-m-d'),
                Forms\Components\Select::make('weather_condition')
                    ->options(WeatherCondition::class)
                    ->required(),
                Forms\Components\Textarea::make('work_summary')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('delays_or_issues')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('workerAllocations')
                    ->relationship()
                    ->label('Worker Allocations')
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => null)
                    ->schema([
                        Forms\Components\Select::make('worker_id')
                            ->label('Worker')
                            ->relationship('worker', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('hours_worked')
                            ->label('Hours Worked')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(24)
                            ->default(8)
                            ->required(),
                        Forms\Components\TextInput::make('remarks')
                            ->label('Remarks'),
                    ])
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Site Photos')
                    ->multiple()
                    ->image()
                    ->disk('photos')
                    ->directory('daily-report-photos')
                    ->maxSize(10240)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->storeFileNamesIn('file_names')
                    ->saveUploadedFileUsing(fn (UploadedFile $file): string => app(DailyReportPhotoService::class)->store($file))
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('meta_data')
                    ->label('Additional Fields')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('admin_notes')
                    ->rows(3)
                    ->visible(fn () => auth()->user()?->role === UserRole::Admin)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('site.project.name')
                    ->label('Project')
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weather_condition')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (DailyReportStatus $state): string => match ($state) {
                        DailyReportStatus::Draft => 'gray',
                        DailyReportStatus::NeedApproval => 'warning',
                        DailyReportStatus::Published => 'success',
                        DailyReportStatus::RevisionRequested => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_by.name')
                    ->label('Created By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_summary')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->defaultSort('report_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn (): array => collect(DailyReportStatus::cases())
                        ->mapWithKeys(fn (DailyReportStatus $status): array => [
                            $status->value => $status === DailyReportStatus::NeedApproval
                                && auth()->user()?->role === UserRole::Admin
                                ? 'Need Approval ('.static::needApprovalCount().')'
                                : str($status->value)->headline()->toString(),
                        ])
                        ->all()),
                Tables\Filters\Filter::make('report_date_range')
                    ->label('Report Date')
                    ->columns(2)
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('report_date', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('report_date', '<=', $date)
                            );
                    }),
                Tables\Filters\SelectFilter::make('site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_activity_log')
                    ->label('View Activity Log')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->visible(fn (?DailyReport $record): bool => $record !== null
                        && auth()->user()?->role === UserRole::Admin)
                    ->modalHeading(fn (DailyReport $record): string => "Activity Log — {$record->site->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (DailyReport $record): View {
                        $activities = $record->activitiesAsSubject()
                            ->with('causer')
                            ->latest()
                            ->get();

                        return view('filament.activity-log', [
                            'activities' => $activities,
                            'timezone' => $record->site->project->timezone ?: 'UTC',
                        ]);
                    }),
                Tables\Actions\Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->visible(fn (DailyReport $record): bool => auth()->user()?->role === UserRole::Admin
                        && $record->status === DailyReportStatus::Published)
                    ->action(function (DailyReport $record): void {
                        $queued = app(PdfDocumentService::class)->queueDaily($record, auth()->id());

                        Notification::make()
                            ->title($queued
                                ? 'Daily progress PDF queued for generation.'
                                : 'A PDF already exists — download link sent.')
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

    public static function needApprovalCount(): int
    {
        return static::scopedQuery()
            ->where('status', DailyReportStatus::NeedApproval)
            ->count();
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->role !== UserRole::Admin) {
            return null;
        }

        return (string) static::needApprovalCount();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return auth()->user()?->role === UserRole::Admin ? 'warning' : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyReports::route('/'),
            'create' => Pages\CreateDailyReport::route('/create'),
            'edit' => Pages\EditDailyReport::route('/{record}/edit'),
        ];
    }
}
