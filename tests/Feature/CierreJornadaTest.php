<?php

use App\Models\Asistencia;
use App\Services\Discord\AttendanceDiscordService;
use Illuminate\Support\Carbon;

/*
| Escenarios del cierre automático de jornada (asistencia:cierre):
| - Corta conexiones activas a las 13:00.
| - Genera faltas de practicantes sin asistencia (idempotente).
| - Respeta fines de semana y el periodo de prácticas.
*/

it('cierra la conexión activa a las 13:00 con el comando de cierre', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 12, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => today()->toDateString(),
        'tipo' => 'asistio',
    ]);

    app(AttendanceDiscordService::class)
        ->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    $this->artisan('asistencia:cierre')->assertSuccessful();

    $actualizada = $asistencia->fresh();

    expect($actualizada->tiempo_trabajado)->toBe('01:00:00')
        ->and($actualizada->hora_fin)->toBe('13:00:00')
        ->and(Cache::has("attendance:{$asistencia->id}:connection"))->toBeFalse();
});

it('el cierre es idempotente: no duplica horas ni faltas', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 12, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => today()->toDateString(),
        'tipo' => 'asistio',
    ]);

    app(AttendanceDiscordService::class)
        ->processPresence($usuario->discord_id, 'online');

    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    $this->artisan('asistencia:cierre');
    $this->artisan('asistencia:cierre');

    expect($asistencia->fresh()->tiempo_trabajado)->toBe('01:00:00')
        ->and(Asistencia::where('usuario_id', $usuario->id)->count())->toBe(1);
});

it('genera la falta de los practicantes que no registraron asistencia', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    $sinAsistencia = crearPracticante();
    $conAsistencia = crearPracticante(['discord_id' => '987654321098765432']);

    Asistencia::create([
        'usuario_id' => $conAsistencia->id,
        'fecha' => today()->toDateString(),
        'tipo' => 'asistio',
        'tiempo_trabajado' => '05:00:00',
    ]);

    $this->artisan('asistencia:cierre')->assertSuccessful();

    $falta = Asistencia::where('usuario_id', $sinAsistencia->id)->first();

    expect($falta)->not->toBeNull()
        ->and($falta->tipo->value)->toBe('falto')
        ->and(Asistencia::where('usuario_id', $conAsistencia->id)->count())->toBe(1);
});

it('no genera faltas los fines de semana', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 21, 13, 5, 0, 'America/Lima'));

    crearPracticante();

    $this->artisan('asistencia:cierre')->assertSuccessful();

    expect(Asistencia::count())->toBe(0);
});

it('no genera faltas fuera del periodo de prácticas', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    // Aún no inicia sus prácticas
    crearPracticante(['fecha_inicio' => '2026-11-20', 'fecha_fin' => null]);
    // Ya finalizó sus prácticas
    crearPracticante(['discord_id' => '888888888888888888', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-11-14']);

    $this->artisan('asistencia:cierre')->assertSuccessful();

    expect(Asistencia::count())->toBe(0);
});

it('notifica a los administradores las faltas nuevas', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    $admin = crearUsuario([], 'admin');
    crearPracticante();

    $this->artisan('asistencia:cierre')->assertSuccessful();

    expect($admin->fresh()->notifications()->count())->toBe(1);

    // Segunda ejecución: sin faltas nuevas, sin notificaciones duplicadas.
    $this->artisan('asistencia:cierre');

    expect($admin->fresh()->notifications()->count())->toBe(1);
});

it('no crea faltas para administradores', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    crearUsuario([], 'admin');

    $this->artisan('asistencia:cierre')->assertSuccessful();

    expect(Asistencia::count())->toBe(0);
});

it('no modifica asistencias justificadas en el cierre', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 13, 5, 0, 'America/Lima'));

    $usuario = crearPracticante();
    $asistencia = Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => today()->toDateString(),
        'tipo' => 'justificado',
    ]);

    $this->artisan('asistencia:cierre')->assertSuccessful();

    expect($asistencia->fresh()->tipo->value)->toBe('justificado')
        ->and($asistencia->fresh()->tiempo_trabajado)->toBeNull();
});
