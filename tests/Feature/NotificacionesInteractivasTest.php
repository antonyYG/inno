<?php

use App\Models\Asistencia;
use App\Models\Justificacion;
use App\Models\User;
use App\Notifications\FaltaAutomaticaNotification;
use App\Notifications\SolicitudPracticaNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

function notifCrearAsistencia(User $practicante, string $fecha = '2026-11-17'): Asistencia
{
    return Asistencia::create([
        'usuario_id' => $practicante->id,
        'fecha' => $fecha,
        'hora_inicio' => '08:55:00',
        'tipo' => 'asistio',
    ]);
}

function notifCrearJustificacion(Asistencia $asistencia, string $tipo): Justificacion
{
    return Justificacion::create([
        'asistencia_id' => $asistencia->id,
        'descripcion' => 'Documento de prueba',
        'archivo_path' => 'evidencias/prueba.png',
        'estado' => 'pendiente',
        'tipo' => $tipo,
        'fecha' => $asistencia->fecha,
    ]);
}

/**
 * @return array{0: DatabaseNotification, 1: Asistencia, 2: Justificacion}
 */
function notifCrearSolicitud(User $admin, string $tipo): array
{
    $practicante = crearPracticante([
        'discord_id' => fake()->unique()->numerify('##################'),
    ]);
    $asistencia = notifCrearAsistencia($practicante);
    $justificacion = notifCrearJustificacion($asistencia, $tipo);

    $admin->notify(new SolicitudPracticaNotification($justificacion));

    $notificacion = $admin->notifications()
        ->whereJsonContains('data->justificacion_id', $justificacion->id)
        ->first();

    return [$notificacion, $asistencia, $justificacion];
}

/**
 * @return array{0: DatabaseNotification, 1: Asistencia}
 */
function notifCrearFalta(User $admin): array
{
    $practicante = crearPracticante([
        'discord_id' => fake()->unique()->numerify('##################'),
    ]);
    $asistencia = notifCrearAsistencia($practicante);
    $asistencia->update(['tipo' => 'falto', 'hora_inicio' => null]);

    $admin->notify(new FaltaAutomaticaNotification(
        [$asistencia],
        '17/11/2026',
        '2026-11-17'
    ));

    $notificacion = $admin->notifications()
        ->where('type', FaltaAutomaticaNotification::class)
        ->first();

    return [$notificacion, $asistencia];
}

/**
 * @return array<string, string>
 */
function notifParametros(string $url): array
{
    $consulta = parse_url($url, PHP_URL_QUERY) ?: '';
    parse_str($consulta, $parametros);

    return $parametros;
}

it('abre la evidencia concreta que originó la notificación', function () {
    $admin = crearUsuario([], 'admin');

    [$notificacion, $asistencia, $justificacion] = notifCrearSolicitud($admin, 'evidencia');

    $respuesta = $this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk();

    $datos = collect($respuesta->json('notificaciones'))
        ->firstWhere('id', $notificacion->id);

    $parametros = notifParametros($datos['url']);

    expect($datos['tipo'])->toBe('evidencia')
        ->and($datos['icono'])->toBe('fa-solid fa-file-image')
        ->and($datos['url'])->toContain(route('admin.calendar.index'))
        ->and($parametros['asistencia'])->toBe((string) $asistencia->id)
        ->and($parametros['registro'])->toBe((string) $justificacion->id)
        ->and($parametros['fecha'])->toBe('2026-11-17');

    $this->actingAs($admin)
        ->get(route('admin.calendar.index'))
        ->assertOk()
        ->assertSee('abrirDesdeNotificacion', false)
        ->assertSee('registroDestacado', false);
});

it('marca la notificación como leída sin eliminarla', function () {
    $admin = crearUsuario([], 'admin');
    [$notificacion] = notifCrearSolicitud($admin, 'justificacion');

    $this->actingAs($admin)
        ->postJson(route('admin.notificaciones.read', $notificacion->id))
        ->assertOk()
        ->assertJson([
            'id' => $notificacion->id,
            'leida' => true,
            'contador' => 0,
        ]);

    $leida = $admin->notifications()->find($notificacion->id);

    expect($leida)->not->toBeNull()
        ->and($leida->read_at)->not->toBeNull()
        ->and($admin->notifications()->count())->toBe(1);
});

it('descuenta el contador y quita la notificación del listado de pendientes', function () {
    $admin = crearUsuario([], 'admin');
    [$primera] = notifCrearSolicitud($admin, 'evidencia');
    [$segunda] = notifCrearFalta($admin);

    $antes = $this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk();

    expect($antes->json('contador'))->toBe(2);

    $lectura = $this->actingAs($admin)
        ->postJson(route('admin.notificaciones.read', $primera->id))
        ->assertOk();

    expect($lectura->json('contador'))->toBe(1);

    $despues = $this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk();

    expect($despues->json('contador'))->toBe(1)
        ->and(collect($despues->json('notificaciones'))->pluck('id')->all())->toBe([$segunda->id]);
});

