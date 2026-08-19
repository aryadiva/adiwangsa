<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\MilestoneSubJobStatus;
use App\Enums\ProjectMilestoneStatus;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Rules\MilestoneStartDateRule;
use App\Rules\MilestoneWeightsTotalRule;
use App\Rules\SubJobsWeightsTotalRule;
use Filament\Forms;
use Filament\Forms\Components\Field;
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
        /** @var Project $project */
        $project = $this->ownerRecord;

        $startDate = Forms\Components\DatePicker::make('start_date')
            ->native(false)
            ->required()
            ->rules([
                new MilestoneStartDateRule($project->start_date?->toDateString()),
            ]);

        $weightField = Forms\Components\TextInput::make('weight_percentage')
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->suffix('%')
            ->rules([
                'required',
                function (Field $component) use ($project): MilestoneWeightsTotalRule {
                    return new MilestoneWeightsTotalRule(
                        $project,
                        $component->getRecord()?->getKey(),
                    );
                },
            ]);

        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                $startDate,
                Forms\Components\DatePicker::make('target_date')
                    ->native(false)
                    ->afterOrEqual('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('completed_at')
                    ->native(false)
                    ->afterOrEqual('target_date'),
                Forms\Components\Select::make('status')
                    ->options(ProjectMilestoneStatus::class)
                    ->default(ProjectMilestoneStatus::Pending)
                    ->required(),
                $weightField,
                Forms\Components\Repeater::make('subJobs')
                    ->relationship()
                    ->label('Sub-Jobs')
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                    ->reorderableWithButtons()
                    ->grid(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('start_date')
                            ->native(false)
                            ->required(),
                        Forms\Components\TextInput::make('working_days')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Forms\Components\TextInput::make('weight_percentage')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(MilestoneSubJobStatus::class)
                            ->default(MilestoneSubJobStatus::Pending)
                            ->required(),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->integer()
                            ->default(0),
                    ])
                    ->rules([new SubJobsWeightsTotalRule]),
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
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_percentage')
                    ->label('Weight')
                    ->suffix('%')
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
