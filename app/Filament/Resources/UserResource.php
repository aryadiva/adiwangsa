<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options(UserRole::class)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->inline(false)
                    ->default(true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8)
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (UserRole $state): string => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::SiteEngineer => 'info',
                        UserRole::Client => 'success',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(UserRole::class),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
