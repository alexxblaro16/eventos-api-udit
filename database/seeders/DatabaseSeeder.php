<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga de datos de demostración.
     */
    public function run(): void
    {
        // ---- Categorías fijas del enunciado ----
        $categorias = ['Conciertos', 'Talleres', 'Meetups', 'Conferencias', 'Jornadas deportivas'];
        foreach ($categorias as $nombre) {
            Category::create(['name' => $nombre]);
        }

        // ---- Un organizador y dos asistentes de prueba ----
        $organizador = User::create([
            'name' => 'Alejandro Organizador',
            'email' => 'organizador@eventos.test',
            'password' => Hash::make('password'),
            'is_organizer' => true,
        ]);

        $asistente1 = User::create([
            'name' => 'Asistente Uno',
            'email' => 'asistente1@eventos.test',
            'password' => Hash::make('password'),
        ]);

        $asistente2 = User::create([
            'name' => 'Asistente Dos',
            'email' => 'asistente2@eventos.test',
            'password' => Hash::make('password'),
        ]);

        // ---- Eventos del organizador (próximos y pasados) ----
        $proximos = Event::factory(5)->create([
            'user_id' => $organizador->id,
            'category_id' => Category::inRandomOrder()->first()->id,
        ]);

        Event::factory(3)->pasado()->create([
            'user_id' => $organizador->id,
            'category_id' => Category::inRandomOrder()->first()->id,
        ]);

        // ---- Inscripciones de demo a través del Service (genera tickets reales) ----
        $service = app()->get(RegistrationService::class);
        foreach ($proximos->take(2) as $evento) {
            $service->inscribir($asistente1, $evento);
            $service->inscribir($asistente2, $evento);
        }
    }
}
