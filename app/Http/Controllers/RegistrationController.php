<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegistrationResource;
use App\Http\Resources\UserResource;
use App\Mail\RegistrationConfirmationMail;
use App\Models\Event;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class RegistrationController extends Controller
{
    /**
     * Inscribe al usuario autenticado en un evento.
     * Genera un código de ticket único (Service) y envía email de confirmación.
     */
    public function store(Request $request, string $eventId)
    {
        $event = Event::findOrFail($eventId);
        $user = $request->user();

        // Validaciones de negocio
        if ($event->status === 'cancelled') {
            return $this->jsonError('El evento está cancelado.', null, 409);
        }
        if ($event->starts_at->isPast()) {
            return $this->jsonError('No puedes inscribirte a un evento que ya ha pasado.', null, 409);
        }
        if ($event->is_sold_out) {
            return $this->jsonError('El evento está agotado.', null, 409);
        }
        // ¿ya inscrito y sin cancelar?
        $yaInscrito = $user->registrations()
            ->wherePivot('event_id', $event->id)
            ->wherePivotNull('cancelled_at')
            ->exists();
        if ($yaInscrito) {
            return $this->jsonError('Ya estás inscrito en este evento.', null, 409);
        }

        $service = app()->get(RegistrationService::class);
        $ticketCode = $service->inscribir($user, $event);

        // Email de confirmación al usuario inscrito
        Mail::to($user->email)->send(new RegistrationConfirmationMail($user, $event, $ticketCode));

        return $this->jsonOk([
            'codigo_ticket' => $ticketCode,
            'evento' => $event->title,
        ], 'Inscripción realizada. Revisa tu email.', 201);
    }

    /**
     * Cancela la inscripción del usuario autenticado a un evento.
     */
    public function destroy(Request $request, string $eventId)
    {
        $event = Event::findOrFail($eventId);
        $user = $request->user();

        $inscrito = $user->registrations()
            ->wherePivot('event_id', $event->id)
            ->wherePivotNull('cancelled_at')
            ->exists();

        if (! $inscrito) {
            return $this->jsonError('No tienes una inscripción activa en este evento.', null, 404);
        }

        $service = app()->get(RegistrationService::class);
        $service->cancelar($user, $event);

        return $this->jsonOk(null, 'Inscripción cancelada.');
    }

    /**
     * Lista los tickets (inscripciones) del usuario autenticado.
     */
    public function misTickets(Request $request)
    {
        $tickets = $request->user()->registrations()->get();

        return $this->jsonOk(RegistrationResource::collection($tickets), 'Tus tickets.');
    }

    /**
     * Check-in: el organizador valida un código de ticket en su evento.
     */
    public function checkIn(Request $request, string $eventId)
    {
        $event = Event::findOrFail($eventId);

        if ($request->user()->cannot('manage', $event)) {
            return $this->jsonError('Sólo el organizador del evento puede hacer check-in.', null, 403);
        }

        $request->validate([
            'ticket_code' => ['required', 'string'],
        ]);

        $service = app()->get(RegistrationService::class);
        $ok = $service->checkIn($event, $request->ticket_code);

        if (! $ok) {
            return $this->jsonError('Código de ticket no válido para este evento.', null, 404);
        }

        return $this->jsonOk(null, 'Check-in realizado correctamente.');
    }

    /**
     * Lista los inscritos activos de un evento (sólo el organizador).
     */
    public function inscritos(Request $request, string $eventId)
    {
        $event = Event::findOrFail($eventId);

        if ($request->user()->cannot('manage', $event)) {
            return $this->jsonError('Sólo el organizador puede ver los inscritos.', null, 403);
        }

        return $this->jsonOk(
            UserResource::collection($event->inscritosActivos()->get()),
            'Inscritos del evento.'
        );
    }
}
