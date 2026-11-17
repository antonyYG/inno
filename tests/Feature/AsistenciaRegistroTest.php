<?php

use App\Models\Asistencia;
use Illuminate\Support\Carbon;

/*
| Escenarios de registro de asistencia (TickController):
| - Antes de 08:00  -> asistio
| - 08:00 a 12:59   -> tardanza
| - 13:00 en adelante -> bloqueado
| - Solo un registro por día, solo usuarios activos con Discord ID.
*/

it('clasifica como asistio un registro antes de las 08:00', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 7, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)
        ->post(route('admin.tick.marcar'))
        ->assertRedirect();

    $asistencia = Asistencia::where('usuario_id', $usuario->id)->first();

    expect($asistencia)->not->toBeNull()
        ->and($asistencia->tipo->value)->toBe('asistio');
});

it('clasifica como tardanza un registro exactamente a las 08:00', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)->post(route('admin.tick.marcar'));

    expect(Asistencia::first()->tipo->value)->toBe('tardanza');
});

it('clasifica como tardanza un registro a las 08:30', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 8, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)->post(route('admin.tick.marcar'));

    expect(Asistencia::first()->tipo->value)->toBe('tardanza');
});

it('clasifica como tardanza un registro a las 09:00 sin bloquearlo', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)->post(route('admin.tick.marcar'));

    expect(Asistencia::first()->tipo->value)->toBe('tardanza');
});

it('clasifica como tardanza un registro a las 12:30', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 12, 30, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)->post(route('admin.tick.marcar'));

    expect(Asistencia::first()->tipo->value)->toBe('tardanza');
});

it('bloquea el registro a partir de las 13:00', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 13, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)
        ->post(route('admin.tick.marcar'))
        ->assertSessionHas('error');

    expect(Asistencia::count())->toBe(0);
});

it('no permite registrar asistencia dos veces el mismo día', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearPracticante();

    $this->actingAs($usuario)->post(route('admin.tick.marcar'));
    $this->actingAs($usuario)->post(route('admin.tick.marcar'));

    expect(Asistencia::count())->toBe(1);
});

it('rechaza el registro de un usuario sin Discord ID', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearUsuario(['discord_id' => null]);

    $this->actingAs($usuario)
        ->post(route('admin.tick.marcar'))
        ->assertSessionHas('error');

    expect(Asistencia::count())->toBe(0);
});

it('rechaza el registro de un usuario inactivo', function () {
    Carbon::setTestNow(Carbon::create(2026, 11, 16, 9, 0, 0, 'America/Lima'));

    $usuario = crearPracticante(['estado' => false]);

    // El middleware 'active' cierra la sesión y redirige al login.
    $this->actingAs($usuario)
        ->post(route('admin.tick.marcar'))
        ->assertRedirect()
        ->assertSessionHasErrors(['email' => 'Cuenta inactiva']);

    expect(Asistencia::count())->toBe(0);
});
