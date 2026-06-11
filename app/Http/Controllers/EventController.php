<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Notifications\EventCancelledNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Catálogo público de eventos con búsqueda y filtros.
     * Visitantes (sin autenticar) pueden consultarlo.
     * Filtros vía LOCAL SCOPES del SearchTrait: categoria, ciudad, fecha, estado.
     */
    public function index(Request $request)
    {
        $query = Event::query()->with(['category', 'organizer']);

        // Filtros opcionales encadenando local scopes
        if ($request->filled('titulo')) {
            $query->findByTitle($request->titulo);
        }
        if ($request->filled('categoria')) {
            $query->byCategory((int) $request->categoria);
        }
        if ($request->filled('ciudad')) {
            $query->byCity($request->ciudad);
        }
        if ($request->filled('fecha')) {
            $query->byDate($request->fecha);
        }

        // Estado: proximos | pasados | agotados | disponibles
        switch ($request->estado) {
            case 'proximos':
                $query->upcoming();
                break;
            case 'pasados':
                $query->past();
                break;
            case 'agotados':
                $query->soldOut();
                break;
            case 'disponibles':
                $query->available();
                break;
        }

        $events = $query->orderBy('starts_at')->paginate(10);

        return $this->jsonOk(EventResource::collection($events), 'Catálogo de eventos.');
    }

    /**
     * Detalle público de un evento.
     */
    public function show(string $id)
    {
        $event = Event::with(['category', 'organizer'])->findOrFail($id);

        return $this->jsonOk(new EventResource($event), 'Detalle del evento.');
    }

    /**
     * Crea un evento. Sólo usuarios con rol organizador.
     */
    public function store(Request $request)
    {
        if (! $request->user()->is_organizer) {
            return $this->jsonError('Necesitas el rol de organizador para crear eventos.', null, 403);
        }

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'city' => ['required', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after:now'],
            'capacity' => ['required', 'integer', 'min:1'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $event = Event::create($validated);

        return $this->jsonOk(
            new EventResource($event->load(['category', 'organizer'])),
            'Evento creado.',
            201
        );
    }

    /**
     * Edita un evento propio (Policy manage).
     */
    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        if ($request->user()->cannot('manage', $event)) {
            return $this->jsonError('No puedes editar un evento que no es tuyo.', null, 403);
        }

        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'city' => ['sometimes', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $event->update($validated);

        return $this->jsonOk(
            new EventResource($event->load(['category', 'organizer'])),
            'Evento actualizado.'
        );
    }

    /**
     * Elimina (borrado lógico) un evento propio.
     */
    public function destroy(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        if ($request->user()->cannot('manage', $event)) {
            return $this->jsonError('No puedes eliminar un evento que no es tuyo.', null, 403);
        }

        $event->delete();

        return $this->jsonOk(null, 'Evento eliminado.');
    }

    /**
     * Cancela un evento propio (status = cancelled) y NOTIFICA a todos los inscritos activos.
     * La notificación se envía por email y se guarda en base de datos (canal database).
     */
    public function cancel(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        if ($request->user()->cannot('manage', $event)) {
            return $this->jsonError('No puedes cancelar un evento que no es tuyo.', null, 403);
        }

        if ($event->status === 'cancelled') {
            return $this->jsonError('El evento ya estaba cancelado.', null, 409);
        }

        $event->update(['status' => 'cancelled']);

        // Notificamos a todos los inscritos activos
        $inscritos = $event->inscritosActivos()->get();
        Notification::send($inscritos, new EventCancelledNotification($event));

        return $this->jsonOk(
            new EventResource($event->load(['category', 'organizer'])),
            'Evento cancelado. Se ha avisado a los inscritos.'
        );
    }

    /**
     * Actualiza la imagen de portada de un evento propio.
     * La imagen se guarda en storage (disco public) y se guarda su ruta.
     */
    public function updateCover(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        if ($request->user()->cannot('manage', $event)) {
            return $this->jsonError('No puedes modificar un evento que no es tuyo.', null, 403);
        }

        $request->validate([
            'cover_image' => ['required', 'image', 'max:2048'],
        ]);

        // Borramos la portada anterior si existía
        if ($event->cover_image) {
            Storage::disk('public')->delete($event->cover_image);
        }

        $path = $request->file('cover_image')->store('covers', 'public');
        $event->update(['cover_image' => $path]);

        return $this->jsonOk(
            new EventResource($event->load(['category', 'organizer'])),
            'Imagen de portada actualizada.'
        );
    }
}
