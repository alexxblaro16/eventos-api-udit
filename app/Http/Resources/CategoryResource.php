<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource: capa intermedia entre el modelo y el JSON que devolvemos al cliente.
 * Con $this accedo al modelo Category.
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'nombre' => $this->name,
        ];
    }
}
