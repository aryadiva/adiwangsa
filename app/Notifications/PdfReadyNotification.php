<?php

namespace App\Notifications;

use App\Enums\DocumentType;
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
        return (new MailMessage)
            ->subject("Your {$this->type->label()} is ready")
            ->line("The requested PDF ({$this->type->label()}) has been generated and is ready to download.")
            ->action('Download PDF', $this->downloadUrl)
            ->line('The download link expires in 24 hours.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "Your {$this->type->label()} is ready to download.",
            'url' => $this->downloadUrl,
            'document_type' => $this->type->value,
        ];
    }
}
