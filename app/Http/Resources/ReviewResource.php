<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de una reseña de un evento.
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'puntuacion' => $this->rating,
            'comentario' => $this->comment,
            'autor' => new UserResource($this->whenLoaded('user')),
            'evento_id' => $this->event_id,
            'fecha' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
