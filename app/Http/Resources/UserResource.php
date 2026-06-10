<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource del usuario. Nunca exponemos la contraseña.
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'nombre' => $this->name,
            'email' => $this->email,
            'es_organizador' => (bool) $this->is_organizer,
        ];
    }
}
