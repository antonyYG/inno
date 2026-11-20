<?php

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use App\Services\ReporteAuditoriaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Maatwebsite\Excel\Facades\Excel;

function crearAdminReportes(string $nombre, string $apellidos): User
{
    return crearUsuario([
        'nombre' => $nombre,
        'apellidos' => $apellidos,
    ], 'admin');
}

function crearJornadaReportes(User $practicante, string $tipo = 'falto', string $fecha = '2026-11-10'): Asistencia
{
    return Asistencia::create([
        'usuario_id' => $practicante->id,
        'fecha' => $fecha,
        'tipo' => $tipo,
    ]);
}

function crearJustificacionReportes(Asistencia $asistencia, string $motivo): Justificacion
{
    return $asistencia->justificaciones()->create([
        'fecha' => $asistencia->fecha,
        'archivo_path' => 'justificaciones/prueba.pdf',
        'descripcion' => $motivo,
        'tipo' => 'justificacion',
        'estado' => 'pendiente',
    ]);
}

function crearEvidenciaReporte(Asistencia $asistencia, string $estado, string $fecha): Justificacion
{
    return $asistencia->justificaciones()->create([
        'fecha' => $fecha,
        'archivo_path' => 'evidencias/prueba.pdf',
        'descripcion' => 'Evidencia '.$estado,
        'tipo' => 'evidencia',
        'estado' => $estado,
        'observacion' => 'Observación '.$estado,
    ]);
}

