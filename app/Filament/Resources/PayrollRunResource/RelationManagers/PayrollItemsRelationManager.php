<?php

namespace App\Filament\Resources\PayrollRunResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PayrollItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.payroll.items_title');
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worker.full_name')
                    ->label(__('app.payroll.worker'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('worker.trade_skill')
                    ->label(__('app.payroll.trade'))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('regular_hours_total')
                    ->label(__('app.payroll.regular_hrs'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_hours_total')
                    ->label(__('app.payroll.overtime_hrs'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('regular_pay')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_pay')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_pay')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
