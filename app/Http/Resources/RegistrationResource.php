<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource de una INSCRIPCIÓN (ticket). Se aplica sobre un modelo Event que viene
 * de la relación M:N del usuario, por lo que tiene cargado el pivote 'inscripcion'
 * (definido con ->as('inscripcion') en el modelo).
 */
class RegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo_ticket' => $this->inscripcion->ticket_code,
            'inscrito_el' => $this->inscripcion->registered_at,
            'check_in' => $this->inscripcion->checked_in_at,
            'cancelada_el' => $this->inscripcion->cancelled_at,
            'evento' => [
                'id' => $this->getKey(),
                'titulo' => $this->title,
                'ciudad' => $this->city,
                'fecha' => $this->starts_at?->format('d/m/Y H:i'),
            ],
        ];
    }
}
