<?php

namespace App\Filament\Resources;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Projects';

    protected static ?string $navigationGroup = 'Operations';

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
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(50),
                Forms\Components\Select::make('status')
                    ->options(ProjectStatus::class)
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make('target_end_date')
                    ->native(false)
                    ->afterOrEqual('start_date'),
                Forms\Components\TextInput::make('budget')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\Select::make('timezone')
                    ->options(fn (): array => collect(\DateTimeZone::listIdentifiers())
                        ->filter(fn (string $tz): bool => str_starts_with($tz, 'UTC') || str_starts_with($tz, 'Asia/'))
                        ->values()
                        ->all())
                    ->default('UTC')
                    ->searchable(),
                Forms\Components\KeyValue::make('meta_data')
                    ->label('Additional Fields')
                    ->columnSpanFull(),
                Forms\Components\Select::make('engineers')
                    ->label('Assigned Site Engineers')
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
                    ->label('Client')
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
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sites_count')
                    ->counts('sites')
                    ->label('Sites'),
                Tables\Columns\TextColumn::make('engineers_count')
                    ->counts('engineers')
                    ->label('Engineers'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ProjectStatus::class),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'company_name')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
