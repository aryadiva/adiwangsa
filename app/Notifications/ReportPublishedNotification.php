<?php

namespace App\Notifications;

use App\Models\DailyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportPublishedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject("Daily progress report published — {$this->report->site->name}")
            ->line("A new daily progress report has been published for {$this->report->site->name} on {$this->report->report_date->toDateString()}.");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Daily progress report published for {$this->report->site->name}.",
            'url' => "/admin/daily-reports/{$this->report->id}/edit",
            'report_id' => $this->report->id,
            'report_date' => $this->report->report_date->toDateString(),
        ];
    }
}
