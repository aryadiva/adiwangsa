<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkerResource\Pages;
use App\Models\Worker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkerResource extends Resource
{
    protected static ?string $model = Worker::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('app.nav.workers');
    }

    public static function getNavigationGroup(): string
    {
        return __('app.nav.group_operations');
    }

    public static function getModelLabel(): string
    {
        return __('app.nav.worker');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.nav.workers');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label(__('app.worker.full_name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('trade_skill')
                    ->label(__('app.worker.trade_skill'))
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('daily_rate')
                    ->label(__('app.worker.daily_rate'))
                    ->numeric()
                    ->minValue(0),
                Forms\Components\DatePicker::make('active_start_date')
                    ->label(__('app.worker.active_from')),
                Forms\Components\DatePicker::make('deactivation_date')
                    ->label(__('app.worker.deactivation_date')),
                Forms\Components\TextInput::make('phone_number')
                    ->label(__('app.worker.phone'))
                    ->tel()
                    ->maxLength(32),
                Forms\Components\Fieldset::make(__('app.worker.bank_account'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label(__('app.worker.account_number'))
                            ->maxLength(64),
                        Forms\Components\TextInput::make('bank_account_name')
                            ->label(__('app.worker.account_name'))
                            ->maxLength(255),
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('app.common.is_active'))
                    ->inline(false)
                    ->default(true),
                Forms\Components\KeyValue::make('meta_data')
                    ->label(__('app.common.additional_fields'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('app.worker.full_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trade_skill')
                    ->label(__('app.worker.trade_skill'))
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_rate')
                    ->label(__('app.worker.daily_rate'))
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label(__('app.worker.column_phone'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('active_start_date')
                    ->label(__('app.worker.column_active_from'))
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deactivation_date')
                    ->label(__('app.worker.deactivation_date'))
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('app.worker.column_active')),
                Tables\Columns\TextColumn::make('report_allocations_count')
                    ->counts('reportAllocations')
                    ->label(__('app.worker.column_allocations')),
            ])
            ->defaultSort('full_name')
            ->filters([
                Tables\Filters\SelectFilter::make('trade_skill')
                    ->label(__('app.worker.trade_skill'))
                    ->options(fn (): array => Worker::query()
                        ->whereNotNull('trade_skill')
                        ->distinct()
                        ->pluck('trade_skill', 'trade_skill')
                        ->all()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('app.worker.filter_active')),
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
            'index' => Pages\ListWorkers::route('/'),
            'create' => Pages\CreateWorker::route('/create'),
            'edit' => Pages\EditWorker::route('/{record}/edit'),
        ];
    }
}
