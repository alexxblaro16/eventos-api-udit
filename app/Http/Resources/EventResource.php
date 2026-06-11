<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource del evento. Mete dentro la categoria y el organizador con sus Resources.
 * whenLoaded() solo incluye la relacion si se cargo antes, asi evito el N+1.
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'titulo' => $this->title,
            'descripcion' => $this->description,
            'ciudad' => $this->city,
            'lugar' => $this->venue,
            'fecha' => $this->starts_at?->format('d/m/Y H:i'),
            'aforo' => $this->capacity,
            'plazas_libres' => max(0, $this->capacity - $this->inscritosActivos()->count()),
            'agotado' => $this->is_sold_out,
            'estado' => $this->status === 'cancelled' ? 'cancelado' : 'activo',
            'imagen_portada' => $this->cover_image,
            'categoria' => new CategoryResource($this->whenLoaded('category')),
            'organizador' => new UserResource($this->whenLoaded('organizer')),
        ];
    }
}
