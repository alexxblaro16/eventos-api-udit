<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso que les llega a los inscritos cuando se cancela un evento.
 * La mando por dos canales:
 *   - mail     => toMail()
 *   - database => toArray()  (se guarda en la tabla notifications)
 */
class EventCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public Event $event)
    {
    }

    /**
     * Canales por los que se envía la notificación.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Representación por email.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Evento cancelado: ' . $this->event->title)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Lamentamos informarte de que el evento "' . $this->event->title . '" ha sido cancelado por el organizador.')
            ->line('Ciudad: ' . $this->event->city)
            ->line('Fecha prevista: ' . $this->event->starts_at->format('d/m/Y H:i'))
            ->line('Disculpa las molestias.');
    }

    /**
     * Representación que se guarda en base de datos (canal database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'evento_id' => $this->event->id,
            'titulo' => $this->event->title,
            'mensaje' => 'El evento "' . $this->event->title . '" ha sido cancelado.',
        ];
    }
}
