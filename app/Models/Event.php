<?php

namespace App\Models;

use App\SearchTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory,   // Para poder usar su Factory y crear datos
        SoftDeletes,  // Borrado lógico (campo deleted_at)
        SearchTrait;  // Local scopes de búsqueda/filtrado (byCategory, byCity, upcoming...)

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'city',
        'venue',
        'starts_at',
        'capacity',
        'status',
        'cover_image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
        ];
    }

    /**
     * Campos calculados que se añaden al serializar el modelo.
     */
    protected $appends = ['is_sold_out'];

    /**
     * Relación 1:N inversa: un evento pertenece a un organizador (user).
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación 1:N inversa: un evento pertenece a una categoría.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación M:N: usuarios inscritos en el evento (con datos del pivote).
     */
    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->as('inscripcion')
            ->withPivot(['ticket_code', 'registered_at', 'checked_in_at', 'cancelled_at'])
            ->withTimestamps();
    }

    /** Inscritos activos (no cancelados). */
    public function inscritosActivos(): BelongsToMany
    {
        return $this->attendees()->wherePivotNull('cancelled_at');
    }

    /**
     * Relación 1:N: reseñas del evento.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Accessor: ¿el evento está agotado? (plazas activas >= aforo)
     * Se accede como $event->is_sold_out.
     */
    protected function isSoldOut(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->inscritosActivos()->count() >= $this->capacity,
        );
    }

    /**
     * Scope para los eventos agotados (inscritos sin cancelar >= aforo).
     * Cuento las inscripciones activas de cada evento con una subconsulta.
     */
    public function scopeSoldOut(Builder $query): Builder
    {
        return $query->whereRaw(
            '(select count(*) from event_user where event_user.event_id = events.id '
            . 'and event_user.cancelled_at is null) >= events.capacity'
        );
    }

    /**
     * Scope local DISPONIBLE: eventos con plazas libres (lo contrario de agotado).
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereRaw(
            '(select count(*) from event_user where event_user.event_id = events.id '
            . 'and event_user.cancelled_at is null) < events.capacity'
        );
    }
}
