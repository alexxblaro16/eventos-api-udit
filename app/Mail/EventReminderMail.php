<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email recordatorio que se envía automáticamente 24h antes del evento.
 */
class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Event $event,
        public string $ticketCode
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: mañana es ' . $this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.event_reminder',
            with: [
                'usuario' => $this->user,
                'evento' => $this->event,
                'codigo' => $this->ticketCode,
            ],
        );
    }
}
