<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\TargetDelayWarning;
use App\Support\NotifiableLocale;
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
        $locale = NotifiableLocale::of($notifiable);
        $subJob = $this->warning->subJob;
        /** @var Project|null $project */
        $project = $this->warning->project;
        $projectName = $project !== null ? $project->name : __('app.notification.unknown_project');

        return (new MailMessage)
            ->subject(__('app.notification.target_warning_subject', ['project' => $projectName], $locale))
            ->line(__('app.notification.target_warning_line', ['sub_job' => $subJob->title], $locale))
            ->line(__('app.notification.target_warning_daily_target', ['target' => $this->warning->daily_target], $locale))
            ->line(__('app.notification.target_warning_actual', ['actual' => $this->warning->actual_progress], $locale))
            ->line(__('app.notification.target_warning_deficit', ['deficit' => $this->warning->deficit], $locale))
            ->action(__('app.notification.target_warning_action', [], $locale), route('filament.admin.resources.daily-reports.edit', $this->warning->daily_report_id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $locale = NotifiableLocale::of($notifiable);
        $subJob = $this->warning->subJob;

        return [
            'format' => 'filament',
            'duration' => 'persistent',
            'status' => 'warning',
            'icon' => 'heroicon-o-exclamation-triangle',
            'title' => __('app.notification.target_warning_title', [], $locale),
            'body' => __('app.notification.target_warning_body', [
                'sub_job' => $subJob->title,
                'deficit' => $this->warning->deficit,
            ], $locale),
            'target_delay_warning_id' => $this->warning->id,
            'daily_report_id' => $this->warning->daily_report_id,
            'milestone_sub_job_id' => $this->warning->milestone_sub_job_id,
        ];
    }
}
