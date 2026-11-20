<?php

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use App\Services\ReporteAuditoriaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

function crearAdminAuditoria(string $nombre, string $apellidos): User
{
    return crearUsuario([
        'nombre' => $nombre,
        'apellidos' => $apellidos,
    ], 'admin');
}

function crearJornadaAuditoria(User $practicante, string $tipo = 'falto', string $fecha = '2026-11-10'): Asistencia
{
    return Asistencia::create([
        'usuario_id' => $practicante->id,
        'fecha' => $fecha,
        'tipo' => $tipo,
    ]);
}

function crearJustificacionPendiente(Asistencia $asistencia, string $motivo): Justificacion
{
    return $asistencia->justificaciones()->create([
        'fecha' => $asistencia->fecha,
        'archivo_path' => 'justificaciones/prueba.pdf',
        'descripcion' => $motivo,
        'tipo' => 'justificacion',
        'estado' => 'pendiente',
    ]);
}

function aceptarJustificacionAuditoria($prueba, Asistencia $asistencia, User $admin, string $observacion = 'Aceptada'): void
{
    $prueba->actingAs($admin)
        ->post(route('admin.justificaciones.validar', $asistencia), [
            'observacion' => $observacion,
        ])
        ->assertOk();
}

function rechazarJustificacionAuditoria($prueba, Asistencia $asistencia, User $admin, string $observacion = 'Rechazada'): void
{
    $prueba->actingAs($admin)
        ->post(route('admin.justificaciones.rechazar', $asistencia), [
            'observacion' => $observacion,
        ])
        ->assertOk();
}

function auditoriasJustificaciones()
{
    return DB::table('activity_log')->where('log_name', 'justificaciones');
}

