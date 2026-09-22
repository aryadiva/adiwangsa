<?php

namespace App\Filament\Resources;

use App\Enums\DailyReportStatus;
use App\Enums\ReportShift;
use App\Enums\UserRole;
use App\Enums\WeatherCondition;
use App\Filament\Components\LiveCapture;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use App\Models\Site;
use App\Rules\LiveCapture as LiveCaptureRule;
use App\Services\DailyReportPhotoService;
use App\Services\DeficitCarryForwardService;
use App\Services\PdfDocumentService;
use App\Services\PhotoCaptureService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
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

    public static function getNavigationLabel(): string
    {
        return __('app.nav.daily_reports');
    }

    public static function getNavigationGroup(): string
    {
        return __('app.nav.group_site_activity');
    }

    public static function getModelLabel(): string
    {
        return __('app.nav.daily_report');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.nav.daily_reports');
    }

    public static function scopedQuery(): Builder
    {
        /** @var Builder<DailyReport> $query */
        $query = DailyReport::query()
            ->with(['site.project', 'createdBy']);
        $user = auth()->user();

        if ($user === null) {
            return $query;
        }

        return match ($user->role) {
            UserRole::Admin => $query,
            UserRole::SiteEngineer => $query->forSiteEngineer($user),
            UserRole::Client => $query->forClient($user),
            UserRole::Hrd => $query->whereRaw('1 = 0'),
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
                    ->label(__('app.daily_report.site'))
                    ->relationship(
                        'site',
                        'name',
                        modifyQueryUsing: fn (Builder $query): Builder => static::scopedSiteQuery($query)
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set): void {
                        $set('milestone_sub_job_id', null);
                    }),
                Forms\Components\DatePicker::make('report_date')
                    ->label(__('app.daily_report.report_date'))
                    ->required()
                    ->native(false)
                    ->displayFormat('Y-m-d'),
                Forms\Components\Select::make('shift')
                    ->label(__('app.daily_report.shift'))
                    ->options(ReportShift::class)
                    ->default(ReportShift::Shift1)
                    ->required(),
                Forms\Components\Select::make('milestone_sub_job_id')
                    ->label(__('app.daily_report.sub_job'))
                    ->options(function (callable $get) {
                        $siteId = $get('site_id');

                        if ($siteId === null) {
                            return [];
                        }

                        $site = Site::query()->with('project.milestones.subJobs')->find($siteId);

                        if ($site === null) {
                            return [];
                        }

                        return $site->project->milestones
                            ->flatMap(fn ($m) => $m->subJobs->mapWithKeys(fn ($sj) => [$sj->id => "{$m->title}: {$sj->title}"]))
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set): void {
                        $siteId = $get('site_id');
                        $subJobId = $get('milestone_sub_job_id');

                        if ($siteId !== null && $subJobId !== null) {
                            $site = Site::query()->with('project.milestones.subJobs')->find($siteId);

                            if ($site !== null) {
                                $subJob = $site->project->milestones
                                    ->flatMap(fn ($m) => $m->subJobs)
                                    ->firstWhere('id', $subJobId);

                                if ($subJob !== null) {
                                    $baseline = DeficitCarryForwardService::computeDailyTarget(
                                        $subJob,
                                        $get('report_date') ? Carbon::parse($get('report_date')) : now(),
                                    );
                                    $set('daily_target', $baseline);
                                }
                            }
                        }
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('daily_target')
                    ->label(__('app.daily_report.daily_target'))
                    ->disabled()
                    ->dehydrated(false)
                    ->suffix(__('app.daily_report.daily_target_suffix'))
                    ->columnSpanFull(),
                Forms\Components\Select::make('weather_condition')
                    ->label(__('app.daily_report.weather'))
                    ->options(WeatherCondition::class)
                    ->required(),
                Forms\Components\Textarea::make('work_summary')
                    ->label(__('app.daily_report.work_summary'))
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('delays_or_issues')
                    ->label(__('app.daily_report.delays_issues'))
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('daily_achievement')
                    ->label(__('app.daily_report.daily_achievement'))
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->suffix(__('app.daily_report.daily_target_suffix'))
                    ->helperText(__('app.daily_report.achievement_helper')),
                Forms\Components\Textarea::make('delay_reason')
                    ->label(__('app.daily_report.delay_reason'))
                    ->rows(2)
                    ->helperText(__('app.daily_report.delay_reason_helper'))
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('workerAllocations')
                    ->relationship()
                    ->label(__('app.daily_report.worker_allocations'))
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => null)
                    ->schema([
                        Forms\Components\Select::make('worker_id')
                            ->label(__('app.common.worker'))
                            ->relationship('worker', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('hours_worked')
                            ->label(__('app.common.hours_worked'))
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(24)
                            ->default(8)
                            ->required(),
                        Forms\Components\TextInput::make('remarks')
                            ->label(__('app.common.remarks')),
                    ])
                    ->columnSpanFull(),
                Forms\Components\Section::make(__('app.daily_report.photos_section'))
                    ->description(__('app.daily_report.photos_section_description'))
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                LiveCapture::make('before_photo')
                                    ->label(__('app.daily_report.before'))
                                    ->captureDirectory(DailyReportPhotoService::DIRECTORY)
                                    ->rules([new LiveCaptureRule])
                                    ->visible(fn (): bool => auth()->user()?->role !== UserRole::Admin),
                                FileUpload::make('before_file_path')
                                    ->label(__('app.daily_report.before'))
                                    ->image()
                                    ->disk('photos')
                                    ->visibility('private')
                                    ->directory(DailyReportPhotoService::DIRECTORY)
                                    ->maxSize(10240)
                                    ->acceptedFileTypes(PhotoCaptureService::ALLOWED_MIMES)
                                    ->saveUploadedFileUsing(fn (UploadedFile $file): string => app(DailyReportPhotoService::class)->store($file))
                                    ->visible(fn (): bool => auth()->user()?->role === UserRole::Admin),
                                LiveCapture::make('after_photo')
                                    ->label(__('app.daily_report.after'))
                                    ->captureDirectory(DailyReportPhotoService::DIRECTORY)
                                    ->rules([new LiveCaptureRule])
                                    ->visible(fn (): bool => auth()->user()?->role !== UserRole::Admin),
                                FileUpload::make('after_file_path')
                                    ->label(__('app.daily_report.after'))
                                    ->image()
                                    ->disk('photos')
                                    ->visibility('private')
                                    ->directory(DailyReportPhotoService::DIRECTORY)
                                    ->maxSize(10240)
                                    ->acceptedFileTypes(PhotoCaptureService::ALLOWED_MIMES)
                                    ->saveUploadedFileUsing(fn (UploadedFile $file): string => app(DailyReportPhotoService::class)->store($file))
                                    ->visible(fn (): bool => auth()->user()?->role === UserRole::Admin),
                                Forms\Components\Textarea::make('photo_description')
                                    ->label(__('app.daily_report.photo_description'))
                                    ->rows(4),
                            ]),
                    ])
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('meta_data')
                    ->label(__('app.common.additional_fields'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('admin_notes')
                    ->label(__('app.daily_report.admin_notes'))
                    ->visible(fn () => auth()->user()?->role === UserRole::Admin)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label(__('app.daily_report.site'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('site.project.name')
                    ->label(__('app.daily_report.column_project'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->label(__('app.daily_report.report_date'))
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shift')
                    ->label(__('app.daily_report.shift'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weather_condition')
                    ->label(__('app.daily_report.weather'))
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.common.status'))
                    ->badge()
                    ->color(fn (DailyReportStatus $state): string => match ($state) {
                        DailyReportStatus::Draft => 'gray',
                        DailyReportStatus::NeedApproval => 'warning',
                        DailyReportStatus::Published => 'success',
                        DailyReportStatus::RevisionRequested => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_by.name')
                    ->label(__('app.daily_report.column_created_by'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('work_summary')
                    ->label(__('app.daily_report.column_work_summary'))
                    ->limit(50)
                    ->toggleable(),
            ])
            ->defaultSort('report_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.daily_report.filter_status'))
                    ->options(fn (): array => collect(DailyReportStatus::cases())
                        ->mapWithKeys(fn (DailyReportStatus $status): array => [
                            $status->value => $status === DailyReportStatus::NeedApproval
                            && auth()->user()?->role === UserRole::Admin
                                ? __('app.daily_report.need_approval_with_count', ['count' => static::needApprovalCount()])
                                : $status->getLabel(),
                        ])
                        ->all()),
                Tables\Filters\Filter::make('report_date_range')
                    ->label(__('app.daily_report.filter_report_date'))
                    ->columns(2)
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('app.common.from'))
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('app.common.until'))
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
                    ->label(__('app.daily_report.filter_site'))
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('view_activity_log')
                    ->label(__('app.daily_report.action_view_activity_log'))
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->visible(fn (?DailyReport $record): bool => $record !== null
                        && auth()->user()?->role === UserRole::Admin)
                    ->modalHeading(fn (DailyReport $record): string => __('app.daily_report.activity_log_heading', ['site' => $record->site->name]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('app.common.close'))
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
                    ->label(__('app.daily_report.action_generate_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->visible(fn (DailyReport $record): bool => auth()->user()?->role === UserRole::Admin
                        && $record->status === DailyReportStatus::Published)
                    ->action(function (DailyReport $record): void {
                        $queued = app(PdfDocumentService::class)->queueDaily($record, auth()->id());

                        Notification::make()
                            ->title($queued
                                ? __('app.daily_report.pdf_queued')
                                : __('app.daily_report.pdf_exists'))
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
