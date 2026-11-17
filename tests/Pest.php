<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers de pruebas
|--------------------------------------------------------------------------
*/

/**
 * Crea un usuario con rol asignado.
 */
function crearUsuario(array $atributos = [], string $rol = 'practicante'): User
{
    // Estado explícito: el middleware 'active' lo consulta en la instancia en memoria.
    $usuario = User::factory()->create(array_merge(['estado' => true], $atributos));
    $usuario->assignRole($rol);

    return $usuario;
}

/**
 * Crea un practicante listo para registrar asistencia.
 */
function crearPracticante(array $atributos = []): User
{
    return crearUsuario(array_merge([
        'discord_id' => '123456789012345678',
        'estado' => true,
    ], $atributos), 'practicante');
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
