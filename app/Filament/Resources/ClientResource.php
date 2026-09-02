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

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Linked User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Optional — the user account that represents this client.'),
                Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_person')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(50),
                Forms\Components\Section::make('Report Email Delivery Defaults')
                    ->description('Used when emailing published Daily Site Progress Summary PDFs (Sender / Receivers / CC). Per-send overrides win over these defaults.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('meta_data.email_delivery.sender_email')
                            ->label('Sender Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('meta_data.email_delivery.sender_name')
                            ->label('Sender Name')
                            ->maxLength(255),
                        Forms\Components\TagsInput::make('meta_data.email_delivery.receivers')
                            ->label('Receivers')
                            ->helperText('Defaults to the client email above when empty.')
                            ->splitKeys([',', ' '])
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('meta_data.email_delivery.cc')
                            ->label('CC')
                            ->splitKeys([',', ' '])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('meta_data')
                    ->label('Additional Fields')
                    ->helperText('Email delivery defaults are stored under the "email_delivery" key — edit through the section above.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('projects_count')
                    ->counts('projects')
                    ->label('Projects'),
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
