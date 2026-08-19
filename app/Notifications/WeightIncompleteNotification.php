<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Notifications\Notification;

class WeightIncompleteNotification extends Notification
{
    /**
     * @param  array<int, array{title: string, total: string}>  $incompleteSets
     */
    public function __construct(
        public Project $project,
        public array $incompleteSets,
    ) {}

    /**
     * Bell-only. A normal, dismissible notification.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $bodyParts = [];

        foreach ($this->incompleteSets as $set) {
            $bodyParts[] = "{$set['title']} ({$set['total']}%)";
        }

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'status' => 'warning',
            'icon' => 'heroicon-o-exclamation-triangle',
            'project_id' => $this->project->getKey(),
            'title' => __('Milestone weights incomplete'),
            'body' => $this->project->name.': '.implode(', ', $bodyParts).'. '
                .__('All weight sets must total exactly 100%.'),
        ];
    }
}
