<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests de integración de la API de eventos.
 * RefreshDatabase recrea la base de datos (en memoria) antes de cada test.
 */
class EventPlatformTest extends TestCase
{
    use RefreshDatabase;

    /** Un visitante puede registrarse y recibe un token. */
    public function test_un_usuario_puede_registrarse(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nuevo',
            'email' => 'nuevo@test.com',
            'password' => 'secreto',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'message', 'data' => ['usuario', 'token'], 'errors']);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    }

    /** El login con credenciales incorrectas devuelve 401 con la estructura común. */
    public function test_login_invalido_devuelve_401(): void
    {
        User::factory()->create(['email' => 'a@test.com']);

        $response = $this->postJson('/api/login', [
            'email' => 'a@test.com',
            'password' => 'incorrecta',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'data' => null]);
    }

    /** El catálogo de eventos es público (un visitante lo ve). */
    public function test_catalogo_de_eventos_es_publico(): void
    {
        Category::factory()->create();
        Event::factory(3)->create();

        $this->getJson('/api/events')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    /** Sólo un organizador puede crear eventos. */
    public function test_solo_un_organizador_crea_eventos(): void
    {
        $categoria = Category::factory()->create();
        $normal = User::factory()->create(['is_organizer' => false]);

        $payload = [
            'category_id' => $categoria->id,
            'title' => 'Mi concierto',
            'description' => 'Un gran concierto',
            'city' => 'Madrid',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'capacity' => 100,
        ];

        // Usuario normal -> 403
        $this->actingAs($normal)->postJson('/api/events', $payload)->assertStatus(403);

        // Organizador -> 201
        $organizador = User::factory()->create(['is_organizer' => true]);
        $this->actingAs($organizador)->postJson('/api/events', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.titulo', 'Mi concierto');
    }

    /** Un organizador no puede editar un evento de otro (Policy). */
    public function test_un_organizador_no_edita_eventos_de_otro(): void
    {
        $duenio = User::factory()->create(['is_organizer' => true]);
        $otro = User::factory()->create(['is_organizer' => true]);
        $evento = Event::factory()->create(['user_id' => $duenio->id]);

        $this->actingAs($otro)
            ->putJson("/api/events/{$evento->id}", ['title' => 'Hackeado'])
            ->assertStatus(403);
    }

    /** Inscribirse genera un ticket único y envía email de confirmación. */
    public function test_inscripcion_genera_ticket_y_envia_email(): void
    {
        Mail::fake();

        $evento = Event::factory()->create(['capacity' => 10]);
        $asistente = User::factory()->create();

        $response = $this->actingAs($asistente)
            ->postJson("/api/events/{$evento->id}/register");

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('event_user', [
            'user_id' => $asistente->id,
            'event_id' => $evento->id,
        ]);

        Mail::assertSent(\App\Mail\RegistrationConfirmationMail::class);
    }

    /** No te puedes inscribir a un evento agotado. */
    public function test_no_inscripcion_si_agotado(): void
    {
        $evento = Event::factory()->create(['capacity' => 1]);
        $service = app()->get(RegistrationService::class);
        $service->inscribir(User::factory()->create(), $evento); // llena el aforo

        $this->actingAs(User::factory()->create())
            ->postJson("/api/events/{$evento->id}/register")
            ->assertStatus(409);
    }

    /** El organizador hace check-in con el código del ticket. */
    public function test_organizador_hace_checkin(): void
    {
        $organizador = User::factory()->create(['is_organizer' => true]);
        $evento = Event::factory()->create(['user_id' => $organizador->id, 'capacity' => 10]);
        $asistente = User::factory()->create();

        $service = app()->get(RegistrationService::class);
        $ticket = $service->inscribir($asistente, $evento);

        $this->actingAs($organizador)
            ->postJson("/api/events/{$evento->id}/check-in", ['ticket_code' => $ticket])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(
            \DB::table('event_user')->where('ticket_code', $ticket)->value('checked_in_at')
        );
    }

    /** Sólo puedes valorar un evento al que has asistido (check-in). */
    public function test_solo_valoras_si_asististe(): void
    {
        $evento = Event::factory()->create(['capacity' => 10]);
        $asistente = User::factory()->create();
        $service = app()->get(RegistrationService::class);
        $service->inscribir($asistente, $evento);

        // Sin check-in -> 403
        $this->actingAs($asistente)
            ->postJson("/api/events/{$evento->id}/reviews", ['rating' => 5])
            ->assertStatus(403);

        // Con check-in -> 201
        $service->checkIn($evento, \DB::table('event_user')->value('ticket_code'));
        $this->actingAs($asistente)
            ->postJson("/api/events/{$evento->id}/reviews", ['rating' => 5, 'comment' => 'Genial'])
            ->assertStatus(201);
    }

    /** Cancelar un evento notifica a los inscritos. */
    public function test_cancelar_evento_notifica_inscritos(): void
    {
        Notification::fake();

        $organizador = User::factory()->create(['is_organizer' => true]);
        $evento = Event::factory()->create(['user_id' => $organizador->id, 'capacity' => 10]);
        $asistente = User::factory()->create();
        app()->get(RegistrationService::class)->inscribir($asistente, $evento);

        $this->actingAs($organizador)
            ->postJson("/api/events/{$evento->id}/cancel")
            ->assertOk();

        Notification::assertSentTo($asistente, \App\Notifications\EventCancelledNotification::class);
    }
}
