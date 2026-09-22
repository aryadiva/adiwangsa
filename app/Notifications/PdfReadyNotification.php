<?php

namespace App\Notifications;

use App\Enums\DocumentType;
use App\Support\NotifiableLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PdfReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DocumentType $type, public string $downloadUrl) {}

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
            ->subject(__('app.notification.pdf_ready_subject', ['document' => $this->type->label()], $locale))
            ->line(__('app.notification.pdf_ready_line', ['document' => $this->type->label()], $locale))
            ->action(__('app.notification.pdf_ready_action', [], $locale), $this->downloadUrl)
            ->line(__('app.notification.pdf_ready_expiry', [], $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => __('app.notification.pdf_ready_db', ['document' => $this->type->label()], NotifiableLocale::of($notifiable)),
            'url' => $this->downloadUrl,
            'document_type' => $this->type->value,
        ];
    }
}
