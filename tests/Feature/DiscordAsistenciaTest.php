<?php

use App\Models\Asistencia;
use App\Models\User;
use App\Services\Discord\AttendanceDiscordService;
use Illuminate\Support\Carbon;

/*
| Escenarios de acumulación de horas por Discord y del cierre automático.
*/

function servicioDiscord(): AttendanceDiscordService
{
    return app(AttendanceDiscordService::class);
}

function crearAsistenciaHoy(User $usuario, string $tipo = 'tardanza'): Asistencia
{
    return Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => today()->toDateString(),
        'tipo' => $tipo,
    ]);
}

it('inicia la conexión y guarda la primera hora de ingreso', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    expect($asistencia->fresh()->hora_inicio)->toBe('08:30:00')
        ->and(Cache::has("attendance:{$asistencia->id}:connection"))->toBeTrue();
});

it('cambia online a idle y a dnd sin reiniciar el contador', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');
    $inicioInicial = Cache::get("attendance:{$asistencia->id}:connection");

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'idle');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 15, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'dnd');

    expect(Cache::get("attendance:{$asistencia->id}:connection"))->toBe($inicioInicial)
        ->and($inicioInicial)->toContain('08:30');
});

it('acumula el tiempo al desconectarse', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 30, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'offline');

    $actualizada = $asistencia->fresh();

    expect($actualizada->tiempo_trabajado)->toBe('01:00:00')
        ->and($actualizada->hora_fin)->toBe('09:30:00');
});

it('acumula varias conexiones el mismo día sin duplicar', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'offline');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 10, 0, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 10, 30, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'offline');

    // 30 min (08:30-09:00) + 30 min (10:00-10:30) = 1 hora.
    expect($asistencia->fresh()->tiempo_trabajado)->toBe('01:00:00');
});

it('no contabiliza tiempo antes de las 08:00', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 7, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario, 'asistio');

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 30, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'offline');

    $actualizada = $asistencia->fresh();

    expect($actualizada->hora_inicio)->toBe('08:00:00')
        ->and($actualizada->tiempo_trabajado)->toBe('00:30:00');
});

it('no inicia conexión después de las 13:00', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 13, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    expect(Cache::has("attendance:{$asistencia->id}:connection"))->toBeFalse()
        ->and($asistencia->fresh()->hora_inicio)->toBeNull();
});

it('corta la contabilización a las 13:00 en una desconexión tardía', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 16, 14, 0, 0, 'America/Lima'));
    servicioDiscord()->processPresence($usuario->discord_id, 'offline');

    $actualizada = $asistencia->fresh();

    expect($actualizada->tiempo_trabajado)->toBe('01:00:00')
        ->and($actualizada->hora_fin)->toBe('13:00:00');
});

it('no cuenta tiempo para usuarios sin asistencia registrada', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    expect(Asistencia::count())->toBe(0);
});

it('no cuenta tiempo para faltas ni justificadas', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = crearAsistenciaHoy($usuario, 'falto');

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    expect(Cache::has("attendance:{$asistencia->id}:connection"))->toBeFalse();
});

it('no cuenta tiempo para usuarios inactivos', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearPracticante(['estado' => false]);

    servicioDiscord()->processPresence($usuario->discord_id, 'online');

    expect(Asistencia::count())->toBe(0);
});
