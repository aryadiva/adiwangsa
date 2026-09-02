<?php

namespace App\Mail;

use App\DTOs\ReportDataDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Emailed Daily Site Progress Summary PDF to the client (PRD §7.3).
 * Replaces the removed client-portal notification. Transport stays on
 * Mailpit/dummy for v0.2.0 — production SMTP is an .env-only change.
 */
class DailyReportPublished extends Mailable implements ShouldQueue
{
    use Queueable;

    public string $pdfPath = '';

    /**
     * @param  list<string>  $receivers
     * @param  list<string>  $cc
     */
    public function __construct(
        public ReportDataDTO $dto,
        public string $senderEmail,
        public string $senderName,
        public array $receivers,
        public $cc = [],
    ) {
        $this->cc = $cc;
    }

    /** @return list<string> */
    public function ccList(): array
    {
        return (array) $this->cc;
    }

    public function withPdfPath(string $path): static
    {
        $this->pdfPath = $path;

        return $this;
    }

    public function envelope(): Envelope
    {
        $from = new Address($this->senderEmail, $this->senderName);

        $envelope = (new Envelope)
            ->from($from)
            ->replyTo($from)
            ->subject(__(
                'mail.daily_report_subject',
                ['site' => $this->dto->siteName ?? '', 'date' => $this->dto->reportDate ?? ''],
            ));

        foreach ($this->receivers as $receiver) {
            $envelope->to($receiver);
        }

        foreach ($this->ccList() as $ccAddress) {
            $envelope->cc($ccAddress);
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-report-published',
            with: [
                'siteName' => $this->dto->siteName,
                'reportDate' => $this->dto->reportDate,
                'projectName' => $this->dto->projectName,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('pdfs', $this->pdfPath)
                ->as($this->attachmentName())
                ->withMime('application/pdf'),
        ];
    }

    protected function attachmentName(): string
    {
        $date = $this->dto->reportDate ? Carbon::parse($this->dto->reportDate)->format('Ymd') : now()->format('Ymd');
        $site = Str::slug($this->dto->siteName ?? 'site');

        return "{$this->dto->projectCode}-{$site}-{$date}.pdf";
    }
}
