<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoUserSeeder extends Seeder
{
    private const PASSWORD_DEMO = '12345678';

    private const ADMIN_EMAIL = 'pepe@gmail.com';

    private const PRACTICANTE_EMAIL = 'practicante@practicontrol.test';

    private const CARLOS_EMAIL = 'carlos.ramirez@practicontrol.test';

    private const JUAN_EMAIL = 'juan.perez@practicontrol.test';

    private const MARIA_EMAIL = 'maria.torres@practicontrol.test';

    public function run(): void
    {
        $areaDesarrolloWeb = Area::where('nombre', 'Desarrollo web')->firstOrFail();
        $areaDisenoGrafico = Area::where('nombre', 'Diseño gráfico')->firstOrFail();

        $this->ensureUsuario(
            [
                'nombre' => 'Administrador Demo',
                'apellidos' => '',
                'email' => self::ADMIN_EMAIL,
                'estado' => true,
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-12-18',
                'horas_requeridas' => 360,
            ],
            'admin',
            $areaDesarrolloWeb,
            false
        );

        $this->ensureUsuario(
            [
                'nombre' => 'Juan',
                'apellidos' => 'Pérez Demo',
                'email' => self::PRACTICANTE_EMAIL,
                'estado' => true,
                'area_id' => $areaDesarrolloWeb->id,
                'discord_id' => 'demo-practicante-0001',
                'fecha_inicio' => '2026-11-02',
                'fecha_fin' => '2026-12-18',
                'horas_requeridas' => 360,
            ],
            'practicante',
            $areaDesarrolloWeb,
            true
        );

        $this->ensureUsuario(
            [
                'nombre' => 'Carlos',
                'apellidos' => 'Ramírez Demo',
                'email' => self::CARLOS_EMAIL,
                'estado' => true,
                'area_id' => $areaDesarrolloWeb->id,
                'discord_id' => 'demo-carlos-0001',
                'fecha_inicio' => '2026-08-10',
                'fecha_fin' => '2026-11-17',
                'horas_requeridas' => 360,
            ],
            'practicante',
            $areaDesarrolloWeb,
            true
        );

        $this->ensureUsuario(
            [
                'nombre' => 'Juan',
                'apellidos' => 'Pérez Demo',
                'email' => self::JUAN_EMAIL,
                'estado' => true,
                'area_id' => $areaDesarrolloWeb->id,
                'discord_id' => 'demo-juan-0001',
                'fecha_inicio' => '2026-09-10',
                'fecha_fin' => '2026-11-17',
                'horas_requeridas' => 360,
            ],
            'practicante',
            $areaDesarrolloWeb,
            true
        );

        $this->ensureUsuario(
            [
                'nombre' => 'María',
                'apellidos' => 'Torres Demo',
                'email' => self::MARIA_EMAIL,
                'estado' => true,
                'area_id' => $areaDisenoGrafico->id,
                'discord_id' => 'demo-maria-0001',
                'fecha_inicio' => '2026-10-23',
                'fecha_fin' => '2026-11-17',
                'horas_requeridas' => 360,
            ],
            'practicante',
            $areaDisenoGrafico,
            true
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Crea o ajusta únicamente el usuario de demostración identificado por email.
     *
     * @param  array<string, mixed>  $atributos
     */
    private function ensureUsuario(
        array $atributos,
        string $rol,
        Area $areaPorDefecto,
        bool $actualizarAtributosDemo
    ): User {
        $usuario = User::firstOrNew(['email' => $atributos['email']]);
        $esNuevo = ! $usuario->exists;

        if ($esNuevo) {
            $atributos['area_id'] = $areaPorDefecto->id;
        }

        // El administrador ya existente conserva su área y fechas; solo se ajustan
        // nombre, estado y contraseña para cumplir con las credenciales de demo.
        $atributosAplicar = $esNuevo || $actualizarAtributosDemo
            ? $atributos
            : collect($atributos)->only(['nombre', 'apellidos', 'estado'])->all();

        if (! $usuario->password || ! Hash::check(self::PASSWORD_DEMO, $usuario->password)) {
            $atributosAplicar['password'] = Hash::make(self::PASSWORD_DEMO);
        }

        $usuario->fill($atributosAplicar);

        if ($usuario->isDirty()) {
            $usuario->save();
        }

        if (! $usuario->hasRole($rol)) {
            $usuario->assignRole($rol);
        }

        return $usuario;
    }
}
