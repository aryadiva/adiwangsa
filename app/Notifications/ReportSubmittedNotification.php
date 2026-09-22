<?php

namespace App\Notifications;

use App\Models\DailyReport;
use App\Support\NotifiableLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DailyReport $report) {}

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

        return (new MailMessage)
            ->subject(__('app.notification.submitted_subject', ['site' => $this->report->site->name], $locale))
            ->line(__('app.notification.submitted_line', [
                'site' => $this->report->site->name,
                'date' => $this->report->report_date->toDateString(),
            ], $locale))
            ->line(__('app.notification.submitted_review', [], $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => __('app.notification.submitted_db', ['site' => $this->report->site->name], NotifiableLocale::of($notifiable)),
            'url' => "/admin/daily-reports/{$this->report->id}/edit",
            'report_id' => $this->report->id,
            'report_date' => $this->report->report_date->toDateString(),
        ];
    }
}