function registrarActividadReporte(Asistencia $asistencia, User $admin, ?Justificacion $justificacion = null): void
{
    DB::table('activity_log')->insert([
        'log_name' => 'justificaciones',
        'description' => 'Decisión registrada.',
        'subject_type' => Justificacion::class,
        'subject_id' => $justificacion?->id,
        'event' => 'accepted',
        'causer_type' => User::class,
        'causer_id' => $admin->id,
        'attribute_changes' => json_encode([]),
        'properties' => json_encode([
            'estado_anterior' => 'pendiente',
            'estado_nuevo' => 'aprobada',
            'observacion' => 'Procede',
            'asistencia_id' => $asistencia->id,
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function registrarActividadDiariaReporte(Asistencia $asistencia, User $causer): void
{
    DB::table('activity_log')->insert([
        'log_name' => 'actividades',
        'description' => 'Actividad registrada.',
        'subject_type' => Asistencia::class,
        'subject_id' => $asistencia->id,
        'event' => 'actividad_diaria',
        'causer_type' => User::class,
        'causer_id' => $causer->id,
        'attribute_changes' => json_encode([]),
        'properties' => json_encode(['asistencia_id' => $asistencia->id]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('muestra una interfaz uniforme sin los filtros generales eliminados', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');

    $rutas = [
        route('admin.reportes.index'),
        route('admin.reportes.asistencia'),
        route('admin.reportes.evidencias'),
        route('admin.reportes.auditoria'),
    ];

    foreach ($rutas as $ruta) {
        $respuesta = $this->actingAs($admin)
            ->get($ruta)
            ->assertOk()
            ->assertViewIs('admin.reportes.index')
            ->assertSee('Filtros de búsqueda')
            ->assertSee('Tipo de reporte')
            ->assertSee('Practicante')
            ->assertSee('Fecha inicial')
            ->assertSee('Fecha final')
            ->assertSee('Buscar')
            ->assertSee('Limpiar filtros')
            ->assertSee('Resultados')
            ->assertSee('Exportar Excel')
            ->assertSee('Exportar PDF')
            ->assertDontSee('name="usuario"', false)
            ->assertDontSee('name="area"', false)
            ->assertDontSee('name="rol"', false)
            ->assertDontSee('Tipo de usuario')
            ->assertDontSee('id="busqueda"', false);

        $contenido = $respuesta->getContent();

        foreach (['practicante', 'administrador', 'fechaDesde', 'fechaHasta', 'estadoAsistencia'] as $campo) {
            expect(substr_count($contenido, 'name="'.$campo.'"'))
                ->toBe(1, "El filtro {$campo} no debe duplicarse.");
        }
    }
});

it('muestra los filtros y estados reales que corresponden a cada reporte', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');

    $asistencias = $this->actingAs($admin)
        ->get(route('admin.reportes.asistencia'))
        ->assertOk()
        ->getContent();

    $evidencias = $this->actingAs($admin)
        ->get(route('admin.reportes.evidencias'))
        ->assertOk()
        ->getContent();

    $auditorias = $this->actingAs($admin)
        ->get(route('admin.reportes.auditoria'))
        ->assertOk()
        ->getContent();

    expect($asistencias)
        ->toContain('Estados de asistencia')
        ->toContain('value="asistio"')
        ->toContain('value="tardanza"')
        ->toContain('value="falto"')
        ->toContain('value="pendiente"')
        ->toContain('value="justificado"')
        ->and($evidencias)
        ->toContain('Estados de evidencia')
        ->toContain('value="aprobada"')
        ->toContain('value="rechazada"')
        ->and($auditorias)
        ->toContain('Acciones de auditoría')
        ->toContain('Aceptada / aprobada')
        ->toContain('Actividad registrada')
        ->toContain('value="actividad"')
        ->toContain('name="administrador"');
});

it('los practicantes no pueden acceder a los reportes administrativos', function () {
    $practicante = crearPracticante();

    $rutas = [
        'admin.reportes.index',
        'admin.reportes.asistencia',
        'admin.reportes.evidencias',
        'admin.reportes.auditoria',
        'admin.reportes.filtrar',
        'admin.reportes.filtrar.evidencias',
        'admin.reportes.filtrar.auditorias',
    ];

    foreach ($rutas as $nombre) {
        $this->actingAs($practicante)
            ->get(route($nombre))
            ->assertForbidden();
    }
});

it('muestra catálogos reales de practicantes y administradores', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
        'email' => 'carla.catalogo@test.com',
    ]);

    $respuesta = $this->actingAs($admin)
        ->get(route('admin.reportes.asistencia'))
        ->assertOk();

    $datos = $respuesta->original->getData();

    expect(isset($datos['usuarios']))->toBeFalse()
        ->and(isset($datos['areas']))->toBeFalse()
        ->and(isset($datos['roles']))->toBeFalse()
        ->and(array_keys($datos['practicantes']))->toBe(['', $practicante->id])
        ->and(array_values($datos['practicantes']))->toBe(['Todos los practicantes', 'Carla Castro'])
        ->and(array_keys($datos['administradores']))->toBe(['', $admin->id])
        ->and(array_values($datos['administradores']))->toBe(['Todos los administradores', 'Ana Acevedo']);
});

it('aplica practicante, fechas y estado en el reporte de asistencias', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $otroAdmin = crearAdminReportes('Beto', 'Bravo');
    $carla = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $diego = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'discord_id' => '999999999999999999',
    ]);

    crearJornadaReportes($carla, 'asistio', '2026-11-01');
    crearJornadaReportes($carla, 'falto', '2026-11-10');
    crearJornadaReportes($diego, 'asistio', '2026-11-02');
    crearJornadaReportes($otroAdmin, 'asistio', '2026-11-02');

    $base = route('admin.reportes.filtrar');
    $consultar = fn (array $parametros): TestResponse => $this->actingAs($admin)
        ->getJson($base.'?'.http_build_query($parametros))
        ->assertOk();

    expect($consultar([])->json('total'))->toBe(4)
        ->and($consultar(['practicante' => $carla->id])->json('total'))->toBe(2)
        ->and($consultar([
            'fechaDesde' => '2026-11-10',
            'fechaHasta' => '2026-11-30',
            'estadoAsistencia' => 'falto',
        ])->json('total'))->toBe(1)
        ->and($consultar([
            'practicante' => $carla->id,
            'fechaDesde' => '2026-11-01',
            'fechaHasta' => '2026-11-30',
            'estadoAsistencia' => 'asistio',
        ])->json('total'))->toBe(1);
});

it('aplica practicante, fechas y estado de evidencia en el reporte de evidencias', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $otroAdmin = crearAdminReportes('Beto', 'Bravo');
    $carla = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $diego = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'discord_id' => '999999999999999999',
    ]);

    crearEvidenciaReporte(
        crearJornadaReportes($carla, 'falto', '2026-11-05'),
        'pendiente',
        '2026-11-05'
    );
    crearEvidenciaReporte(
        crearJornadaReportes($carla, 'falto', '2026-11-06'),
        'aprobada',
        '2026-11-06'
    );
    crearEvidenciaReporte(
        crearJornadaReportes($diego, 'falto', '2026-11-07'),
        'rechazada',
        '2026-11-07'
    );
    crearEvidenciaReporte(
        crearJornadaReportes($otroAdmin, 'falto', '2026-11-08'),
        'aprobada',
        '2026-11-08'
    );

    $base = route('admin.reportes.filtrar.evidencias');
    $consultar = fn (array $parametros): TestResponse => $this->actingAs($admin)
        ->getJson($base.'?'.http_build_query($parametros))
        ->assertOk();

    expect($consultar([])->json('total'))->toBe(4)
        ->and($consultar(['practicante' => $carla->id])->json('total'))->toBe(2)
        ->and($consultar(['estado' => 'aprobada'])->json('total'))->toBe(2)
        ->and($consultar(['fechaDesde' => '2026-11-06'])->json('total'))->toBe(3)
        ->and($consultar([
            'practicante' => $carla->id,
            'estado' => 'aprobada',
            'fechaDesde' => '2026-11-01',
            'fechaHasta' => '2026-11-30',
        ])->json('total'))->toBe(1);
});

