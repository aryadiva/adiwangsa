<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\DailyReportResource\Pages;
use App\Models\DailyReport;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyReportResource extends Resource
{
    protected static ?string $model = DailyReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<DailyReport> $query */
        $query = parent::getEloquentQuery();
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

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table;
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
