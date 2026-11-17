<?php

use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/*
| Escenarios de revisión de evidencias y justificaciones:
| - Tardanza conservada al aprobar evidencia o justificación.
| - Justificación de falta aprobada -> justificado.
| - Rechazo -> falta.
| - Un practicante no puede tocar registros de otro.
*/

function crearJornada(User $usuario, string $tipo = 'tardanza'): Asistencia
{
    return Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => '2026-11-16',
        'tipo' => $tipo,
        'tiempo_trabajado' => '04:00:00',
    ]);
}

function crearAdmin(): User
{
    return crearUsuario([], 'admin');
}

it('aprobar la evidencia de una tardanza NO la convierte en asistio', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'tardanza');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Avance del módulo de reportes',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.asistencias.validar', $asistencia), ['observacion' => 'Ok'])
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('tardanza')
        ->and($asistencia->fresh()->justificaciones()->where('tipo', 'evidencia')->first()->estado)->toBe('aprobada');
});

it('aprobar la evidencia de una asistencia pendiente la marca como asistio', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'pendiente');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Avance del día',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.asistencias.validar', $asistencia))
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('asistio');
});

it('rechazar la evidencia registra la falta definitiva', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'tardanza');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Avance del día',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.asistencias.rechazar', $asistencia), ['observacion' => 'No corresponde'])
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('falto');
});

it('el practicante presenta justificación de tardanza', function () {
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'tardanza');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.justificaciones.store', $asistencia), [
            'evidencia' => UploadedFile::fake()->image('sustento.png'),
            'motivo' => 'Retraso del transporte',
        ]);

    $justificacion = $asistencia->fresh()->justificaciones()->where('tipo', 'justificacion')->first();

    expect($justificacion)->not->toBeNull()
        ->and($justificacion->estado)->toBe('pendiente')
        ->and($justificacion->descripcion)->toBe('Retraso del transporte');
});

it('el practicante no puede justificar una asistencia normal', function () {
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'asistio');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.justificaciones.store', $asistencia), [
            'evidencia' => UploadedFile::fake()->image('sustento.png'),
            'motivo' => 'No aplica',
        ])
        ->assertSessionHas('error');

    expect($asistencia->fresh()->justificaciones()->count())->toBe(0);
});

it('otro practicante no puede enviar evidencia ajena', function () {
    $dueno = crearPracticante();
    $otro = crearPracticante(['discord_id' => '999999999999999999']);
    $asistencia = crearJornada($dueno, 'tardanza');

    Storage::fake('public');
    $this->actingAs($otro)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Evidencia ajena',
        ]);

    expect($asistencia->fresh()->justificaciones()->count())->toBe(0);
});

it('aprobar la justificación de una tardanza mantiene la tardanza', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'tardanza');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.justificaciones.store', $asistencia), [
            'evidencia' => UploadedFile::fake()->image('sustento.png'),
            'motivo' => 'Retraso del transporte',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.justificaciones.validar', $asistencia), ['observacion' => 'Aceptado'])
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('tardanza')
        ->and($asistencia->fresh()->justificaciones()->where('tipo', 'justificacion')->first()->estado)->toBe('aprobada');
});

it('aprobar la justificación de una falta aplica justificado', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'falto');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.justificaciones.store', $asistencia), [
            'evidencia' => UploadedFile::fake()->image('sustento.png'),
            'motivo' => 'Emergencia médica',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.justificaciones.validar', $asistencia))
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('justificado');
});

it('rechazar la justificación de una tardanza mantiene la tardanza', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'tardanza');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.justificaciones.store', $asistencia), [
            'evidencia' => UploadedFile::fake()->image('sustento.png'),
            'motivo' => 'Retraso',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.justificaciones.rechazar', $asistencia), ['observacion' => 'Sin sustento'])
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('tardanza')
        ->and($asistencia->fresh()->justificaciones()->where('tipo', 'justificacion')->first()->estado)->toBe('rechazada');
});

it('rechazar la justificación de una falta mantiene la falta', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'falto');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.justificaciones.store', $asistencia), [
            'evidencia' => UploadedFile::fake()->image('sustento.png'),
            'motivo' => 'Motivo',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.justificaciones.rechazar', $asistencia), ['observacion' => 'Sin sustento'])
        ->assertOk();

    expect($asistencia->fresh()->tipo->value)->toBe('falto');
});

it('cada revisión registra una entrada de auditoría', function () {
    $admin = crearAdmin();
    $practicante = crearPracticante();
    $asistencia = crearJornada($practicante, 'tardanza');

    Storage::fake('public');
    $this->actingAs($practicante)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Avance',
        ]);

    $this->actingAs($admin)
        ->post(route('admin.asistencias.validar', $asistencia))
        ->assertOk();

    $auditoria = DB::table('activity_log')
        ->where('log_name', 'auditoria')
        ->where('event', 'evidencia_validada')
        ->get();

    expect($auditoria->count())->toBe(1)
        ->and($auditoria->first()->causer_id)->toBe($admin->id);
});