it('aplica practicante, administrador, fechas y acción en auditoría', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $otroAdmin = crearAdminReportes('Beto', 'Bravo');
    $carla = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $diego = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'discord_id' => '999999999999999999',
    ]);

    $asistenciaCarla = crearJornadaReportes($carla, 'falto', '2026-11-05');
    $asistenciaDiego = crearJornadaReportes($diego, 'falto', '2026-11-06');
    $asistenciaBeto = crearJornadaReportes($otroAdmin, 'asistio', '2026-11-09');

    registrarActividadReporte($asistenciaCarla, $admin, crearJustificacionReportes($asistenciaCarla, 'Motivo médico'));
    registrarActividadReporte($asistenciaDiego, $otroAdmin, crearJustificacionReportes($asistenciaDiego, 'Otro motivo'));
    registrarActividadDiariaReporte($asistenciaCarla, $diego);
    registrarActividadDiariaReporte($asistenciaBeto, $admin);

    $base = route('admin.reportes.filtrar.auditorias');
    $consultar = fn (array $parametros): TestResponse => $this->actingAs($admin)
        ->getJson($base.'?'.http_build_query($parametros))
        ->assertOk();

    expect($consultar([])->json('total'))->toBe(4)
        ->and($consultar(['practicante' => $carla->id])->json('total'))->toBe(2)
        ->and($consultar(['practicante' => $diego->id])->json('total'))->toBe(1)
        ->and($consultar(['administrador' => $admin->id])->json('total'))->toBe(2)
        ->and($consultar(['administrador' => $otroAdmin->id])->json('total'))->toBe(1)
        ->and($consultar(['accion' => 'aceptada'])->json('total'))->toBe(2)
        ->and($consultar(['accion' => 'actividad'])->json('total'))->toBe(2)
        ->and($consultar(['fechaDesde' => '2026-12-01'])->json('total'))->toBe(0)
        ->and($consultar([
            'administrador' => $admin->id,
            'accion' => 'aceptada',
        ])->json('total'))->toBe(1)
        ->and($consultar([
            'practicante' => $carla->id,
            'administrador' => $admin->id,
        ])->json('total'))->toBe(1);
});

