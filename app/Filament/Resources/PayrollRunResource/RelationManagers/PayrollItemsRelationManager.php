<?php

namespace App\Filament\Resources\PayrollRunResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PayrollItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Payroll Items';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('worker.full_name')
                    ->label('Worker')
                    ->searchable(),
                Tables\Columns\TextColumn::make('worker.trade_skill')
                    ->label('Trade')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('regular_hours_total')
                    ->label('Regular Hrs')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('overtime_hours_total')
                    ->label('Overtime Hrs')
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
