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
use Illuminate\Database\Eloquent\Model;

class ProjectMilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.milestone.title');
    }

    public function form(Form $form): Form
    {
        /** @var Project $project */
        $project = $this->ownerRecord;

        $startDate = Forms\Components\DatePicker::make('start_date')
            ->native(false)
            ->required()
            ->rules([
                new MilestoneStartDateRule($project->start_date->toDateString()),
            ]);

        $weightField = Forms\Components\TextInput::make('weight_percentage')
            ->label(__('app.milestone.weight_percentage'))
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
                    ->label(__('app.common.title'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('app.milestone.description'))
                    ->rows(3)
                    ->columnSpanFull(),
                $startDate,
                Forms\Components\DatePicker::make('target_date')
                    ->label(__('app.milestone.target_date'))
                    ->native(false)
                    ->afterOrEqual('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('completed_at')
                    ->label(__('app.milestone.completed_at'))
                    ->native(false)
                    ->afterOrEqual('target_date'),
                Forms\Components\Select::make('status')
                    ->label(__('app.milestone.status'))
                    ->options(ProjectMilestoneStatus::class)
                    ->default(ProjectMilestoneStatus::Pending)
                    ->required(),
                $weightField,
                Forms\Components\Section::make(__('app.milestone.sub_jobs_section'))
                    ->columnSpanFull()
                    ->description(__('app.milestone.sub_jobs_section_description'))
                    ->schema([
                        Forms\Components\Repeater::make('subJobs')
                            ->relationship()
                            ->label('')
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->reorderableWithButtons()
                            ->grid(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label(__('app.milestone.sub_job_title'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label(__('app.milestone.sub_job_description'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\DatePicker::make('start_date')
                                    ->label(__('app.milestone.sub_job_start_date'))
                                    ->native(false)
                                    ->required(),
                                Forms\Components\TextInput::make('working_days')
                                    ->label(__('app.milestone.working_days'))
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('app.milestone.quantity'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                Forms\Components\TextInput::make('weight_percentage')
                                    ->label(__('app.milestone.weight_percentage'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->label(__('app.milestone.sub_job_status'))
                                    ->options(MilestoneSubJobStatus::class)
                                    ->default(MilestoneSubJobStatus::Pending)
                                    ->required(),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label(__('app.milestone.sort_order'))
                                    ->numeric()
                                    ->integer()
                                    ->default(0),
                            ])
                            ->rules([new SubJobsWeightsTotalRule]),
                    ]),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('app.milestone.column_order'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('app.common.title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('app.milestone.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_date')
                    ->label(__('app.milestone.target_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_percentage')
                    ->label(__('app.milestone.column_weight'))
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label(__('app.milestone.completed_at'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.milestone.status'))
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
