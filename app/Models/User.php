<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory>
     *
     * HasApiTokens => permite crear tokens de autenticación (Sanctum) para el usuario.
     * Notifiable   => permite enviar notificaciones al modelo con la función notify().
     */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_organizer',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_organizer' => 'boolean',
        ];
    }

    /**
     * Relación 1:N: eventos que este usuario ORGANIZA.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relación M:N: eventos en los que este usuario está INSCRITO (asistente).
     * Acceso a los campos extra del pivote (ticket_code, fechas) con ->as('inscripcion').
     */
    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->as('inscripcion')
            ->withPivot(['ticket_code', 'registered_at', 'checked_in_at', 'cancelled_at'])
            ->withTimestamps();
    }

    /** Inscripciones activas (no canceladas). */
    public function inscripcionesActivas(): BelongsToMany
    {
        return $this->registrations()->wherePivotNull('cancelled_at');
    }

    /**
     * Relación 1:N: reseñas escritas por este usuario.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
