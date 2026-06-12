<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Event;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Lista pública de reseñas de un evento.
     */
    public function index(string $eventId)
    {
        $event = Event::findOrFail($eventId);
        $reviews = $event->reviews()->with('user')->latest()->get();

        return $this->jsonOk(ReviewResource::collection($reviews), 'Reseñas del evento.');
    }

    /**
     * Crea una reseña. Sólo si el usuario ASISTIÓ al evento (tiene check-in)
     * y no lo ha valorado ya.
     */
    public function store(Request $request, string $eventId)
    {
        $event = Event::findOrFail($eventId);
        $user = $request->user();

        // ¿Asistió? -> inscripción con checked_in_at no nulo
        $asistio = $user->registrations()
            ->wherePivot('event_id', $event->id)
            ->wherePivotNotNull('checked_in_at')
            ->exists();

        if (! $asistio) {
            return $this->jsonError('Sólo puedes valorar un evento al que hayas asistido.', null, 403);
        }

        // ¿Ya lo valoró?
        $yaValorado = Review::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();

        if ($yaValorado) {
            return $this->jsonError('Ya has valorado este evento.', null, 409);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = Review::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return $this->jsonOk(
            new ReviewResource($review->load('user')),
            'Reseña publicada.',
            201
        );
    }
}
