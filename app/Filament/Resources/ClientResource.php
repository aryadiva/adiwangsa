<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationLabel(): string
    {
        return __('app.nav.clients');
    }

    public static function getNavigationGroup(): string
    {
        return __('app.nav.group_administration');
    }

    public static function getModelLabel(): string
    {
        return __('app.nav.client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.nav.clients');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(__('app.client.linked_user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText(__('app.client.linked_user_helper')),
                Forms\Components\TextInput::make('company_name')
                    ->label(__('app.client.company_name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_person')
                    ->label(__('app.client.contact_person'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label(__('app.common.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label(__('app.common.phone'))
                    ->tel()
                    ->required()
                    ->maxLength(50),
                Forms\Components\Section::make(__('app.client.email_delivery_section'))
                    ->description(__('app.client.email_delivery_description'))
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('meta_data.email_delivery.sender_email')
                            ->label(__('app.client.sender_email'))
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('meta_data.email_delivery.sender_name')
                            ->label(__('app.client.sender_name'))
                            ->maxLength(255),
                        Forms\Components\TagsInput::make('meta_data.email_delivery.receivers')
                            ->label(__('app.client.receivers'))
                            ->helperText(__('app.client.receivers_helper'))
                            ->splitKeys([',', ' '])
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('meta_data.email_delivery.cc')
                            ->label(__('app.client.cc'))
                            ->splitKeys([',', ' '])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('meta_data')
                    ->label(__('app.common.additional_fields'))
                    ->helperText(__('app.client.additional_fields_helper'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label(__('app.client.company_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label(__('app.client.contact_person'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('app.common.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('app.common.phone')),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label(__('app.client.column_projects')),
            ])
            ->defaultSort('company_name')
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
