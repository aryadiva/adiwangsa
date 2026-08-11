<?php

namespace App\Notifications;

use App\Models\DailyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionRequestedNotification extends Notification implements ShouldQueue
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
        $notes = $this->report->admin_notes;

        return (new MailMessage)
            ->subject("Revision requested — {$this->report->site->name}")
            ->line("A revision has been requested for your daily report on {$this->report->site->name} ({$this->report->report_date->toDateString()}).")
            ->when($notes !== null, fn (MailMessage $message): MailMessage => $message->line("Notes: {$notes}"))
            ->line('Please update the report and resubmit for approval.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $notes = $this->report->admin_notes;

        return [
            'message' => "Revision requested for {$this->report->site->name} report.".($notes !== null ? " Notes: {$notes}" : ''),
            'url' => "/admin/daily-reports/{$this->report->id}/edit",
            'report_id' => $this->report->id,
            'report_date' => $this->report->report_date->toDateString(),
        ];
    }
}
