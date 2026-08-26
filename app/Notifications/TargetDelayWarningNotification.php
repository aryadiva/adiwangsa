<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\TargetDelayWarning;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TargetDelayWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TargetDelayWarning $warning) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subJob = $this->warning->subJob;
        /** @var Project|null $project */
        $project = $this->warning->project;
        $projectName = $project !== null ? $project->name : 'Unknown Project';

        return (new MailMessage)
            ->subject("Target delay warning — {$projectName}")
            ->line("Sub-job \"{$subJob->title}\" has fallen behind its daily target.")
            ->line("Daily target: {$this->warning->daily_target}")
            ->line("Actual progress: {$this->warning->actual_progress}")
            ->line("Deficit: {$this->warning->deficit}")
            ->action('View Report', route('filament.admin.resources.daily-reports.edit', $this->warning->daily_report_id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $subJob = $this->warning->subJob;

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'status' => 'warning',
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => 'Target delay warning',
            'body' => "Sub-job \"{$subJob->title}\" is behind schedule. Deficit: {$this->warning->deficit}.",
            'target_delay_warning_id' => $this->warning->id,
            'daily_report_id' => $this->warning->daily_report_id,
            'milestone_sub_job_id' => $this->warning->milestone_sub_job_id,
        ];
    }
}
