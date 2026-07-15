<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email pemberitahuan generik untuk aksi/event sistem.
 * ShouldQueue → dikirim lewat queue (async bila worker aktif; inline pada
 * QUEUE_CONNECTION=sync). Kegagalan kirim tidak menggagalkan aksi (lihat Notifier).
 */
class ActionNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $subjectLine,
        public string $body,
        public ?string $url = null,
        public ?string $urlLabel = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.action', with: [
            'name'     => $this->name,
            'body'     => $this->body,
            'url'      => $this->url,
            'urlLabel' => $this->urlLabel,
        ]);
    }
}
