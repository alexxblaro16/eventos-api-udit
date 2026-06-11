<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Aqui meto toda la logica de las inscripciones: inscribir (genera el codigo),
 * cancelar y check-in. Todo va sobre el pivote event_user.
 */
class RegistrationService
{
    /**
     * Inscribe a un usuario en un evento.
     * Genera un código de ticket único y guarda la fecha de inscripción en el pivote.
     * Devuelve el código de ticket generado.
     */
    public function inscribir(User $user, Event $event): string
    {
        $ticketCode = $this->generarCodigoUnico();

        $user->registrations()->attach($event->getKey(), [
            'ticket_code' => $ticketCode,
            'registered_at' => now(),
        ]);

        return $ticketCode;
    }

    /**
     * Cancela la inscripción de un usuario a un evento (marca cancelled_at en el pivote).
     * No borramos la fila para conservar el historial del ticket.
     */
    public function cancelar(User $user, Event $event): void
    {
        $user->registrations()->updateExistingPivot($event->getKey(), [
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Check-in: el organizador valida un código de ticket en su evento.
     * Marca checked_in_at en el pivote correspondiente.
     */
    public function checkIn(Event $event, string $ticketCode): bool
    {
        // Buscamos al inscrito activo cuyo ticket coincide
        $attendee = $event->inscritosActivos()
            ->wherePivot('ticket_code', $ticketCode)
            ->first();

        if (! $attendee) {
            return false;
        }

        $event->attendees()->updateExistingPivot($attendee->getKey(), [
            'checked_in_at' => now(),
        ]);

        return true;
    }

    /**
     * Genera un código de ticket único (8 caracteres en mayúsculas).
     * Repite hasta que no exista en el pivote.
     */
    private function generarCodigoUnico(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (
            \DB::table('event_user')->where('ticket_code', $code)->exists()
        );

        return $code;
    }
}
