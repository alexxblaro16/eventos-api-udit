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
 * Email de confirmacion cuando alguien se inscribe a un evento.
 * Lleva envelope() (asunto) y content() (vista + datos).
 */
class RegistrationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Las propiedades públicas quedan disponibles directamente en la vista.
     */
    public function __construct(
        public User $user,
        public Event $event,
        public string $ticketCode
    ) {
    }

    /**
     * Asunto del email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de inscripción: ' . $this->event->title,
        );
    }

    /**
     * Vista del email y datos que se le pasan.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.registration_confirmation',
            with: [
                'usuario' => $this->user,
                'evento' => $this->event,
                'codigo' => $this->ticketCode,
            ],
        );
    }
}