it('obtiene la auditoría desde activity_log con causer, subject y propiedades reales', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);

    $asistencia = crearJornadaReportes($practicante, 'falto', '2026-11-05');
    $justificacion = crearJustificacionReportes($asistencia, 'Motivo médico');
    registrarActividadReporte($asistencia, $admin, $justificacion);

    $registros = app(ReporteAuditoriaService::class)->obtenerTodos([
        'fechaDesde' => null,
        'fechaHasta' => null,
        'practicante' => null,
        'administrador' => null,
        'accion' => null,
    ]);

    expect($registros)->toHaveCount(1)
        ->and($registros->first()->log_name)->toBe('justificaciones')
        ->and($registros->first()->event)->toBe('accepted')
        ->and($registros->first()->practicante)->toBe('Carla Castro')
        ->and($registros->first()->justificacion)->toBe('Motivo médico')
        ->and($registros->first()->administrador)->toBe('Ana Acevedo')
        ->and($registros->first()->fecha_asistencia)->toBe('2026-11-05')
        ->and($registros->first()->observacion)->toBe('Procede')
        ->and($registros->first()->estado_anterior)->toBe('pendiente')
        ->and($registros->first()->estado_nuevo)->toBe('aprobada');
});

it('la paginación se aplica a todos los reportes y conserva los filtros', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $practicante = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);

    for ($i = 1; $i <= 5; $i++) {
        crearJornadaReportes($practicante, 'falto', sprintf('2026-11-%02d', $i));
    }

    $asistencias = $this->actingAs($admin)
        ->getJson(route('admin.reportes.filtrar', [
            'practicante' => $practicante->id,
            'estadoAsistencia' => 'falto',
            'per_page' => 2,
            'page' => 2,
        ]))
        ->assertOk();

    expect($asistencias->json('total'))->toBe(5)
        ->and(count($asistencias->json('data')))->toBe(2)
        ->and($asistencias->json('pagination.current_page'))->toBe(2)
        ->and($asistencias->json('pagination.prev_page_url'))->toContain('practicante='.$practicante->id)
        ->and($asistencias->json('pagination.prev_page_url'))->toContain('estadoAsistencia=falto');

    for ($i = 1; $i <= 3; $i++) {
        crearEvidenciaReporte(
            crearJornadaReportes($practicante, 'falto', sprintf('2026-10-%02d', $i)),
            'pendiente',
            sprintf('2026-10-%02d', $i)
        );
    }

    $evidencias = $this->actingAs($admin)
        ->getJson(route('admin.reportes.filtrar.evidencias', [
            'practicante' => $practicante->id,
            'estado' => 'pendiente',
            'per_page' => 2,
            'page' => 2,
        ]))
        ->assertOk();

    expect($evidencias->json('total'))->toBe(3)
        ->and(count($evidencias->json('data')))->toBe(1)
        ->and($evidencias->json('pagination.current_page'))->toBe(2)
        ->and($evidencias->json('pagination.prev_page_url'))->toContain('practicante='.$practicante->id)
        ->and($evidencias->json('pagination.prev_page_url'))->toContain('estado=pendiente');
});

it('las exportaciones de asistencias y evidencias respetan los filtros', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $carla = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
    ]);
    $diego = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'discord_id' => '999999999999999999',
    ]);

    crearJornadaReportes($carla, 'asistio', '2026-11-01');
    crearJornadaReportes($diego, 'asistio', '2026-11-01');

    $respuesta = $this->actingAs($admin)
        ->get(route('admin.reportes.exportar.excel', [
            'practicante' => $carla->id,
            'fechaDesde' => '2026-11-01',
            'estadoAsistencia' => 'asistio',
        ]))
        ->assertOk();

    $archivo = sys_get_temp_dir().DIRECTORY_SEPARATOR.'reporte-asistencias-filtrado.xlsx';
    file_put_contents($archivo, $respuesta->streamedContent());

    $contenido = Excel::toCollection(null, $archivo)->first()->flatten()
        ->map(fn ($celda): string => (string) $celda)
        ->implode('|');

    expect($contenido)->toContain('Carla Castro')
        ->not->toContain('Diego Díaz');

    crearEvidenciaReporte(
        crearJornadaReportes($carla, 'falto', '2026-11-12'),
        'pendiente',
        '2026-11-12'
    );
    crearEvidenciaReporte(
        crearJornadaReportes($carla, 'falto', '2026-11-13'),
        'aprobada',
        '2026-11-13'
    );

    $respuesta = $this->actingAs($admin)
        ->get(route('admin.reportes.exportar.evidencias.excel', [
            'practicante' => $carla->id,
            'estado' => 'aprobada',
        ]))
        ->assertOk();

    $archivo = sys_get_temp_dir().DIRECTORY_SEPARATOR.'reporte-evidencias-filtrado.xlsx';
    file_put_contents($archivo, $respuesta->streamedContent());

    $contenido = Excel::toCollection(null, $archivo)->first()->flatten()
        ->map(fn ($celda): string => (string) $celda)
        ->implode('|');

    expect($contenido)->toContain('Aprobada')
        ->not->toContain('Pendiente');
});

