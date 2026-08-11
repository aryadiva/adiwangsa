<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\ProjectMilestoneStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectMilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $title = 'Milestones';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('target_date')
                    ->native(false)
                    ->required(),
                Forms\Components\DatePicker::make('completed_at')
                    ->native(false)
                    ->afterOrEqual('target_date'),
                Forms\Components\Select::make('status')
                    ->options(ProjectMilestoneStatus::class)
                    ->default(ProjectMilestoneStatus::Pending)
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProjectMilestoneStatus $state): string => match ($state) {
                        ProjectMilestoneStatus::Pending => 'gray',
                        ProjectMilestoneStatus::InProgress => 'info',
                        ProjectMilestoneStatus::Completed => 'success',
                        ProjectMilestoneStatus::Delayed => 'warning',
                    }),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ProjectMilestoneStatus::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Project $project */
                        $project = $this->ownerRecord;

                        /** @var Builder<ProjectMilestone> $query */
                        $query = $project->milestones();

                        $data['sort_order'] = $data['sort_order']
                            ?? $query->withTrashed()->max('sort_order') + 1;

                        return $data;
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
}
