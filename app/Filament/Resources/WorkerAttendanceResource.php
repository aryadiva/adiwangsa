<?php

namespace App\Filament\Resources;

use App\Filament\Components\LiveCapture;
use App\Filament\Resources\WorkerAttendanceResource\Pages;
use App\Models\WorkerAttendance;
use App\Rules\LiveCapture as LiveCaptureRule;
use App\Services\PhotoCaptureService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WorkerAttendanceResource extends Resource
{
    protected static ?string $model = WorkerAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Worker Attendance';

    protected static ?string $navigationGroup = 'Human Resources';

    public static function getEloquentQuery(): Builder
    {
        // HRD manages all records (confirmed scope); Admin full. SE/Client
        // are policy-denied and never reach this resource.
        return parent::getEloquentQuery()
            ->with(['worker', 'site', 'recordedBy']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('worker_id')
                    ->label('Worker')
                    ->relationship('worker', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('site_id')
                    ->label('Site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('attendance_date')
                    ->label('Attendance Date')
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->maxDate(now()->toDateString())
                    ->default(now()->toDateString())
                    ->required(),
                Forms\Components\TextInput::make('hours_worked')
                    ->label('Hours Worked')
                    ->numeric()
                    ->step(0.5)
                    ->minValue(0)
                    ->maxValue(24)
                    ->default(8)
                    ->required(),
                Forms\Components\TextInput::make('overtime_hours')
                    ->label('Overtime Hours')
                    ->numeric()
                    ->step(0.5)
                    ->minValue(0)
                    ->maxValue(24)
                    ->default(0)
                    ->required(),
                Forms\Components\Section::make('Attendance Photo')
                    ->description('Captured live with the camera — one photo per worker per day.')
                    ->schema([
                        LiveCapture::make('attendance_photo')
                            ->label('Attendance Photo')
                            ->captureDirectory(PhotoCaptureService::ATTENDANCE_DIRECTORY)
                            ->rules([new LiveCaptureRule])
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worker.full_name')
                    ->label('Worker')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Site')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hours_worked')
                    ->label('Hours'),
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('Overtime'),
                Tables\Columns\IconColumn::make('photo_file_path')
                    ->label('Photo')
                    ->boolean()
                    ->getStateUsing(fn (WorkerAttendance $record): bool => filled($record->photo_file_path)),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded By'),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('site')
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('attendance_date_range')
                    ->label('Attendance Date')
                    ->columns(2)
                    ->form([
                        Forms\Components\DatePicker::make('from')->native(false),
                        Forms\Components\DatePicker::make('until')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('attendance_date', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('attendance_date', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkerAttendance::route('/'),
            'create' => Pages\CreateWorkerAttendance::route('/create'),
            'edit' => Pages\EditWorkerAttendance::route('/{record}/edit'),
        ];
    }
}
