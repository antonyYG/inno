<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre automático de la jornada de prácticas (America/Lima):
// corta las conexiones activas de Discord a las 13:00 y genera las
// faltas de los practicantes que no registraron asistencia.
// El comando es idempotente y usa withoutOverlapping para evitar ejecuciones duplicadas.
Schedule::command('asistencia:cierre')
    ->weekdays()
    ->at('13:05')
    ->timezone('America/Lima')
    ->withoutOverlapping();
