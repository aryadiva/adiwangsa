<?php

namespace App\Notifications;

use App\Models\DailyReport;
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
        return (new MailMessage)
            ->subject("Daily report submitted for approval — {$this->report->site->name}")
            ->line("A daily report for {$this->report->site->name} on {$this->report->report_date->toDateString()} has been submitted for approval.")
            ->line('Review it in the admin queue.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Daily report for {$this->report->site->name} submitted for approval.",
            'url' => "/admin/daily-reports/{$this->report->id}/edit",
            'report_id' => $this->report->id,
            'report_date' => $this->report->report_date->toDateString(),
        ];
    }
}
