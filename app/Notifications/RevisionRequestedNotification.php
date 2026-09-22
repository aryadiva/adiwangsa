<?php

namespace App\Notifications;

use App\Models\DailyReport;
use App\Support\NotifiableLocale;
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
        $locale = NotifiableLocale::of($notifiable);
        $notes = $this->report->admin_notes;

        return (new MailMessage)
            ->subject(__('app.notification.revision_subject', ['site' => $this->report->site->name], $locale))
            ->line(__('app.notification.revision_line', [
                'site' => $this->report->site->name,
                'date' => $this->report->report_date->toDateString(),
            ], $locale))
            ->when($notes !== null, fn (MailMessage $message): MailMessage => $message->line(__('app.notification.revision_notes', ['notes' => $notes], $locale)))
            ->line(__('app.notification.revision_instruction', [], $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $locale = NotifiableLocale::of($notifiable);
        $notes = $this->report->admin_notes;

        return [
            'message' => __('app.notification.revision_db', ['site' => $this->report->site->name], $locale)
                .($notes !== null ? ' '.__('app.notification.revision_notes', ['notes' => $notes], $locale) : ''),
            'url' => "/admin/daily-reports/{$this->report->id}/edit",
            'report_id' => $this->report->id,
            'report_date' => $this->report->report_date->toDateString(),
        ];
    }
}
