<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Policy de eventos: define quién puede gestionar cada evento.
 * El enunciado dice que un organizador sólo gestiona SUS PROPIOS eventos.
 */
class EventPolicy
{
    /**
     * ¿Puede el usuario gestionar (editar / eliminar / inscritos / check-in) este evento?
     * Sólo si es el organizador que lo creó.
     */
    public function manage(User $user, Event $event): bool
    {
        return $user->id === $event->user_id;
    }
}