it('no muestra la notificación como pendiente al volver a abrir el modal', function () {
    $admin = crearUsuario([], 'admin');
    [$notificacion] = notifCrearFalta($admin);

    $this->actingAs($admin)
        ->postJson(route('admin.notificaciones.read', $notificacion->id))
        ->assertOk();

    $respuesta = $this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk();

    expect($respuesta->json('notificaciones'))->toBe([])
        ->and(collect($respuesta->json('historial'))->pluck('id')->all())->toBe([$notificacion->id]);
});

it('conserva la notificación leída en el historial', function () {
    $admin = crearUsuario([], 'admin');
    [$notificacion] = notifCrearSolicitud($admin, 'justificacion');

    $this->actingAs($admin)
        ->postJson(route('admin.notificaciones.read', $notificacion->id))
        ->assertOk();

    $historial = $this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk()
        ->json('historial.0');

    expect($historial['id'])->toBe($notificacion->id)
        ->and($historial['leida'])->toBeTrue()
        ->and($historial['url'])->toContain(route('admin.calendar.index'))
        ->and($admin->notifications()->count())->toBe(1);
});

it('no expone registros ajenos a un usuario sin rol de administrador', function () {
    $admin = crearUsuario([], 'admin');
    $practicante = crearPracticante();
    $otroPracticante = crearPracticante([
        'discord_id' => '999999999999999999',
        'email' => 'otro@example.com',
    ]);

    $asistenciaAjena = notifCrearAsistencia($otroPracticante);
    $justificacionAjena = notifCrearJustificacion($asistenciaAjena, 'evidencia');

    $admin->notify(new SolicitudPracticaNotification($justificacionAjena));
    $notificacion = $admin->notifications()->first();

    $respuesta = $this->actingAs($practicante)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk();

    expect($respuesta->json('notificaciones'))->toBe([]);

    $practicante->notify(new SolicitudPracticaNotification($justificacionAjena));
    $propia = $practicante->unreadNotifications()->first();

    $datos = $this->actingAs($practicante)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk()
        ->json('notificaciones.0');

    expect($datos['id'])->toBe($propia->id)
        ->and($datos['url'])->toBe(route('admin.dashboard'))
        ->and(notifParametros($datos['url']))->toBe([]);

    $calendario = $this->actingAs($practicante)
        ->getJson(route('admin.calendar.data', ['asistencia' => $asistenciaAjena->id]))
        ->assertOk()
        ->json();

    expect(collect($calendario)->pluck('id'))->not->toContain($asistenciaAjena->id);
});

it('resuelve la navegación de todos los tipos de notificación existentes', function () {
    $admin = crearUsuario([], 'admin');

    [$evidencia] = notifCrearSolicitud($admin, 'evidencia');
    [$justificacion] = notifCrearSolicitud($admin, 'justificacion');
    [$falta, $asistenciaFalta] = notifCrearFalta($admin);

    $porTipo = collect($this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk()
        ->json('notificaciones'))
        ->keyBy('tipo');

    expect($porTipo->has(['evidencia', 'justificacion', 'falta']))->toBeTrue()
        ->and($porTipo->get('evidencia')['icono'])->toBe('fa-solid fa-file-image')
        ->and($porTipo->get('justificacion')['icono'])->toBe('fa-solid fa-file-circle-check')
        ->and($porTipo->get('falta')['icono'])->toBe('fa-solid fa-calendar-xmark');

    $parametrosFalta = notifParametros($porTipo->get('falta')['url']);

    expect($parametrosFalta['asistencia'])->toBe((string) $asistenciaFalta->id)
        ->and($parametrosFalta['fecha'])->toBe('2026-11-17')
        ->and($porTipo->get('evidencia')['fecha'])->not->toBeEmpty()
        ->and($porTipo->get('evidencia')['hora'])->not->toBeEmpty();
});

it('envía las notificaciones desconocidas a una vista segura', function () {
    $admin = crearUsuario([], 'admin');

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'notificacion-externa',
        'notifiable_type' => User::class,
        'notifiable_id' => $admin->id,
        'data' => [
            'titulo' => 'Aviso general',
            'mensaje' => 'No requiere un registro específico.',
        ],
    ]);

    $datos = $this->actingAs($admin)
        ->getJson(route('admin.notificaciones.data'))
        ->assertOk()
        ->json('notificaciones.0');

    expect($datos['tipo'])->toBe('general')
        ->and($datos['icono'])->toBe('fa-solid fa-bell')
        ->and($datos['url'])->toBe(route('admin.dashboard'));
});
