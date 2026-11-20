<?php

use App\Enum\EstadoAsistenciaEnum;
use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
});

function crearAsistenciaValidacion(
    User $practicante,
    EstadoAsistenciaEnum $estado,
    ?string $horaInicio = null,
    string $fecha = '2026-11-17'
): Asistencia {
    return Asistencia::create([
        'usuario_id' => $practicante->id,
        'fecha' => $fecha,
        'hora_inicio' => $horaInicio,
        'tipo' => $estado,
    ]);
}

it('crea una falta al practicante sin registro a las 09:00', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $practicante = crearPracticante();

    $this->artisan('asistencia:validar-ingresos')
        ->expectsOutputToContain('Faltas creadas: 1')
        ->expectsOutputToContain('Pendientes convertidos en faltas: 0')
        ->assertSuccessful();

    $asistencia = Asistencia::where('usuario_id', $practicante->id)->firstOrFail();

    expect($asistencia->tipo)->toBe(EstadoAsistenciaEnum::FALTO)
        ->and($asistencia->fecha)->toBe('2026-11-17')
        ->and($asistencia->hora_inicio)->toBeNull();
});

it('convierte en falta una asistencia pendiente sin hora de inicio', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $practicante = crearPracticante();
    $asistencia = crearAsistenciaValidacion($practicante, EstadoAsistenciaEnum::PENDIENTE);

    $this->artisan('asistencia:validar-ingresos')
        ->expectsOutputToContain('Faltas creadas: 0')
        ->expectsOutputToContain('Pendientes convertidos en faltas: 1')
        ->assertSuccessful();

    expect($asistencia->fresh()->tipo)->toBe(EstadoAsistenciaEnum::FALTO);
});

it('conserva una asistencia pendiente con hora de inicio registrada', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $practicante = crearPracticante();
    $asistencia = crearAsistenciaValidacion(
        $practicante,
        EstadoAsistenciaEnum::PENDIENTE,
        '08:55:00'
    );

    $this->artisan('asistencia:validar-ingresos')->assertSuccessful();

    expect($asistencia->fresh()->tipo)->toBe(EstadoAsistenciaEnum::PENDIENTE)
        ->and($asistencia->fresh()->hora_inicio)->toBe('08:55:00');
});

it('conserva una asistencia asistio con hora de inicio', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $practicante = crearPracticante();
    $asistencia = crearAsistenciaValidacion(
        $practicante,
        EstadoAsistenciaEnum::ASISTIO,
        '08:50:00'
    );

    $this->artisan('asistencia:validar-ingresos')->assertSuccessful();

    expect($asistencia->fresh()->tipo)->toBe(EstadoAsistenciaEnum::ASISTIO);
});

it('conserva una asistencia tardanza con hora de inicio', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $practicante = crearPracticante();
    $asistencia = crearAsistenciaValidacion(
        $practicante,
        EstadoAsistenciaEnum::TARDANZA,
        '09:05:00'
    );

    $this->artisan('asistencia:validar-ingresos')->assertSuccessful();

    expect($asistencia->fresh()->tipo)->toBe(EstadoAsistenciaEnum::TARDANZA);
});

it('conserva las asistencias justificadas y las ya marcadas como falta', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $justificado = crearPracticante();
    $falta = crearPracticante(['discord_id' => '987654321098765432']);

    $asistenciaJustificada = crearAsistenciaValidacion(
        $justificado,
        EstadoAsistenciaEnum::JUSTIFICADO
    );
    $asistenciaFalta = crearAsistenciaValidacion($falta, EstadoAsistenciaEnum::FALTO);

    $this->artisan('asistencia:validar-ingresos')->assertSuccessful();

    expect($asistenciaJustificada->fresh()->tipo)->toBe(EstadoAsistenciaEnum::JUSTIFICADO)
        ->and($asistenciaFalta->fresh()->tipo)->toBe(EstadoAsistenciaEnum::FALTO);
});

it('es idempotente y no repite notificaciones a los administradores', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    $admin = crearUsuario([], 'admin');
    $practicante = crearPracticante();

    $this->artisan('asistencia:validar-ingresos')
        ->expectsOutputToContain('Faltas creadas: 1')
        ->assertSuccessful();

    expect($admin->fresh()->notifications()->count())->toBe(1);

    $this->artisan('asistencia:validar-ingresos')
        ->expectsOutputToContain('Faltas creadas: 0')
        ->expectsOutputToContain('Pendientes convertidos en faltas: 0')
        ->assertSuccessful();

    // El cierre de las 13:00 no debe volver a notificar la misma falta.
    $this->artisan('asistencia:cierre')->assertSuccessful();

    expect(Asistencia::where('usuario_id', $practicante->id)->count())->toBe(1)
        ->and($admin->fresh()->notifications()->count())->toBe(1);
});

it('no procesa practicantes fuera de su periodo de prácticas ni inactivos', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 17, 9, 0, 0, 'America/Lima'));

    crearPracticante(['fecha_inicio' => '2026-11-18', 'fecha_fin' => null]);
    crearPracticante([
        'discord_id' => '987654321098765432',
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-11-14',
    ]);
    crearPracticante([
        'discord_id' => '888888888888888888',
        'estado' => false,
    ]);

    $this->artisan('asistencia:validar-ingresos')
        ->expectsOutputToContain('Faltas creadas: 0')
        ->assertSuccessful();

    expect(Asistencia::count())->toBe(0);
});

it('permite procesar una fecha concreta con la opcion --fecha', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 20, 15, 0, 0, 'America/Lima'));

    $practicante = crearPracticante();

    $this->artisan('asistencia:validar-ingresos', ['--fecha' => '2026-11-17'])
        ->expectsOutputToContain('Validación de ingresos 2026-11-17 finalizada.')
        ->assertSuccessful();

    expect(Asistencia::firstOrFail()->fecha)->toBe('2026-11-17');
});
