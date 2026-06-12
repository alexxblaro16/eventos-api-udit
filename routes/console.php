<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorio automatico: lanzo el comando cada hora.
// En produccion lo dispara el cron con:  * * * * * php artisan schedule:run
Schedule::command('app:send-event-reminders')->hourly();