it('todos los reportes exportan PDF con los filtros seleccionados', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $practicante = crearPracticante();
    crearJornadaReportes($practicante, 'asistio', '2026-11-01');

    $rutas = [
        'admin.reportes.exportar.pdf',
        'admin.reportes.exportar.excel',
        'admin.reportes.exportar.evidencias.pdf',
        'admin.reportes.exportar.evidencias.excel',
        'admin.reportes.exportar.auditorias.pdf',
        'admin.reportes.exportar.auditorias.excel',
    ];

    foreach ($rutas as $nombre) {
        $this->actingAs($admin)
            ->get(route($nombre, [
                'practicante' => $practicante->id,
                'fechaDesde' => '2026-11-01',
                'fechaHasta' => '2026-11-30',
            ]))
            ->assertOk();
    }
});

it('valida que la fecha inicial no sea posterior a la fecha final en los tres reportes', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');

    $rutas = [
        'admin.reportes.filtrar',
        'admin.reportes.filtrar.evidencias',
        'admin.reportes.filtrar.auditorias',
    ];

    foreach ($rutas as $nombre) {
        $this->actingAs($admin)
            ->getJson(route($nombre, [
                'fechaDesde' => '2026-11-10',
                'fechaHasta' => '2026-11-01',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('fechaHasta');
    }
});

it('distingue al administrador responsable del practicante relacionado en auditoría', function () {
    $admin = crearAdminReportes('Ana', 'Acevedo');
    $otroAdmin = crearAdminReportes('Beto', 'Bravo');

    $carla = crearPracticante([
        'nombre' => 'Carla',
        'apellidos' => 'Castro',
        'email' => 'carla.causer@test.com',
    ]);
    $diego = crearPracticante([
        'nombre' => 'Diego',
        'apellidos' => 'Díaz',
        'email' => 'diego.subject@test.com',
        'discord_id' => '999999999999999999',
    ]);

    $asistenciaCarla = crearJornadaReportes($carla, 'falto', '2026-11-05');
    registrarActividadReporte(
        $asistenciaCarla,
        $admin,
        crearJustificacionReportes($asistenciaCarla, 'Motivo médico')
    );

    $asistenciaDiego = crearJornadaReportes($diego, 'falto', '2026-11-06');
    registrarActividadReporte(
        $asistenciaDiego,
        $otroAdmin,
        crearJustificacionReportes($asistenciaDiego, 'Otro motivo')
    );

    $base = route('admin.reportes.filtrar.auditorias');
    $consultar = fn (array $parametros): TestResponse => $this->actingAs($admin)
        ->getJson($base.'?'.http_build_query($parametros))
        ->assertOk();

    expect($consultar(['administrador' => $admin->id])->json('total'))->toBe(1)
        ->and($consultar(['administrador' => $admin->id])->json('data.0.practicante'))->toBe('Carla Castro')
        ->and($consultar(['administrador' => $otroAdmin->id])->json('data.0.administrador'))->toBe('Beto Bravo')
        ->and($consultar(['practicante' => $carla->id])->json('data.0.administrador'))->toBe('Ana Acevedo')
        ->and($consultar(['practicante' => $diego->id])->json('total'))->toBe(1)
        ->and($consultar([
            'administrador' => $admin->id,
            'practicante' => $diego->id,
        ])->json('total'))->toBe(0)
        ->and($consultar(['administrador' => $admin->id, 'practicante' => $carla->id])->json('total'))->toBe(1);
});
