<?php

use App\Models\Asistencia;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/*
| Escenarios de seguimiento de prácticas, certificados y permisos.
*/

function crearJornadaValida(User $usuario, string $fecha, string $tiempo): Asistencia
{
    return Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => $fecha,
        'tipo' => 'asistio',
        'hora_inicio' => '08:00:00',
        'hora_fin' => '13:00:00',
        'tiempo_trabajado' => $tiempo,
    ]);
}

it('calcula horas acumuladas, restantes y porcentaje', function () {
    $usuario = crearPracticante([
        'horas_requeridas' => 10,
        'fecha_inicio' => '2026-11-02',
        'fecha_fin' => '2026-12-31',
    ]);

    crearJornadaValida($usuario, '2026-11-03', '05:00:00');
    crearJornadaValida($usuario, '2026-11-04', '02:30:00');

    $this->actingAs($usuario);

    $dashboard = app(DashboardService::class)->getDashboardPracticante();
    $seguimiento = $dashboard['seguimiento'];

    expect($seguimiento['horas_acumuladas'])->toBe('07:30')
        ->and($seguimiento['horas_pendientes'])->toBe('02:30')
        ->and($seguimiento['porcentaje'])->toBe(75.0)
        ->and($seguimiento['estado_seguimiento'])->toBe('en_curso');
});

it('muestra estado completado al cumplir las horas requeridas', function () {
    $usuario = crearPracticante([
        'horas_requeridas' => 5,
        'fecha_inicio' => '2026-11-02',
        'fecha_fin' => '2026-12-31',
    ]);

    crearJornadaValida($usuario, '2026-11-03', '05:00:00');

    $this->actingAs($usuario);

    $seguimiento = app(DashboardService::class)->getDashboardPracticante()['seguimiento'];

    expect($seguimiento['estado_seguimiento'])->toBe('completado')
        ->and($seguimiento['porcentaje'])->toBe(100.0);
});

it('muestra estado vencido al pasar la fecha de fin sin completar horas', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 0, 0, 'America/Lima'));

    $usuario = crearPracticante([
        'horas_requeridas' => 10,
        'fecha_inicio' => '2026-11-02',
        'fecha_fin' => '2026-11-14',
    ]);

    crearJornadaValida($usuario, '2026-11-03', '05:00:00');

    $this->actingAs($usuario);

    $seguimiento = app(DashboardService::class)->getDashboardPracticante()['seguimiento'];

    expect($seguimiento['estado_seguimiento'])->toBe('vencido');
});

it('no suma horas de faltas ni de justificaciones', function () {
    $usuario = crearPracticante(['horas_requeridas' => 5]);

    crearJornadaValida($usuario, '2026-11-03', '02:00:00');

    Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => '2026-11-04',
        'tipo' => 'falto',
        'tiempo_trabajado' => '05:00:00',
    ]);

    Asistencia::create([
        'usuario_id' => $usuario->id,
        'fecha' => '2026-11-05',
        'tipo' => 'justificado',
        'tiempo_trabajado' => '05:00:00',
    ]);

    $this->actingAs($usuario);

    $seguimiento = app(DashboardService::class)->getDashboardPracticante()['seguimiento'];

    expect($seguimiento['segundos_acumulados'])->toBe(7200);
});

it('bloquea el certificado si no cumple las horas', function () {
    $usuario = crearPracticante(['horas_requeridas' => 360]);

    crearJornadaValida($usuario, '2026-11-03', '05:00:00');

    $this->actingAs($usuario)
        ->get(route('admin.certificate.mine'))
        ->assertRedirect()
        ->assertSessionHas('swal.icon', 'warning');
});

it('genera el certificado propio cuando cumple las horas', function () {
    $usuario = crearPracticante([
        'horas_requeridas' => 5,
        'fecha_inicio' => '2026-11-02',
        'fecha_fin' => '2026-11-30',
    ]);

    crearJornadaValida($usuario, '2026-11-03', '05:00:00');

    $respuesta = $this->actingAs($usuario)
        ->get(route('admin.certificate.mine'));

    $respuesta->assertOk();

    expect($respuesta->headers->get('content-disposition'))->toContain('.docx');
});

it('un practicante no puede generar el certificado de otro', function () {
    $dueno = crearPracticante(['horas_requeridas' => 5]);
    $otro = crearPracticante(['discord_id' => '777777777777777777']);

    crearJornadaValida($dueno, '2026-11-03', '05:00:00');

    $this->actingAs($otro)
        ->get(route('admin.certificate.generate', $dueno))
        ->assertForbidden();
});