function registrarAuditoriaHistorica(Asistencia $asistencia, Justificacion $justificacion, User $admin, string $evento): void
{
    $aprobada = $evento === 'justificacion_aprobada';

    DB::table('activity_log')->insert([
        'log_name' => 'auditoria',
        'description' => $aprobada
            ? 'El administrador aprobó la justificación de la falta.'
            : 'El administrador rechazó la justificación. La asistencia quedó como falta.',
        'subject_type' => Asistencia::class,
        'subject_id' => $asistencia->id,
        'event' => $evento,
        'causer_type' => User::class,
        'causer_id' => $admin->id,
        'attribute_changes' => json_encode([
            'datos_anteriores' => ['estado' => 'pendiente'],
            'datos_nuevos' => ['estado' => $aprobada ? 'aprobada' : 'rechazada'],
        ]),
        'properties' => json_encode([
            'tipo' => 'justificacion',
            'justificacion_id' => $justificacion->id,
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function registrarActividadEvidenciaHistorica(Asistencia $asistencia, User $admin, string $evento): void
{
    $rechazada = $evento === 'evidencia_rechazada';

    DB::table('activity_log')->insert([
        'log_name' => 'auditoria',
        'description' => 'Auditoría de evidencia.',
        'subject_type' => Asistencia::class,
        'subject_id' => $asistencia->id,
        'event' => $evento,
        'causer_type' => User::class,
        'causer_id' => $admin->id,
        'attribute_changes' => json_encode([
            'datos_anteriores' => ['estado' => 'pendiente'],
            'datos_nuevos' => ['estado' => $rechazada ? 'rechazada' : 'aprobada'],
        ]),
        'properties' => json_encode([
            'tipo' => 'evidencia',
            'justificacion_id' => $asistencia->justificaciones()->value('id'),
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function registrarActividadDiariaHistorica(Asistencia $asistencia, User $admin, string $area = 'Diseño'): void
{
    DB::table('activity_log')->insert([
        'log_name' => 'actividades',
        'description' => 'Actividad registrada.',
        'subject_type' => Asistencia::class,
        'subject_id' => $asistencia->id,
        'event' => 'actividad_diaria',
        'causer_type' => User::class,
        'causer_id' => $admin->id,
        'attribute_changes' => json_encode([]),
        'properties' => json_encode(['area' => $area]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('registra una actividad al aceptar una justificación', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $asistencia = crearJornadaAuditoria($practicante);
    $justificacion = crearJustificacionPendiente($asistencia, 'Motivo médico');

    aceptarJustificacionAuditoria($this, $asistencia, $admin, 'Sustento válido');

    $actividad = auditoriasJustificaciones()->firstOrFail();
    $propiedades = json_decode($actividad->properties, true);

    expect($actividad->log_name)->toBe('justificaciones')
        ->and($actividad->event)->toBe('accepted')
        ->and($actividad->description)->toBe('Justificación aceptada.')
        ->and($actividad->subject_type)->toBe(Justificacion::class)
        ->and($actividad->subject_id)->toBe($justificacion->id)
        ->and($actividad->causer_type)->toBe(User::class)
        ->and($actividad->causer_id)->toBe($admin->id)
        ->and($propiedades['estado_anterior'])->toBe('pendiente')
        ->and($propiedades['estado_nuevo'])->toBe('aprobada')
        ->and($propiedades['observacion'])->toBe('Sustento válido')
        ->and($propiedades['asistencia_id'])->toBe($asistencia->id)
        ->and($justificacion->fresh()->estado)->toBe('aprobada');
});

it('registra otra actividad al rechazar una justificación', function () {
    $admin = crearAdminAuditoria('Bruno', 'Berrio');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $asistencia = crearJornadaAuditoria($practicante);
    $justificacion = crearJustificacionPendiente($asistencia, 'Motivo laboral');

    rechazarJustificacionAuditoria($this, $asistencia, $admin, 'No procede');

    $actividad = auditoriasJustificaciones()->firstOrFail();
    $propiedades = json_decode($actividad->properties, true);

    expect($actividad->log_name)->toBe('justificaciones')
        ->and($actividad->event)->toBe('rejected')
        ->and($actividad->description)->toBe('Justificación rechazada.')
        ->and($actividad->subject_type)->toBe(Justificacion::class)
        ->and($actividad->subject_id)->toBe($justificacion->id)
        ->and($actividad->causer_id)->toBe($admin->id)
        ->and($propiedades['estado_anterior'])->toBe('pendiente')
        ->and($propiedades['estado_nuevo'])->toBe('rechazada')
        ->and($propiedades['observacion'])->toBe('No procede')
        ->and($propiedades['asistencia_id'])->toBe($asistencia->id)
        ->and($justificacion->fresh()->estado)->toBe('rechazada');
});

it('obtiene el causer del usuario autenticado e ignora un causer enviado por el frontend', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');
    $otroAdmin = crearAdminAuditoria('Bruno', 'Berrio');
    $practicante = crearPracticante();
    $asistencia = crearJornadaAuditoria($practicante);
    crearJustificacionPendiente($asistencia, 'Motivo médico');

    $this->actingAs($admin)
        ->post(route('admin.justificaciones.validar', $asistencia), [
            'observacion' => 'Aceptada',
            'causer_id' => $otroAdmin->id,
        ])
        ->assertOk();

    $actividad = auditoriasJustificaciones()->firstOrFail();

    expect($actividad->causer_id)->toBe($admin->id)
        ->and($actividad->causer_id)->not->toBe($otroAdmin->id);
});

it('el reporte muestra practicante, fecha de asistencia, administrador, acción y observación', function () {
    $adminA = crearAdminAuditoria('Ana', 'Acevedo');
    $adminB = crearAdminAuditoria('Bruno', 'Berrio');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);

    $asistenciaA = crearJornadaAuditoria($practicante, 'falto', '2026-11-10');
    crearJustificacionPendiente($asistenciaA, 'Motivo médico');
    aceptarJustificacionAuditoria($this, $asistenciaA, $adminA, 'Procede');

    $asistenciaB = crearJornadaAuditoria($practicante, 'falto', '2026-11-11');
    crearJustificacionPendiente($asistenciaB, 'Motivo personal');
    rechazarJustificacionAuditoria($this, $asistenciaB, $adminB, 'Sin sustento');

    $this->actingAs($adminA)
        ->getJson(route('admin.reportes.filtrar.auditorias'))
        ->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonFragment([
            'practicante' => 'Carla Castro',
            'justificacion' => 'Motivo médico',
            'fecha_asistencia' => '2026-11-10',
            'administrador' => 'Ana Acevedo',
            'accion' => 'aceptada',
            'observacion' => 'Procede',
        ])
        ->assertJsonFragment([
            'practicante' => 'Carla Castro',
            'justificacion' => 'Motivo personal',
            'fecha_asistencia' => '2026-11-11',
            'administrador' => 'Bruno Berrio',
            'accion' => 'rechazada',
            'observacion' => 'Sin sustento',
        ]);
});

it('aplica filtros de fecha, practicante, administrador y acción en la consulta', function () {
    $adminA = crearAdminAuditoria('Ana', 'Acevedo');
    $adminB = crearAdminAuditoria('Bruno', 'Berrio');
    $practicanteA = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $practicanteB = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'discord_id' => '999999999999999999',
    ]);

    $asistenciaA = crearJornadaAuditoria($practicanteA);
    crearJustificacionPendiente($asistenciaA, 'Motivo médico');
    aceptarJustificacionAuditoria($this, $asistenciaA, $adminA, 'Procede');

    $asistenciaB = crearJornadaAuditoria($practicanteB);
    crearJustificacionPendiente($asistenciaB, 'Motivo personal');
    rechazarJustificacionAuditoria($this, $asistenciaB, $adminB, 'Sin sustento');

    $base = [
        'fechaDesde' => null,
        'fechaHasta' => null,
        'practicante' => null,
        'administrador' => null,
        'accion' => null,
    ];

    $servicio = app(ReporteAuditoriaService::class);

    $porAccion = $servicio->obtenerTodos([...$base, 'accion' => 'rechazada']);
    $porAdministrador = $servicio->obtenerTodos([...$base, 'administrador' => $adminA->id]);
    $porPracticante = $servicio->obtenerTodos([...$base, 'practicante' => $practicanteB->id]);
    $porFecha = $servicio->obtenerTodos([
        ...$base,
        'fechaDesde' => now()->toDateString(),
        'fechaHasta' => now()->toDateString(),
    ]);
    $sinResultados = $servicio->obtenerTodos([...$base, 'fechaDesde' => now()->addDay()->toDateString()]);

    expect($porAccion)->toHaveCount(1)
        ->and($porAccion->first()->accion)->toBe('rechazada')
        ->and($porAdministrador)->toHaveCount(1)
        ->and($porAdministrador->first()->administrador_id)->toBe($adminA->id)
        ->and($porPracticante)->toHaveCount(1)
        ->and($porFecha)->toHaveCount(2)
        ->and($sinResultados)->toHaveCount(0);

    $this->actingAs($adminA)
        ->getJson(route('admin.reportes.filtrar.auditorias', [
            'estadoAsistencia' => 'rechazada',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.accion', 'rechazada');
});

it('rechaza filtros con valores no permitidos', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');

    $this->actingAs($admin)
        ->get(route('admin.reportes.filtrar.auditorias', [
            'estadoAsistencia' => 'invalida',
        ]))
        ->assertSessionHasErrors('estadoAsistencia');

    $this->actingAs($admin)
        ->getJson(route('admin.reportes.filtrar.auditorias', [
            'estadoAsistencia' => 'actividad',
        ]))
        ->assertOk();
});

it('la paginación funciona y conserva los filtros aplicados', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');
    $otroAdmin = crearAdminAuditoria('Bruno', 'Berrio');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);

    for ($i = 1; $i <= 17; $i++) {
        $asistencia = crearJornadaAuditoria(
            $practicante,
            'falto',
            sprintf('2026-11-%02d', $i)
        );
        crearJustificacionPendiente($asistencia, 'Motivo '.$i);
        aceptarJustificacionAuditoria($this, $asistencia, $admin, 'Aceptada '.$i);
    }

    $asistenciaRechazada = crearJornadaAuditoria($practicante, 'falto', '2026-11-20');
    crearJustificacionPendiente($asistenciaRechazada, 'Motivo rechazado');
    rechazarJustificacionAuditoria($this, $asistenciaRechazada, $otroAdmin, 'Rechazada');

    $respuesta = $this->actingAs($admin)
        ->getJson(route('admin.reportes.filtrar.auditorias', [
            'practicante' => $practicante->id,
            'estadoAsistencia' => 'aceptada',
            'per_page' => 5,
            'page' => 2,
        ]))
        ->assertOk()
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.last_page', 4)
        ->assertJsonPath('pagination.per_page', 5)
        ->assertJsonPath('pagination.total', 17)
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.accion', 'aceptada');

    expect($respuesta->json('pagination.links.0.url'))
        ->toContain('practicante='.$practicante->id)
        ->toContain('estadoAsistencia=aceptada')
        ->toContain('per_page=5');
});

it('una nueva revisión crea una actividad nueva sin borrar el historial anterior', function () {
    $adminA = crearAdminAuditoria('Ana', 'Acevedo');
    $adminB = crearAdminAuditoria('Bruno', 'Berrio');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $asistencia = crearJornadaAuditoria($practicante);
    $justificacion = crearJustificacionPendiente($asistencia, 'Motivo médico');

    aceptarJustificacionAuditoria($this, $asistencia, $adminA, 'Primera revisión');
    rechazarJustificacionAuditoria($this, $asistencia, $adminB, 'Segunda revisión');

    $actividades = auditoriasJustificaciones()
        ->where('subject_id', $justificacion->id)
        ->orderBy('id')
        ->get();

    expect($actividades)->toHaveCount(2)
        ->and($actividades[0]->event)->toBe('accepted')
        ->and($actividades[0]->causer_id)->toBe($adminA->id)
        ->and($actividades[1]->event)->toBe('rejected')
        ->and($actividades[1]->causer_id)->toBe($adminB->id)
        ->and(json_decode($actividades[1]->properties, true)['estado_anterior'])->toBe('aprobada')
        ->and($justificacion->fresh()->estado)->toBe('rechazada');
});

it('muestra No disponible cuando una actividad no tiene información relacionada', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');

    activity('justificaciones')
        ->byAnonymous()
        ->event('accepted')
        ->log('Justificación aceptada.');

    $this->actingAs($admin)
        ->getJson(route('admin.reportes.filtrar.auditorias'))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.practicante', 'No disponible')
        ->assertJsonPath('data.0.justificacion', 'No disponible')
        ->assertJsonPath('data.0.administrador', 'No disponible')
        ->assertJsonPath('data.0.observacion', 'No disponible')
        ->assertJsonPath('data.0.fecha_asistencia', 'No disponible');
});

it('un practicante no puede acceder al reporte ni a sus exportaciones', function () {
    $practicante = crearPracticante();

    $this->actingAs($practicante)
        ->getJson(route('admin.reportes.filtrar.auditorias'))
        ->assertForbidden();

    $this->actingAs($practicante)
        ->get(route('admin.reportes.exportar.auditorias.excel'))
        ->assertForbidden();

    $this->actingAs($practicante)
        ->get(route('admin.reportes.exportar.auditorias.pdf'))
        ->assertForbidden();
});

it('la exportación a Excel respeta los filtros aplicados', function () {
    $adminA = crearAdminAuditoria('Ana', 'Acevedo');
    $adminB = crearAdminAuditoria('Bruno', 'Berrio');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);

    $asistenciaA = crearJornadaAuditoria($practicante);
    crearJustificacionPendiente($asistenciaA, 'Motivo médico');
    aceptarJustificacionAuditoria($this, $asistenciaA, $adminA, 'Procede');

    $asistenciaB = crearJornadaAuditoria($practicante, 'falto', '2026-11-11');
    crearJustificacionPendiente($asistenciaB, 'Motivo personal');
    rechazarJustificacionAuditoria($this, $asistenciaB, $adminB, 'Sin sustento');

    $filtroAceptadas = ['estadoAsistencia' => 'aceptada'];

    $this->actingAs($adminA)
        ->get(route('admin.reportes.exportar.auditorias.pdf', $filtroAceptadas))
        ->assertOk();

    $respuesta = $this->actingAs($adminA)
        ->get(route('admin.reportes.exportar.auditorias.excel', $filtroAceptadas))
        ->assertOk();

    $archivo = sys_get_temp_dir().DIRECTORY_SEPARATOR.'auditoria-justificaciones-filtrada.xlsx';
    file_put_contents($archivo, $respuesta->streamedContent());

    try {
        $filas = Excel::toCollection(null, $archivo)->first();
        $contenido = $filas->flatten()->map(fn ($celda) => (string) $celda)->implode('|');

        expect($contenido)
            ->toContain('Ana Acevedo')
            ->toContain('Aceptada')
            ->toContain('Motivo médico')
            ->and($contenido)->not->toContain('Bruno Berrio')
            ->and($contenido)->not->toContain('Motivo personal');
    } finally {
        @unlink($archivo);
    }
});

it('los reportes existentes siguen funcionando', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');

    $this->actingAs($admin)->get(route('admin.reportes.index'))->assertOk();
    $this->actingAs($admin)->getJson(route('admin.reportes.filtrar'))->assertOk();
    $this->actingAs($admin)->getJson(route('admin.reportes.filtrar.evidencias'))->assertOk();
    $this->actingAs($admin)->getJson(route('admin.reportes.filtrar.auditorias'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reportes.exportar.pdf'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reportes.exportar.excel'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reportes.exportar.evidencias.pdf'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reportes.exportar.evidencias.excel'))->assertOk();
});

it('incluye decisiones históricas reales sin fabricar auditorías', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $asistencia = crearJornadaAuditoria($practicante);
    $justificacion = crearJustificacionPendiente($asistencia, 'Motivo histórico');
    $justificacion->update(['estado' => 'aprobada']);

    registrarAuditoriaHistorica($asistencia, $justificacion, $admin, 'justificacion_aprobada');
    registrarActividadEvidenciaHistorica($asistencia, $admin, 'evidencia_validada');

    $totalAntes = DB::table('activity_log')->count();

    $registros = app(ReporteAuditoriaService::class)->obtenerTodos([
        'fechaDesde' => null,
        'fechaHasta' => null,
        'practicante' => null,
        'administrador' => null,
        'accion' => null,
    ]);

    $porEvento = $registros->keyBy('event');

    expect($registros)->toHaveCount(2)
        ->and($porEvento['justificacion_aprobada']->practicante)->toBe('Carla Castro')
        ->and($porEvento['justificacion_aprobada']->justificacion)->toBe('Motivo histórico')
        ->and($porEvento['justificacion_aprobada']->fecha_asistencia)->toBe($asistencia->fecha)
        ->and($porEvento['justificacion_aprobada']->administrador)->toBe('Ana Acevedo')
        ->and($porEvento['justificacion_aprobada']->accion)->toBe('aceptada')
        ->and($porEvento['justificacion_aprobada']->observacion)->toBe('No disponible')
        ->and($porEvento['justificacion_aprobada']->estado_anterior)->toBe('pendiente')
        ->and($porEvento['justificacion_aprobada']->estado_nuevo)->toBe('aprobada')
        ->and($porEvento['justificacion_aprobada']->asistencia_id)->toBe($asistencia->id)
        ->and($porEvento['evidencia_validada']->log_name)->toBe('auditoria')
        ->and($porEvento['evidencia_validada']->accion)->toBe('aceptada')
        ->and($porEvento['evidencia_validada']->estado_anterior)->toBe('pendiente')
        ->and($porEvento['evidencia_validada']->estado_nuevo)->toBe('aprobada')
        ->and(DB::table('activity_log')->count())->toBe($totalAntes);
});

it('aplica los filtros también a las decisiones históricas', function () {
    $adminA = crearAdminAuditoria('Ana', 'Acevedo');
    $adminB = crearAdminAuditoria('Bruno', 'Berrio');
    $practicanteA = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $practicanteB = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'discord_id' => '999999999999999999',
    ]);

    $asistenciaA = crearJornadaAuditoria($practicanteA);
    $justificacionA = crearJustificacionPendiente($asistenciaA, 'Motivo histórico A');
    $justificacionA->update(['estado' => 'aprobada']);
    registrarAuditoriaHistorica($asistenciaA, $justificacionA, $adminA, 'justificacion_aprobada');

    $asistenciaB = crearJornadaAuditoria($practicanteB);
    $justificacionB = crearJustificacionPendiente($asistenciaB, 'Motivo histórico B');
    $justificacionB->update(['estado' => 'rechazada']);
    registrarAuditoriaHistorica($asistenciaB, $justificacionB, $adminB, 'justificacion_rechazada');

    $base = [
        'fechaDesde' => null,
        'fechaHasta' => null,
        'practicante' => null,
        'administrador' => null,
        'accion' => null,
    ];

    $servicio = app(ReporteAuditoriaService::class);

    $porAccion = $servicio->obtenerTodos([...$base, 'accion' => 'rechazada']);
    $porAdministrador = $servicio->obtenerTodos([...$base, 'administrador' => $adminA->id]);
    $porPracticante = $servicio->obtenerTodos([...$base, 'practicante' => $practicanteB->id]);
    $porFecha = $servicio->obtenerTodos([
        ...$base,
        'fechaDesde' => now()->toDateString(),
        'fechaHasta' => now()->toDateString(),
    ]);
    $sinResultados = $servicio->obtenerTodos([...$base, 'fechaDesde' => now()->addDay()->toDateString()]);

    expect($porAccion)->toHaveCount(1)
        ->and($porAccion->first()->accion)->toBe('rechazada')
        ->and($porAdministrador)->toHaveCount(1)
        ->and($porAdministrador->first()->administrador)->toBe('Ana Acevedo')
        ->and($porPracticante)->toHaveCount(1)
        ->and($porPracticante->first()->practicante)->toBe('Diego Díaz')
        ->and($porFecha)->toHaveCount(2)
        ->and($sinResultados)->toHaveCount(0);

    $this->actingAs($adminA)
        ->getJson(route('admin.reportes.filtrar.auditorias', [
            'estadoAsistencia' => 'rechazada',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.accion', 'rechazada')
        ->assertJsonPath('data.0.justificacion', 'Motivo histórico B');
});

it('incluye también las auditorías de evidencia en el reporte general', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');
    $practicante = crearPracticante();
    $asistencia = crearJornadaAuditoria($practicante);

    registrarActividadEvidenciaHistorica($asistencia, $admin, 'evidencia_validada');
    registrarActividadEvidenciaHistorica($asistencia, $admin, 'evidencia_rechazada');

    $registros = app(ReporteAuditoriaService::class)->obtenerTodos([
        'fechaDesde' => null,
        'fechaHasta' => null,
        'practicante' => null,
        'administrador' => null,
        'accion' => null,
    ]);

    $porEvento = $registros->keyBy('event');

    expect($registros)->toHaveCount(2)
        ->and($porEvento['evidencia_validada']->accion)->toBe('aceptada')
        ->and($porEvento['evidencia_validada']->estado_anterior)->toBe('pendiente')
        ->and($porEvento['evidencia_validada']->estado_nuevo)->toBe('aprobada')
        ->and($porEvento['evidencia_rechazada']->accion)->toBe('rechazada')
        ->and($porEvento['evidencia_rechazada']->estado_nuevo)->toBe('rechazada');
});

it('incluye las actividades diarias y permite filtrar por actividad', function () {
    $admin = crearAdminAuditoria('Ana', 'Acevedo');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $asistencia = crearJornadaAuditoria($practicante);

    registrarActividadDiariaHistorica($asistencia, $admin);

    $base = [
        'fechaDesde' => null,
        'fechaHasta' => null,
        'practicante' => null,
        'administrador' => null,
        'accion' => null,
    ];

    $servicio = app(ReporteAuditoriaService::class);
    $registros = $servicio->obtenerTodos($base);

    expect($registros)->toHaveCount(1)
        ->and($registros->first()->log_name)->toBe('actividades')
        ->and($registros->first()->event)->toBe('actividad_diaria')
        ->and($registros->first()->accion)->toBe('actividad')
        ->and($registros->first()->practicante)->toBe('Carla Castro')
        ->and($registros->first()->fecha_asistencia)->toBe($asistencia->fecha)
        ->and($registros->first()->administrador)->toBe('Ana Acevedo')
        ->and($registros->first()->justificacion)->toBe('No disponible')
        ->and($registros->first()->observacion)->toBe('No disponible');

    expect($servicio->obtenerTodos([...$base, 'accion' => 'actividad']))->toHaveCount(1)
        ->and($servicio->obtenerTodos([...$base, 'accion' => 'aceptada']))->toHaveCount(0);

    $this->actingAs($admin)
        ->getJson(route('admin.reportes.filtrar.auditorias', [
            'estadoAsistencia' => 'actividad',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.accion', 'actividad');
});

it('no existe una segunda tabla ni modelo de auditoría', function () {
    expect(Schema::hasTable('auditoria_justificaciones'))->toBe(false)
        ->and(class_exists(AuditoriaJustificacion::class))->toBe(false)
        ->and(glob(database_path('migrations/*auditoria_justificaciones*')))->toBe([]);
});
