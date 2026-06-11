<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait con los local scopes que uso para filtrar el catalogo de eventos.
 * Se encadenan desde el modelo, p.ej:
 *   Event::byCategory(3)->byCity('Madrid')->upcoming()->get();
 */
trait SearchTrait
{
    /** Busca por título (texto parcial). */
    public function scopeFindByTitle(Builder $query, string $title): Builder
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    /** Filtra por categoría. */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    /** Filtra por ciudad. */
    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', '%' . $city . '%');
    }

    /** Filtra por fecha concreta (día del evento). */
    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('starts_at', $date);
    }

    /** Eventos próximos (su fecha aún no ha pasado). */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    /** Eventos pasados (su fecha ya pasó). */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('starts_at', '<', now());
    }
}