it('un practicante no puede consultar la auditoría administrativa', function () {
    $practicante = crearPracticante();

    $this->actingAs($practicante)
        ->get(route('admin.auditorias.index'))
        ->assertForbidden();
});

it('un administrador puede consultar la auditoría administrativa', function () {
    $admin = crearUsuario([], 'admin');

    $this->actingAs($admin)
        ->get(route('admin.auditorias.index'))
        ->assertOk();
});

it('un practicante no puede consultar los reportes', function () {
    $practicante = crearPracticante();

    $this->actingAs($practicante)
        ->get(route('admin.reportes.index'))
        ->assertForbidden();
});

it('un practicante no puede listar usuarios', function () {
    $practicante = crearPracticante();

    $this->actingAs($practicante)
        ->get(route('admin.users.data'))
        ->assertForbidden();
});

it('el calendario del practicante solo muestra sus asistencias', function () {
    $dueno = crearPracticante();
    $otro = crearPracticante(['discord_id' => '666666666666666666']);

    crearJornadaValida($dueno, '2026-11-03', '05:00:00');
    crearJornadaValida($otro, '2026-11-03', '05:00:00');

    $this->actingAs($dueno)
        ->getJson(route('admin.calendar.data'))
        ->assertOk()
        ->assertJsonCount(1);
});

it('el dashboard del practicante no mezcla datos de otros', function () {
    $dueno = crearPracticante(['horas_requeridas' => 5]);
    $otro = crearPracticante(['discord_id' => '555555555555555555']);

    crearJornadaValida($dueno, '2026-11-03', '05:00:00');
    crearJornadaValida($otro, '2026-11-04', '05:00:00');

    $this->actingAs($dueno);

    $dashboard = app(DashboardService::class)->getDashboardPracticante();

    expect($dashboard['seguimiento']['segundos_acumulados'])->toBe(18000);
});

it('la edición del usuario sincroniza el rol', function () {
    $admin = crearUsuario([], 'admin');
    $practicante = crearPracticante();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $practicante), [
            'nombre' => 'Juan',
            'apellidos' => 'Pérez',
            'rol' => 'admin',
            'email' => $practicante->email,
            'fecha_inicio' => '2026-11-02',
            'fecha_fin' => '2026-12-31',
            'horas_requeridas' => 200,
        ]);

    expect($practicante->fresh()->hasRole('admin'))->toBeTrue()
        ->and($practicante->fresh()->horas_requeridas)->toBe(200)
        ->and($practicante->fresh()->fecha_inicio->toDateString())->toBe('2026-11-02');
});

it('valida que la fecha de fin no sea anterior a la de inicio', function () {
    $admin = crearUsuario([], 'admin');
    $practicante = crearPracticante();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $practicante), [
            'nombre' => 'Juan',
            'apellidos' => 'Pérez',
            'rol' => 'practicante',
            'email' => $practicante->email,
            'fecha_inicio' => '2026-12-31',
            'fecha_fin' => '2026-11-02',
        ])
        ->assertSessionHasErrors(['fecha_fin']);
});

it('las notificaciones se envían al presentar una evidencia', function () {
    $admin = crearUsuario([], 'admin');
    $practicante = crearPracticante();

    $asistencia = crearJornadaValida($practicante, '2026-11-16', '05:00:00');

    $this->actingAs($practicante)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Avance del día',
        ]);

    expect($admin->fresh()->unreadNotifications()->count())->toBe(1);
});

it('marcar una notificación como leída no cambia los estados de revisión', function () {
    $admin = crearUsuario([], 'admin');
    $practicante = crearPracticante();

    $asistencia = crearJornadaValida($practicante, '2026-11-16', '05:00:00');

    $this->actingAs($practicante)
        ->post(route('admin.practicing.evidencias.store', $asistencia), [
            'archivo' => UploadedFile::fake()->image('trabajo.png'),
            'descripcion' => 'Avance del día',
        ]);

    $notificacion = $admin->unreadNotifications()->first();

    $this->actingAs($admin)
        ->post(route('admin.notificaciones.read', $notificacion->id));

    // Las notificaciones no admiten fresh(): se vuelven a consultar.
    $leida = $admin->notifications()->find($notificacion->id);

    expect($asistencia->fresh()->justificaciones()->first()->estado)->toBe('pendiente')
        ->and($leida->read_at)->not->toBeNull();
});
