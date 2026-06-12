<?php

namespace App\Console\Commands;

use App\Mail\EventReminderMail;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEventRemindersCommand extends Command
{
    /**
     * Nombre y firma del comando.
     * Se puede ejecutar a mano:  php artisan app:send-event-reminders
     *
     * @var string
     */
    protected $signature = 'app:send-event-reminders';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Envía recordatorios por email a los inscritos de eventos que ocurren dentro de 24h';

    /**
     * Busca los eventos que empiezan dentro de ~24h y manda un email a cada inscrito.
     * Lo tengo programado cada hora en routes/console.php (asi es automatico).
     */
    public function handle()
    {
        // Ventana: eventos que empiezan entre dentro de 24h y dentro de 25h.
        // Ejecutando el comando cada hora, cada evento recibe el recordatorio una sola vez.
        $desde = now()->addHours(24);
        $hasta = now()->addHours(25);

        $eventos = Event::where('status', 'active')
            ->whereBetween('starts_at', [$desde, $hasta])
            ->get();

        $this->info('Eventos en ventana de recordatorio: ' . $eventos->count());

        $totalEmails = 0;

        foreach ($eventos as $evento) {
            // Inscritos activos (no cancelados)
            $inscritos = $evento->inscritosActivos()->get();

            foreach ($inscritos as $inscrito) {
                Mail::to($inscrito->email)->send(
                    new EventReminderMail($inscrito, $evento, $inscrito->inscripcion->ticket_code)
                );
                $totalEmails++;
            }

            $this->info(" - '{$evento->title}': {$inscritos->count()} recordatorios enviados.");
        }

        $this->info("Total de recordatorios enviados: {$totalEmails}");

        return self::SUCCESS;
    }
}
